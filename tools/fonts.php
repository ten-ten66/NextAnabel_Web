<?php
/**
 * 日本語Webフォントのサブセット化とセルフホスト（tools/build.php から呼ばれる）
 *
 *   php tools/fonts.php <サイトの出力ディレクトリ> [self|google]
 *
 * 日本語フォントをそのまま Google Fonts から読むと、ページごとに数十〜百数十個の分割ファイルを
 * 別ドメインから取得するため、モバイル回線では表示が大きく遅れる。そこでビルド時に、
 *   1. サイト内の HTML・JavaScript で使っている文字を、出現回数の多い順に集める
 *   2. Google Fonts の text= 指定で、その文字だけを含むフォントを取得する
 *      （text= は長すぎると無視されて通常の分割ファイルが返るため、URL が上限を超えないよう
 *        文字をいくつかの塊に分けて取得し、塊ごとの unicode-range で使い分ける）
 *   3. assets/fonts/ に保存し、同一オリジンから表示を止めずに読み込むよう HTML を書き換える
 * 想定外の応答（分割ファイルが返る等）や取得失敗のときは、元の Google Fonts の読み込みを残す。
 *
 * google モードでは保存せず、文字数が1回の要求に収まるときだけ text= を付ける
 * （外部フォントを Google Fonts からしか読めない claude.ai Artifact 向け）。
 */

declare(strict_types=1);

const UA = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0 Safari/537.36';
const LINK_PATTERN = '#<link rel="stylesheet" href="(https://fonts\.googleapis\.com/css2\?[^"]+)">\n?#';
// text= を付けた URL の長さの上限。約5,700字までは有効で、約7,300字では無視された（2026年10月の実測）
const MAX_TEXT_PARAM = 4000;

$dir = rtrim($argv[1] ?? '', '/');
$mode = ($argv[2] ?? 'self') === 'google' ? 'google' : 'self';
if ($dir === '' || !is_dir($dir)) {
    fwrite(STDERR, "使い方: php tools/fonts.php <サイトの出力ディレクトリ> [self|google]\n");
    exit(1);
}

$htmlFiles = glob($dir . '/*.html') ?: [];
$googleUrls = [];
foreach ($htmlFiles as $file) {
    if (preg_match_all(LINK_PATTERN, (string) file_get_contents($file), $m)) {
        foreach ($m[1] as $url) {
            $googleUrls[html_entity_decode($url)] = true;
        }
    }
}
if (!$googleUrls) {
    exit(0);
}

$chars = collect_chars(array_merge($htmlFiles, glob($dir . '/assets/js/*.js') ?: []));
$chunks = chunk_chars($chars);

if ($mode === 'google') {
    if (count($chunks) > 1) {
        printf("  フォント: 使用文字が多いため Google Fonts の通常の読み込みのまま（%d文字・%s）\n", count($chars), basename($dir));
        exit(0);
    }
    $text = implode('', $chunks[0]);
    foreach ($htmlFiles as $file) {
        $html = (string) preg_replace_callback(
            LINK_PATTERN,
            static fn ($m) => '<link rel="stylesheet" href="' . htmlspecialchars(html_entity_decode($m[1]) . '&text=' . rawurlencode($text), ENT_QUOTES) . '">' . "\n",
            (string) file_get_contents($file)
        );
        file_put_contents($file, $html);
    }
    printf("  フォント: Google Fonts に text= を付与（%d文字・%s）\n", count($chars), basename($dir));
    exit(0);
}

$fontDir = $dir . '/assets/fonts';
$tmpDir = $fontDir . '.tmp';
remove_dir($tmpDir);
mkdir($tmpDir, 0775, true);

$faces = [];    // ファイル名 => [family, style, weights[], range, chunk]
foreach (array_keys($googleUrls) as $url) {
    // テンプレート側で text= を指定済みの読み込み（ロゴ用の欧文書体など）は、その文字だけで取得する
    [$baseUrl, $ownText] = split_text_param($url);
    $urlChunks = $ownText === null ? $chunks : chunk_chars(array_values(array_unique(mb_str_split($ownText))));
    $suffix = $ownText === null ? '' : '-t';
    foreach ($urlChunks as $index => $chunk) {
        $result = fetch_chunk($baseUrl, $chunk, $index, $tmpDir, $suffix);
        if ($result === null) {
            fwrite(STDERR, "  フォントを部分取得できなかったため Google Fonts のまま残します: {$url}\n");
            remove_dir($tmpDir);
            exit(0);
        }
        $faces += $result;
    }
}

