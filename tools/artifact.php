<?php
/**
 * claude.ai Artifact 公開用のファイル一式を作る
 *
 *   php tools/artifact.php            # Artifact 用にビルドし、build/artifact/ を作成
 *
 * Artifact は外部フォントを Google Fonts からしか読めないため、--fonts=google で別途ビルドした
 * build/artifact-src/ を元にする（GitHub Pages 用の docs/ はフォントをセルフホストしている）。
 *
 * Artifact のメインページは head を持てない（公開時に雛形で包まれる）ため、
 *   - 各サンプル: メインページ = 概要カバー、サイト本体 = site/ 配下に完全な HTML として同梱
 *   - サンプル集: docs/index.html の head と body を取り出してメインページにする
 * サンプル集から各サンプルへのリンクは build/artifact/urls.json（公開後に記録した URL）で置き換える。
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$docs = $root . '/build/artifact-src';
$out = $root . '/build/artifact';
passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($root . '/tools/build.php') . ' --out=build/artifact-src --fonts=google', $code);
if ($code !== 0) {
    fwrite(STDERR, "ビルドに失敗しました\n");
    exit(1);
}
$manifest = json_decode((string) file_get_contents($docs . '/manifest.json'), true);
$urls = is_file($out . '/urls.json') ? (json_decode((string) file_get_contents($out . '/urls.json'), true) ?: []) : [];

$covers = [
    'skin-clinic' => [
        'title' => '白磁スキンクリニック',
        'type' => '美容皮膚科・コーポレートサイト',
        'accent' => '#2B4C8C',
        'accent_dark' => '#9DB4E8',
        'lead' => '施術の前に、費用の総額・回数の目安・リスクを説明しきることをブランドの軸に据えた美容皮膚科のサイトです。',
        'points' => [
            '素のPHPで共通パーツと施術データを一元管理（WordPressテーマ版も同梱）',
            '入力→確認→完了の予約フォーム（CSRF・二重送信防止・迷惑投稿対策）',
            '悩み別の絞り込みと並べ替えアニメーション、受付状況のリアルタイム表示',
            '施術ごとに費用・リスク・ダウンタイムを価格の隣に明示',
        ],
    ],
    'surgery-clinic' => [
        'title' => 'オルヴァン美容外科',
        'type' => '美容外科・コーポレートサイト',
        'accent' => '#8F7A57',
        'accent_dark' => '#C3A574',
        'lead' => '墨色と象牙色のエディトリアルデザインで、カウンセリング重視の姿勢を伝える美容外科のサイトです。',
        'points' => [
            'GSAP による見出しの文字分割アニメーションと、横スクロールで進む施術の流れ',
            '症例写真の隣に治療内容・費用・リスクを併記する掲載テンプレート',
            '施術ごとの詳細ページ（費用・経過・リスク・術後の注意）',
            'カウンセリング予約フォーム（入力→確認→完了）',
        ],
    ],
    'esthetic' => [
        'title' => '苔と麻',
        'type' => 'エステティックサロン',
        'accent' => '#7E6278',
        'accent_dark' => '#CDB2C6',
        'lead' => '植物の影と麻の質感で、静かな時間を表現したフェイシャル・ボディトリートメントサロンのサイトです。',
        'points' => [
            'スクロールに連動して描かれる植物の線画、奥行きのあるパララックス',
            '医療的な効能をうたわない表現設計と総額表示',
            'コース契約のクーリング・オフ案内',
            'キーボード操作に対応したギャラリーとダイアログ',
        ],
    ],
    'lp' => [
        'title' => '白磁 医療脱毛LP',
        'type' => '医療脱毛ランディングページ',
        'accent' => '#2346A0',
        'accent_dark' => '#A9BEF2',
        'lead' => '値引きや体験談に頼らず、総額と回数を先に示すことで予約につなげる医療脱毛のランディングページです。',
        'points' => [
            '人体図と連動し、税込の総額と通院回数の目安を出す料金シミュレーター',
            '入力しやすい予約フォーム（PHP の JSON API に非同期送信）',
            '固定CTAと計測用のイベント送信（dataLayer）',
            'コーポレートサイトと料金データ・医院情報を共有',
        ],
    ],
];

remove_tree($out . '/sites');
foreach ($covers as $site => $cover) {
    $info = $manifest['sites'][$site] ?? null;
    if (!$info) {
        fwrite(STDERR, "ビルド結果に {$site} がありません（スキップ）\n");
        continue;
    }
    $dest = $out . '/sites/' . $site;
    copy_tree($docs . '/' . $info['base'], $dest . '/site');
    $pages = [];
    foreach ($info['pages'] as $page) {
        $file = basename($page['path']);
        if ($file === '404.html') {
            continue;
        }
        $html = (string) file_get_contents($docs . '/' . $page['path']);
        preg_match('#<title>(.*?)</title>#s', $html, $m);
        $label = html_entity_decode($m[1] ?? $file, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $label = trim(preg_replace('/【サンプル】$|｜.*$/u', '', $label) ?? $label);
        $pages[] = ['href' => 'site/' . $file, 'label' => $file === 'index.html' ? 'トップページ' : $label];
    }
    file_put_contents($dest . '/index.html', cover_html($cover, $pages, is_file($dest . '/site/assets/img/ogp.png')));
    write_files_map($dest, 'site');
    printf("  %-16s %3d files\n", $site, count(json_decode((string) file_get_contents($dest . '/files.json'), true)));
}

// サンプル集（docs/index.html）
if (is_file($docs . '/index.html')) {
    $dest = $out . '/sites/hub';
    $html = (string) file_get_contents($docs . '/index.html');
    foreach ($urls as $site => $url) {
        $html = preg_replace('#href="' . preg_quote($site, '#') . '/(?:index\.html)?"#', 'href="' . htmlspecialchars($url, ENT_QUOTES) . '"', $html) ?? $html;
    }
    if (!is_dir($dest)) {
        mkdir($dest, 0775, true);
    }
    file_put_contents($dest . '/index.html', strip_document($html));
    if (is_dir($docs . '/assets')) {
        copy_tree($docs . '/assets', $dest . '/assets');
    }
    write_files_map($dest, 'assets');
    echo "  hub              ok\n";
}

// ---------------------------------------------------------------------------

/** 完全な HTML から doctype・html・head・body の枠を外し、Artifact のメインページ用にする */
function strip_document(string $html): string
{
    preg_match('#<html[^>]*\blang="([^"]+)"#i', $html, $lang);
    preg_match('#<head[^>]*>(.*?)</head>#is', $html, $head);
    preg_match('#<body([^>]*)>(.*)</body>#is', $html, $body);
    $headInner = $head[1] ?? '';
    // 文字コード・viewport は公開時の雛形が持つため除く
    $headInner = preg_replace('#<meta\s+(charset|name="viewport")[^>]*>\s*#i', '', $headInner) ?? $headInner;
    preg_match('#class="([^"]*)"#', $body[1] ?? '', $bodyClass);
    $boot = '<script>document.documentElement.lang=' . json_encode($lang[1] ?? 'ja') . ';'
        . (isset($bodyClass[1]) ? 'document.body&&(document.body.className=' . json_encode($bodyClass[1]) . ');' : '')
        . '</script>';
    // <title> は先頭 8KB 以内に置く必要がある
    preg_match('#<title>.*?</title>#s', $headInner, $title);
    $headInner = str_replace($title[0] ?? '', '', $headInner);
    return ($title[0] ?? '') . "\n" . trim($headInner) . "\n" . trim($body[2] ?? '') . "\n" . $boot . "\n";
}

