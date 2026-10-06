<?php
/**
 * 広告表現チェック
 *
 *   php tools/lint-compliance.php [出力ディレクトリ]   # 既定は docs/
 *
 * ビルド後の HTML とサイトのデータファイルを検査し、違反があれば終了コード 1 を返す。
 * 法令適合を保証するものではなく、制作時の見落としを機械的に減らすためのチェック。
 *
 * profile=medical  医療機関（医療広告に関するガイドラインを踏まえた禁止表現・必須表示）
 * profile=esthetic エステ（医療的な効能効果をうたう表現）
 * profile=none     対象外
 *
 * 除外: data-lint-ignore 属性を持つ要素（架空サイトの注記など）は検査しない。
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$outDir = rtrim($argv[1] ?? $root . '/docs', '/');
$manifest = json_decode((string) @file_get_contents($outDir . '/manifest.json'), true);
if (!is_array($manifest)) {
    fwrite(STDERR, "manifest.json が見つかりません: {$outDir}\n");
    exit(1);
}

$shared = [
    '最上級・No.1表現' => '/No\.?\s?1|ナンバー\s?ワン|日本一|世界一|業界(?:一|初|トップ)|地域(?:一|No)|最高(?:級|峰|の)|最上級|トップクラス/u',
    '最安表現' => '/最安|激安|格安/u',
    '効果の保証' => '/絶対|必ず(?:効果|治|改善|満足|キレイ|綺麗|痩)|効果(?:を)?保証|100\s?[%％]|完璧/u',
];
$rules = [
    'medical' => $shared + [
        '最新・最先端の強調' => '/最先端|最新鋭|最新の(?:治療|機器|設備|技術)/u',
        '永久の表現' => '/永久/u',
        '費用の強調（キャンペーン・割引・月額）' => '/キャンペーン|期間限定|今だけ|今なら|先着|[0-9０-９]+\s?[%％]\s?(?:OFF|オフ|off|引き)|割引|半額|し放題|打ち放題|回数無制限|月額|月々/u',
        '体験談・口コミ' => '/体験談|口コミ|お客様の声|患者様の声|喜びの声|満足度/u',
        '著名人の利用' => '/芸能人|有名人|インフルエンサー|著名人/u',
        'リスクがないかのような表現' => '/痛くない|痛みゼロ|無痛|ダウンタイム(?:は)?(?:なし|ゼロ|ありません)|副作用(?:は)?(?:なし|ゼロ|ありません)|リスク(?:は)?(?:なし|ゼロ|ありません)|安全な治療/u',
        'ビフォーアフター表記' => '/ビフォー\s?アフター|before\s?(?:&|and)?\s?after/iu',
    ],
    'esthetic' => $shared + [
        '医療行為を想起させる表現' => '/治療|治す|治る|完治|改善(?:します|できます|効果)|効く|医学的|医師監修/u',
        '身体の変化の断定' => '/脂肪(?:を|が)?(?:溶|燃焼|分解|除去)|セルライト(?:を|が)?(?:除去|消|溶)|痩せ|やせ(?:る|ます)|サイズダウン|小顔(?:に)?(?:なる|効果)|リフトアップ効果|若返|アンチエイジング|細胞(?:を|が)?(?:活性|再生)|デトックス効果|代謝(?:を)?(?:上げ|アップ)/u',
        '肌への医薬品的効能' => '/シミ(?:が|を)?(?:消|取|除去|薄く)|シワ(?:が|を)?(?:消|取|なくな)|ニキビ(?:が|を)?(?:治|消)|美白効果/u',
        '脱毛・永久の表現' => '/脱毛|永久/u',
    ],
];

$violations = [];
$checkedPages = 0;

foreach ($manifest['sites'] ?? [] as $siteName => $site) {
    $profile = (string) ($site['profile'] ?? 'none');
    if (!isset($rules[$profile])) {
        continue;
    }
    $pagesByPath = [];
    foreach ($site['pages'] as $entry) {
        $pagesByPath[basename($entry['path'])] = $entry;
    }

    // --- データの検査（施術データの必須項目） ---
    foreach ((array) ($site['data'] ?? []) as $kind => $relPath) {
        if ($kind !== 'treatments') {
            continue;
        }
        $file = $root . '/sites/' . $siteName . '/' . $relPath;
        $treatments = is_file($file) ? require $file : null;
        if (!is_array($treatments)) {
            $violations[] = [$siteName, $relPath, 'データ', '施術データを読み込めません'];
            continue;
        }
        foreach ($treatments as $t) {
            foreach (data_errors($t) as $error) {
                $violations[] = [$siteName, $relPath, 'データ', ($t['slug'] ?? '?') . ': ' . $error];
            }
            $page = 'treatment-' . ($t['slug'] ?? '') . '.html';
            if (!isset($pagesByPath[$page])) {
                $violations[] = [$siteName, $page, 'データ', '施術詳細ページが生成されていません'];
            }
        }
    }

    // --- HTML の検査 ---
    foreach ($site['pages'] as $entry) {
        $file = $outDir . '/' . $entry['path'];
        $doc = load_html((string) file_get_contents($file));
        $xpath = new DOMXPath($doc);
        [$text, $segments] = visible_text($doc);
        $checkedPages++;

        foreach ($rules[$profile] as $rule => $pattern) {
            if (preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as [$match, $offset]) {
                    $violations[] = [$siteName, $entry['path'], $rule, snippet($text, $offset, strlen($match))];
                }
            }
        }

        // 価格は総額（税込）で表示する
        if (preg_match_all('/[0-9０-９][0-9０-９,，]*\s*円/u', $text, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as [$match, $offset]) {
                if (in_tax_context($segments, $offset)) {
                    continue;
                }
                $after = mb_strcut($text, $offset + strlen($match), 40, 'UTF-8');
                if (!preg_match('/\A.{0,3}?[（(]\s*税込/us', $after)) {
                    $violations[] = [$siteName, $entry['path'], '総額表示（税込）がない価格', snippet($text, $offset, strlen($match))];
                }
            }
        }

        foreach (structure_errors($xpath, $entry['kind'] ?? 'page', $profile) as $error) {
            $violations[] = [$siteName, $entry['path'], '必須表示', $error];
        }
    }
}

if ($violations) {
    fwrite(STDERR, "表現チェック: " . count($violations) . " 件の違反\n");
    foreach ($violations as [$site, $path, $rule, $detail]) {
        fwrite(STDERR, "  - [{$rule}] {$site} / {$path}: {$detail}\n");
    }
    exit(1);
}
echo "表現チェック: 違反なし（{$checkedPages} ページ）\n";
exit(0);

// ---------------------------------------------------------------------------

/**
 * 施術データ1件の必須項目
 *
 * @param array<string, mixed> $t
 * @return list<string>
 */