remove_dir($fontDir);
rename($tmpDir, $fontDir);

$css = "/* tools/fonts.php が生成。サイト内で使う文字だけを含むサブセット（unicode-range で使い分け） */\n";
foreach ($faces as $name => $face) {
    $weights = array_unique($face['weights']);
    sort($weights);
    $css .= sprintf(
        "@font-face {\n  font-family: '%s';\n  font-style: %s;\n  font-weight: %s;\n  font-display: swap;\n  src: url(%s.woff2) format('woff2');\n  unicode-range: %s;\n}\n",
        $face['family'],
        $face['style'],
        count($weights) > 1 ? min($weights) . ' ' . max($weights) : (string) $weights[0],
        $name,
        $face['range']
    );
}
file_put_contents($fontDir . '/fonts.css', $css);

// フォントの CSS は表示を止めないよう非同期で読み込む（media="print" で取得し、読み込み後に all へ切り替える）。
// 日本語フォントは1ページで数百KBになり、先に読むと CSS やファーストビューの画像と回線を奪い合うため、
// 本文はいったん端末のフォントで表示し、Webフォントが届いた時点で差し替える（font-display: swap）。
// JavaScript が無効な環境では <noscript> で通常どおり読み込む。
$tags = '<link rel="stylesheet" href="assets/fonts/fonts.css" media="print" onload="this.media=\'all\'">' . "\n"
    . '<noscript><link rel="stylesheet" href="assets/fonts/fonts.css"></noscript>' . "\n";

foreach ($htmlFiles as $file) {
    $pending = $tags;
    $html = (string) preg_replace_callback(LINK_PATTERN, static function () use (&$pending) {
        $out = $pending;
        $pending = '';
        return $out;
    }, (string) file_get_contents($file));
    $html = (string) preg_replace('#<link rel="preconnect" href="https://fonts\.(googleapis|gstatic)\.com"( crossorigin)?>\n?#', '', $html);
    file_put_contents($file, $html);
}

$total = array_sum(array_map(static fn ($n) => filesize($fontDir . '/' . $n . '.woff2'), array_keys($faces)));
printf("  フォント: %dファイル・%d文字・%d分割 → %dKB（%s）\n", count($faces), count($chars), count($chunks), (int) round($total / 1024), basename($dir));

// ---------------------------------------------------------------------------

/**
 * 使っている文字を出現回数の多い順に返す（HTML の本文・属性・インライン JSON、サイトの JavaScript）
 *
 * @param list<string> $files
 * @return list<string>
 */