/**
 * @param array<string, mixed> $c
 * @param list<array{href: string, label: string}> $pages
 */
function cover_html(array $c, array $pages, bool $hasOg): string
{
    $e = static fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $points = implode('', array_map(static fn ($p) => '<li>' . $e($p) . '</li>', $c['points']));
    $links = implode('', array_map(static fn ($p) => '<li><a href="' . $e($p['href']) . '">' . $e($p['label']) . '</a></li>', $pages));
    $og = $hasOg ? '<img class="shot" src="site/assets/img/ogp.png" width="1200" height="630" alt="' . $e($c['title']) . 'のOGP画像">' : '';
    return <<<HTML
<title>{$e($c['title'])}</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Zen+Kaku+Gothic+New:wght@400;700&amp;display=swap">
<style>
/* 概要カバー: 余白の多い1カラム。色はサイトのアクセントを1色だけ借りる */
:root { --bg: #f5f6f7; --card: #ffffff; --fg: #17191c; --muted: #5a6168; --line: #d9dde1; --accent: {$c['accent']}; --on-accent: #ffffff; --font: "Zen Kaku Gothic New", "Hiragino Sans", "Yu Gothic", sans-serif; }
@media (prefers-color-scheme: dark) { :root:not([data-theme="light"]) { --bg: #121416; --card: #1a1d20; --fg: #eceef0; --muted: #a8b0b8; --line: #2c3136; --accent: {$c['accent_dark']}; --on-accent: #121416; color-scheme: dark; } }
:root[data-theme="dark"] { --bg: #121416; --card: #1a1d20; --fg: #eceef0; --muted: #a8b0b8; --line: #2c3136; --accent: {$c['accent_dark']}; --on-accent: #121416; color-scheme: dark; }
body { background: var(--bg); color: var(--fg); font-family: var(--font); line-height: 1.8; }
.cover { max-width: 46rem; margin-inline: auto; padding: clamp(2rem, 6vw, 4.5rem) 16px 4rem; display: grid; gap: 2rem; }
.eyebrow { margin: 0; font-size: .8125rem; letter-spacing: .08em; color: var(--muted); }
h1 { margin: .25rem 0 0; font-size: clamp(1.75rem, 5vw, 2.5rem); line-height: 1.35; text-wrap: balance; }
.lead { margin: 0; font-size: 1.0625rem; color: var(--fg); }
.open { justify-self: start; display: inline-flex; align-items: center; gap: .5em; padding: .9em 1.6em; border-radius: 999px; background: var(--accent); color: var(--on-accent); font-weight: 700; text-decoration: none; }
.open:hover { filter: brightness(1.08); }
.open:focus-visible, a:focus-visible { outline: 3px solid var(--accent); outline-offset: 3px; }
.shot { width: 100%; max-width: 100%; height: auto; border-radius: 10px; border: 1px solid var(--line); background: var(--card); }
section { display: grid; gap: .75rem; padding-top: 1.5rem; border-top: 1px solid var(--line); }
h2 { margin: 0; font-size: 1rem; }
ul { margin: 0; padding-left: 1.2em; display: grid; gap: .4rem; }
.pages { list-style: none; padding: 0; display: flex; flex-wrap: wrap; gap: .5rem; }
.pages a { display: inline-block; padding: .35em .9em; border: 1px solid var(--line); border-radius: 999px; background: var(--card); color: var(--fg); text-decoration: none; font-size: .875rem; }
.pages a:hover { border-color: var(--accent); }
.note { margin: 0; font-size: .8125rem; color: var(--muted); }
</style>
<main class="cover">
  <div>
    <p class="eyebrow">架空のサンプルサイト｜{$e($c['type'])}</p>
    <h1>{$e($c['title'])}</h1>
  </div>
  <p class="lead">{$e($c['lead'])}</p>
  <a class="open" href="site/index.html">サイトを開く</a>
  {$og}
  <section>
    <h2>見どころ</h2>
    <ul>{$points}</ul>
  </section>
  <section>
    <h2>ページ一覧</h2>
    <ul class="pages">{$links}</ul>
  </section>
  <p class="note">このサイトはWeb制作のサンプルとして作成した架空のものです。実在の医療機関・店舗・人物とは関係ありません。この画面ではフォームは送信されません。</p>
</main>
HTML;
}

/** Artifact の files 引数用に、公開パス → ローカルパスの対応表を書き出す */
function write_files_map(string $dest, string $subdir): void
{
    $map = [];
    $base = $dest . '/' . $subdir;
    if (is_dir($base)) {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            if ($file->isFile() && !str_ends_with($file->getFilename(), '.map')) {
                $rel = $subdir . '/' . str_replace('\\', '/', substr($file->getPathname(), strlen($base) + 1));
                $map[$rel] = $file->getPathname();
            }
        }
    }
    ksort($map);
    file_put_contents($dest . '/files.json', json_encode($map, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
}

function copy_tree(string $from, string $to): void
{
    if (!is_dir($to)) {
        mkdir($to, 0775, true);
    }
    foreach (scandir($from) ?: [] as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        is_dir("{$from}/{$item}") ? copy_tree("{$from}/{$item}", "{$to}/{$item}") : copy("{$from}/{$item}", "{$to}/{$item}");
    }
}

function remove_tree(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($it as $item) {
        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }
    rmdir($dir);
}
