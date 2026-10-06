<?php
/**
 * 日本語Webフォントのサブセット化とセルフホスト（tools/build.php から呼ばれる）
 *
 *   php tools/fonts.php <サイトの出力ディレクトリ>
 *
 * 日本語フォントをそのまま Google Fonts から読むと、ページごとに数十〜百数十個の分割ファイルを
 * 別ドメインから取得するため、モバイル回線では表示が大きく遅れる。そこでビルド時に、
 *   1. サイト内の HTML・JavaScript で使っている文字を集める
 *   2. Google Fonts の text= 指定で、その文字だけを含むフォントを取得する
 *   3. assets/fonts/ に保存し、同一オリジンから preload して読み込むよう HTML を書き換える
 * 取得に失敗した場合は元の Google Fonts の読み込みを残す（表示は崩れない）。
 *
 * google モードでは保存せず、Google Fonts の URL に text= を付けるだけにする
 * （外部フォントを Google Fonts からしか読めない claude.ai Artifact 向け）。
 */

declare(strict_types=1);

$dir = rtrim($argv[1] ?? '', '/');
$mode = ($argv[2] ?? 'self') === 'google' ? 'google' : 'self';
if ($dir === '' || !is_dir($dir)) {
    fwrite(STDERR, "使い方: php tools/fonts.php <サイトの出力ディレクトリ> [self|google]\n");
    exit(1);
}

const UA = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0 Safari/537.36';
const LINK_PATTERN = '#<link rel="stylesheet" href="(https://fonts\.googleapis\.com/css2\?[^"]+)">\n?#';

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

// 使っている文字を集める（HTML の本文・属性・インラインの JSON と、サイトの JavaScript）
$sources = $htmlFiles;
foreach (glob($dir . '/assets/js/*.js') ?: [] as $js) {
    $sources[] = $js;
}
$chars = [];
foreach ($sources as $file) {
    $text = (string) file_get_contents($file);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    // \uXXXX 形式でエスケープされた文字（JSON・JavaScript）も展開する
    $text = (string) preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', static fn ($m) => mb_chr((int) hexdec($m[1]), 'UTF-8'), $text);
    foreach (mb_str_split($text) as $char) {
        if (!preg_match('/\s/u', $char)) {
            $chars[$char] = true;
        }
    }
}
// 入力欄やエラー表示で使われやすい文字を補う
foreach (mb_str_split('0123456789０１２３４５６７８９ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz!"#$%&\'()*+,-./:;<=>?@[\\]^_`{|}~、。・「」『』（）〜ー―…！？：；％＆＋－＝￥') as $char) {
    $chars[$char] = true;
}
ksort($chars, SORT_STRING);
$text = implode('', array_keys($chars));

if ($mode === 'google') {
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
if (!is_dir($fontDir)) {
    mkdir($fontDir, 0775, true);
}

$faces = [];      // ファイル名 => [family, style, weights[]]
$order = [];      // URL に書かれた書体の順（preload の優先度に使う）
$replaced = [];
foreach (array_keys($googleUrls) as $url) {
    if (preg_match_all('/family=([^:&]+)/', $url, $fm)) {
        foreach ($fm[1] as $f) {
            $order[] = str_replace('+', ' ', urldecode($f));
        }
    }
    $css = fetch($url . '&text=' . rawurlencode($text));
    if ($css === null || !preg_match_all('/@font-face\s*\{([^}]+)\}/', $css, $blocks)) {
        fwrite(STDERR, "  フォントの取得に失敗したため Google Fonts のまま残します: {$url}\n");
        continue;
    }
    $byUrl = [];
    foreach ($blocks[1] as $block) {
        preg_match("/font-family:\s*'([^']+)'/", $block, $family);
        preg_match('/font-style:\s*(\w+)/', $block, $style);
        preg_match('/font-weight:\s*(\d+)/', $block, $weight);
        preg_match('/url\(([^)]+)\)/', $block, $src);
        if (!$family || !$src) {
            continue;
        }
        $italic = ($style[1] ?? 'normal') === 'italic';
        $key = $src[1];
        if (!isset($byUrl[$key])) {
            $name = strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $family[1]), '-')) . ($italic ? '-italic' : '');
            $suffix = '';
            while (isset($faces[$name . $suffix])) {
                $suffix = $suffix === '' ? '-2' : '-' . ((int) substr($suffix, 1) + 1);
            }
            $name .= $suffix;
            $file = $fontDir . '/' . $name . '.woff2';
            $data = fetch($src[1]);
            if ($data === null) {
                continue 2;
            }
            file_put_contents($file, $data);
            $byUrl[$key] = $name;
            $faces[$name] = ['family' => $family[1], 'style' => $italic ? 'italic' : 'normal', 'weights' => []];
        }
        $faces[$byUrl[$key]]['weights'][] = (int) ($weight[1] ?? 400);
    }
    $replaced[$url] = true;
}
if (!$replaced) {
    exit(0);
}
$css = "/* tools/fonts.php が生成。サイト内で使う文字だけを含むサブセット */\n";
foreach ($faces as $name => $face) {
    $weights = array_unique($face['weights']);
    sort($weights);
    $css .= sprintf(
        "@font-face {\n  font-family: '%s';\n  font-style: %s;\n  font-weight: %s;\n  font-display: swap;\n  src: url(%s.woff2) format('woff2');\n}\n",
        $face['family'],
        $face['style'],
        count($weights) > 1 ? min($weights) . ' ' . max($weights) : (string) $weights[0],
        $name
    );
}
file_put_contents($fontDir . '/fonts.css', $css);

// URL に先に書かれた書体から最大2ファイルを preload し、Google Fonts への接続を外す
$names = array_keys($faces);
usort($names, static function ($a, $b) use ($faces, $order) {
    $ia = array_search($faces[$a]['family'], $order, true);
    $ib = array_search($faces[$b]['family'], $order, true);
    return ($ia === false ? 99 : $ia) <=> ($ib === false ? 99 : $ib);
});
$preload = array_slice($names, 0, 2);
foreach ($htmlFiles as $file) {
    $html = (string) file_get_contents($file);
    $tags = implode("\n", array_map(
        static fn ($name) => '<link rel="preload" href="assets/fonts/' . $name . '.woff2" as="font" type="font/woff2" crossorigin>',
        $preload
    )) . "\n" . '<link rel="stylesheet" href="assets/fonts/fonts.css">' . "\n";
    $html = (string) preg_replace_callback(LINK_PATTERN, static function ($m) use ($replaced, &$tags) {
        if (!isset($replaced[html_entity_decode($m[1])])) {
            return $m[0];
        }
        $out = $tags;
        $tags = '';
        return $out;
    }, $html);
    if (!preg_match(LINK_PATTERN, $html)) {
        $html = (string) preg_replace('#<link rel="preconnect" href="https://fonts\.(googleapis|gstatic)\.com"( crossorigin)?>\n?#', '', $html);
    }
    file_put_contents($file, $html);
}

$total = array_sum(array_map(static fn ($n) => filesize($fontDir . '/' . $n . '.woff2'), array_keys($faces)));
printf("  フォント: %d書体・%d文字 → %dKB（%s）\n", count($faces), count($chars), (int) round($total / 1024), basename($dir));

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