function collect_chars(array $files): array
{
    $count = [];
    foreach ($files as $file) {
        $text = html_entity_decode((string) file_get_contents($file), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // \uXXXX 形式でエスケープされた文字（JSON・JavaScript）も展開する
        $text = (string) preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', static fn ($m) => mb_chr((int) hexdec($m[1]), 'UTF-8'), $text);
        foreach (mb_str_split($text) as $char) {
            if (!preg_match('/\s/u', $char)) {
                $count[$char] = ($count[$char] ?? 0) + 1;
            }
        }
    }
    // 入力欄やエラー表示で使われやすい文字を補う
    foreach (mb_str_split('0123456789０１２３４５６７８９ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz!"#$%&\'()*+,-./:;<=>?@[\\]^_`{|}~、。・「」『』（）〜ー―…！？：；％＆＋－＝￥') as $char) {
        $count[$char] ??= 1;
    }
    arsort($count);
    return array_map('strval', array_keys($count));
}

/**
 * URL の長さが上限を超えないように文字を塊に分ける
 *
 * @param list<string> $chars
 * @return list<list<string>>
 */
function chunk_chars(array $chars): array
{
    $chunks = [[]];
    $length = 0;
    foreach ($chars as $char) {
        $encoded = strlen(rawurlencode($char));
        if ($length + $encoded > MAX_TEXT_PARAM) {
            $chunks[] = [];
            $length = 0;
        }
        $chunks[array_key_last($chunks)][] = $char;
        $length += $encoded;
    }
    return $chunks;
}

/**
 * 1つの塊の文字だけを含むフォントを取得して保存する。想定外の応答なら null。
 *
 * @param list<string> $chunk
 * @return array<string, array{family: string, style: string, weights: list<int>, range: string, chunk: int}>|null
 */
function fetch_chunk(string $url, array $chunk, int $index, string $tmpDir, string $suffix = ''): ?array
{
    $css = fetch($url . '&text=' . rawurlencode(implode('', $chunk)));
    if ($css === null || !preg_match_all('/@font-face\s*\{([^}]+)\}/', $css, $blocks)) {
        return null;
    }
    $seen = [];
    $byUrl = [];
    $faces = [];
    foreach ($blocks[1] as $block) {
        preg_match("/font-family:\s*'([^']+)'/", $block, $family);
        preg_match('/font-style:\s*(\w+)/', $block, $style);
        preg_match('/font-weight:\s*(\d+)/', $block, $weight);
        preg_match('/url\(([^)]+)\)/', $block, $src);
        preg_match('/unicode-range:\s*([^;]+);/', $block, $range);
        if (!$family || !$src) {
            return null;
        }
        $italic = ($style[1] ?? 'normal') === 'italic';
        $combo = $family[1] . '|' . ($italic ? 'i' : 'n') . '|' . ($weight[1] ?? '400');
        if (isset($seen[$combo])) {
            // 同じ書体・太さが複数ある＝text= が無視され、通常の分割ファイルが返っている
            return null;
        }
        $seen[$combo] = true;
        if (!isset($byUrl[$src[1]])) {
            $name = strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $family[1]), '-'))
                . '-' . ($weight[1] ?? '400') . ($italic ? 'i' : '') . $suffix . '-' . $index;
            $data = fetch($src[1]);
            if ($data === null) {
                return null;
            }
            file_put_contents($tmpDir . '/' . $name . '.woff2', $data);
            $byUrl[$src[1]] = $name;
            $faces[$name] = [
                'family' => $family[1],
                'style' => $italic ? 'italic' : 'normal',
                'weights' => [],
                'range' => trim($range[1] ?? unicode_range($chunk)),
                'chunk' => $index,
            ];
        }
        $faces[$byUrl[$src[1]]]['weights'][] = (int) ($weight[1] ?? 400);
    }
    return $faces;
}

/**
 * URL から text= を取り除き、[text= を除いた URL, 指定された文字列（なければ null）] を返す
 * （family= は複数あるため parse_str は使わない）
 *
 * @return array{0: string, 1: ?string}
 */
function split_text_param(string $url): array
{
    if (!preg_match('/[?&]text=([^&]*)/', $url, $m)) {
        return [$url, null];
    }
    $base = (string) preg_replace('/([?&])text=[^&]*&?/', '$1', $url);
    return [rtrim($base, '?&'), rawurldecode(str_replace('+', ' ', $m[1]))];
}

/** @param list<string> $chars */
function unicode_range(array $chars): string
{
    $points = array_unique(array_map(static fn ($c) => mb_ord($c, 'UTF-8'), $chars));
    sort($points);
    $ranges = [];
    foreach ($points as $point) {
        $last = array_key_last($ranges);
        if ($last !== null && $ranges[$last][1] === $point - 1) {
            $ranges[$last][1] = $point;
        } else {
            $ranges[] = [$point, $point];
        }
    }
    return implode(', ', array_map(
        static fn ($r) => $r[0] === $r[1] ? sprintf('U+%X', $r[0]) : sprintf('U+%X-%X', $r[0], $r[1]),
        $ranges
    ));
}

function fetch(string $url): ?string
{
    // libcurl は HTTPS_PROXY などの環境変数のプロキシ設定に従う
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => UA,
        CURLOPT_TIMEOUT => 30,
    ]);
    $data = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return (is_string($data) && $status === 200) ? $data : null;
}

function remove_dir(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    foreach (glob($dir . '/*') ?: [] as $file) {
        unlink($file);
    }
    rmdir($dir);
}