function data_errors(array $t): array
{
    $errors = [];
    foreach (['slug', 'name', 'summary', 'sessions', 'downtime'] as $key) {
        if (!isset($t[$key]) || !is_string($t[$key]) || trim($t[$key]) === '') {
            $errors[] = "{$key} がありません";
        }
    }
    $prices = $t['prices'] ?? [];
    if (!is_array($prices) || !$prices) {
        $errors[] = 'prices（税込価格）がありません';
    } else {
        foreach ($prices as $price) {
            if (!is_int($price['amount'] ?? null) || $price['amount'] <= 0 || empty($price['label'])) {
                $errors[] = 'prices の各要素には label と正の整数 amount（税込）が必要です';
                break;
            }
        }
    }
    if (empty($t['risks']) || !is_array($t['risks'])) {
        $errors[] = 'risks（主なリスク・副作用）がありません';
    }
    if (!empty($t['unapproved'])) {
        foreach (['status', 'route', 'domestic', 'overseas', 'relief'] as $key) {
            if (empty($t['unapproved_info'][$key])) {
                $errors[] = "未承認医薬品等の必須表示 unapproved_info.{$key} がありません";
            }
        }
    }
    return $errors;
}

/**
 * ページ構造の必須表示
 *
 * @return list<string>
 */
