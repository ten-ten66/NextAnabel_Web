<?php
/**
 * テンプレート用ヘルパー
 */

declare(strict_types=1);

/** HTMLエスケープ。テンプレートで値を出力するときは必ず通す。 */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
}

/** エスケープした上で改行を <br> に変換する。 */
function nl2br_e(string $value): string
{
    return nl2br(e($value), false);
}

/**
 * サイト設定（sites/<site>/data/site.php）の保持と参照
 *
 * @param array<string, mixed>|null $config 渡したときは設定を登録する
 */
function site_config(?array $config = null): array
{
    static $store = [];
    if ($config !== null) {
        $store = $config;
    }
    return $store;
}

/** ドット区切りでサイト設定を取り出す。例: site('address.locality') */
function site(?string $key = null, mixed $default = null): mixed
{
    $value = site_config();
    if ($key === null) {
        return $value;
    }
    foreach (explode('.', $key) as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }
    return $value;
}

function is_static(): bool
{
    return BUILD_MODE === 'static';
}

/**
 * 静的書き出し時のファイル名
 * treatment + ['slug' => 'hifu'] → treatment-hifu.html
 *
 * @param array<string, string> $query
 */
function static_filename(string $page, array $query = []): string
{
    $parts = [$page];
    foreach ($query as $value) {
        $value = (string) $value;
        if (!preg_match('/\A[a-z0-9][a-z0-9-]*\z/', $value)) {
            throw new InvalidArgumentException("静的ファイル名に使えない値です: {$value}");
        }
        $parts[] = $value;
    }
    return implode('-', $parts) . '.html';
}

/**
 * サイト内リンク
 * サーバー版は treatment.php?slug=hifu、静的版は treatment-hifu.html を返す。
 * サイトは常にフラットな1階層で構成するため、相対パスのままどの公開先でも壊れない。
 *
 * @param array<string, string> $query
 */
function url(string $page, array $query = []): string
{
    if (preg_match('#\A(?:[a-z][a-z0-9+.-]*:|//|\#)#i', $page)) {
        return $page; // 外部URL・mailto:・tel:・ページ内リンク
    }
    $hash = '';
    if (str_contains($page, '#')) {
        [$page, $fragment] = explode('#', $page, 2);
        $hash = '#' . $fragment;
    }
    if (is_static()) {
        return static_filename($page, $query) . $hash;
    }
    return $page . '.php' . ($query ? '?' . http_build_query($query) : '') . $hash;
}

/** assets/ 配下のファイルURL。サーバー版のみ更新日時でキャッシュを無効化する。 */
function asset(string $path): string
{
    $path = ltrim($path, '/');
    $url = 'assets/' . $path;
    if (!is_static() && defined('SITE_DIR')) {
        $file = SITE_DIR . '/assets/' . $path;
        if (is_file($file)) {
            $url .= '?v=' . filemtime($file);
        }
    }
    return $url;
}

/**
 * 共通パーツの読み込み（sites/<site>/partials/<name>.php）
 * 渡した配列は変数として展開される。既存の変数は上書きしない。
 *
 * @param array<string, mixed> $vars
 */
function partial(string $name, array $vars = []): void
{
    $file = SITE_DIR . '/partials/' . $name . '.php';
    if (!is_file($file)) {
        throw new RuntimeException("partial が見つかりません: {$name}");
    }
    (static function (string $__file, array $__vars): void {
        extract($__vars, EXTR_SKIP);
        require $__file;
    })($file, $vars);
}

/** 22000 → 22,000円 */
function yen(int $amount): string
{
    return number_format($amount) . '円';
}

/** 22000 → 22,000円（税込）。料金表示は総額表示が義務のため原則こちらを使う。 */
function tax_in(int $amount): string
{
    return yen($amount) . '（税込）';
}

/** 日付を <time> 要素で出力する。 */
function time_tag(string $ymd, string $format = 'Y.m.d'): string
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $ymd);
    if ($date === false) {
        throw new InvalidArgumentException("日付の形式が不正です: {$ymd}");
    }
    return '<time datetime="' . e($date->format('Y-m-d')) . '">' . e($date->format($format)) . '</time>';
}

/** 現在のページの、サイト内での相対パス（canonical 生成用） */
function current_path(): string
{
    if (is_static()) {
        return (string) (getenv('PAGE_OUT') ?: 'index.html');
    }
    $script = basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'index.php'));
    $allowed = (array) site('canonical_params', ['slug']);
    $query = array_filter(
        $_GET,
        static fn ($value, $key) => in_array($key, $allowed, true) && is_string($value),
        ARRAY_FILTER_USE_BOTH
    );
    return $script . ($query ? '?' . http_build_query($query) : '');
}

/** 公開先の絶対URL。canonical と OGP にのみ使う。 */
function absolute_url(?string $path = null): string
{
    $path ??= current_path();
    if ($path === 'index.html' || $path === 'index.php') {
        $path = '';
    }
    $base = trim((string) site('base', ''), '/');
    return SITE_ORIGIN . '/' . ($base !== '' ? $base . '/' : '') . ltrim($path, '/');
}

/** ナビゲーションの現在地表示 */
function aria_current(string $pageId, string $currentId): string
{
    return $pageId === $currentId ? ' aria-current="page"' : '';
}