function structure_errors(DOMXPath $xpath, string $kind, string $profile): array
{
    $errors = [];
    if ($profile === 'medical' && $kind === 'treatment') {
        foreach (['price' => '費用', 'sessions' => '回数・期間の目安', 'risks' => 'リスク・副作用', 'downtime' => 'ダウンタイム', 'contact' => '問い合わせ先'] as $key => $label) {
            if ($xpath->query("//*[@data-disclosure='{$key}']")->length === 0) {
                $errors[] = "{$label}（data-disclosure=\"{$key}\"）の表示がありません";
            } elseif ($xpath->query("//*[@data-disclosure='{$key}'][ancestor::details]")->length > 0) {
                $errors[] = "{$label}が折りたたみ（details）の中にあります。初期状態で見える位置に置いてください";
            }
        }
        $price = $xpath->query("//*[@data-disclosure='price']")->item(0);
        if ($price instanceof DOMElement) {
            $group = $xpath->query('ancestor::*[@data-disclosure-group]', $price)->item(0);
            if (!$group || $xpath->query(".//*[@data-disclosure='risks']", $group)->length === 0) {
                $errors[] = '費用とリスク・副作用を同じ data-disclosure-group 内に並べてください';
            }
        }
        if ($xpath->query("//*[@data-reviewed-by]")->length === 0) {
            $errors[] = '監修者・最終確認日（data-reviewed-by）の表示がありません';
        }
    }
    if ($xpath->query("//*[@data-course='qualifying']")->length > 0
        && $xpath->query("//*[@data-disclosure='cooling-off']")->length === 0) {
        $errors[] = '特定継続的役務に当たるコースがあるのに、クーリング・オフの案内（data-disclosure="cooling-off"）がありません';
    }
    foreach ($xpath->query('//*[@data-case]') as $case) {
        foreach (['treatment' => '治療内容', 'price' => '費用', 'risks' => 'リスク・副作用'] as $key => $label) {
            if ($xpath->query(".//*[@data-disclosure='{$key}']", $case)->length === 0) {
                $errors[] = "症例写真の枠に{$label}の説明がありません";
            }
        }
    }
    foreach ($xpath->query('//*[@data-unapproved]') as $block) {
        $items = [];
        foreach ($xpath->query('.//*[@data-unapproved-item]', $block) as $item) {
            $items[$item->getAttribute('data-unapproved-item')] = true;
        }
        foreach (['status', 'route', 'domestic', 'overseas', 'relief'] as $key) {
            if (!isset($items[$key])) {
                $errors[] = "未承認医薬品等の必須表示（{$key}）がありません";
            }
        }
    }
    return $errors;
}

function load_html(string $html): DOMDocument
{
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NONET);
    libxml_clear_errors();
    return $doc;
}

/**
 * 画面に表示されうる文字列を連結して返す（title・description・alt を含む）。
 * 価格の税込判定のため、data-price-context="tax-included" の内側かどうかも記録する。
 *
 * @return array{0: string, 1: list<array{0: int, 1: int, 2: bool}>} [テキスト, [開始, 終了, 税込文脈]]
 */
function visible_text(DOMDocument $doc): array
{
    $text = '';
    $segments = [];
    $xpath = new DOMXPath($doc);
    foreach ($xpath->query('//title | //meta[@name="description"]/@content') as $node) {
        $text .= trim($node->textContent) . "\n";
    }
    $walk = static function (DOMNode $node, bool $tax) use (&$walk, &$text, &$segments): void {
        if ($node instanceof DOMElement) {
            $tag = strtolower($node->tagName);
            if (in_array($tag, ['script', 'style', 'template', 'noscript', 'head'], true) || $node->hasAttribute('data-lint-ignore')) {
                return;
            }
            $tax = $tax || $node->getAttribute('data-price-context') === 'tax-included';
            if ($tag === 'img' && $node->getAttribute('alt') !== '') {
                $text .= ' ' . $node->getAttribute('alt') . ' ';
            }
            foreach ($node->childNodes as $child) {
                $walk($child, $tax);
            }
            if (in_array($tag, ['p', 'li', 'td', 'th', 'dt', 'dd', 'div', 'section', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'br', 'tr', 'caption', 'figcaption', 'blockquote', 'label', 'option'], true)) {
                $text .= "\n";
            }
            return;
        }
        if ($node instanceof DOMText) {
            $value = preg_replace('/\s+/u', ' ', $node->textContent) ?? '';
            $start = strlen($text);
            $text .= $value;
            $segments[] = [$start, strlen($text), $tax];
        }
    };
    $body = $doc->getElementsByTagName('body')->item(0);
    if ($body) {
        $walk($body, false);
    }
    return [$text, $segments];
}

/** @param list<array{0: int, 1: int, 2: bool}> $segments */
function in_tax_context(array $segments, int $offset): bool
{
    foreach ($segments as [$start, $end, $tax]) {
        if ($offset >= $start && $offset < $end) {
            return $tax;
        }
    }
    return false;
}

function snippet(string $text, int $offset, int $length): string
{
    $before = mb_substr(substr($text, 0, $offset), -12);
    $match = substr($text, $offset, $length);
    $after = mb_substr(substr($text, $offset + $length), 0, 12);
    return trim(preg_replace('/\s+/u', ' ', "…{$before}【{$match}】{$after}…") ?? '');
}
