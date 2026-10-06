<?php
/**
 * テンプレート用の関数
 *
 * @package hakuji
 */

declare(strict_types=1);

/** 医院情報 */
function hakuji_clinic(?string $key = null): mixed
{
    static $clinic = null;
    $clinic ??= require HAKUJI_DIR . '/inc/clinic.php';
    return $key === null ? $clinic : ($clinic[$key] ?? null);
}

/** 22000 → 22,000円（税込） */
function hakuji_tax_in(int $amount): string
{
    return number_format($amount) . '円（税込）';
}

/**
 * 改行区切りのテキストを配列にする（空行は除く）
 *
 * @return list<string>
 */
function hakuji_lines(string $text): array
{
    $lines = preg_split('/\R/u', $text) ?: [];
    return array_values(array_filter(array_map('trim', $lines), static fn ($line) => $line !== ''));
}

/**
 * 料金（「ラベル|金額」の行）を配列にする
 *
 * @return list<array{label: string, amount: int}>
 */
function hakuji_prices(int $postId): array
{
    $rows = [];
    foreach (hakuji_lines((string) get_post_meta($postId, '_hakuji_prices', true)) as $line) {
        [$label, $amount] = array_map('trim', explode('|', $line, 2) + [1 => '']);
        $amount = (int) preg_replace('/\D/', '', $amount);
        if ($label !== '' && $amount > 0) {
            $rows[] = ['label' => $label, 'amount' => $amount];
        }
    }
    return $rows;
}

/**
 * コース（「ラベル|期間（月）|金額」の行）。期間1か月超かつ5万円超は特定継続的役務に当たる。
 *
 * @return list<array{label: string, months: int, amount: int, qualifying: bool}>
 */
function hakuji_courses(int $postId): array
{
    $rows = [];
    foreach (hakuji_lines((string) get_post_meta($postId, '_hakuji_courses', true)) as $line) {
        $parts = array_map('trim', explode('|', $line));
        if (count($parts) !== 3) {
            continue;
        }
        $months = (int) $parts[1];
        $amount = (int) preg_replace('/\D/', '', $parts[2]);
        if ($parts[0] !== '' && $amount > 0) {
            $rows[] = ['label' => $parts[0], 'months' => $months, 'amount' => $amount, 'qualifying' => $months > 1 && $amount > 50000];
        }
    }
    return $rows;
}

/**
 * 公開に必要な表示のうち、未入力のもの
 *
 * @return list<string>
 */
function hakuji_missing_disclosures(int $postId): array
{
    $missing = [];
    if (!hakuji_prices($postId)) {
        $missing[] = '費用（税込）';
    }
    foreach (['_hakuji_sessions' => '回数・期間の目安', '_hakuji_downtime' => 'ダウンタイム', '_hakuji_reviewed' => '最終確認日'] as $key => $label) {
        if (trim((string) get_post_meta($postId, $key, true)) === '') {
            $missing[] = $label;
        }
    }
    if (!hakuji_lines((string) get_post_meta($postId, '_hakuji_risks', true))) {
        $missing[] = '主なリスク・副作用';
    }
    if (get_post_meta($postId, '_hakuji_unapproved', true) === '1') {
        foreach (HAKUJI_UNAPPROVED_ITEMS as $key => $label) {
            if (trim((string) get_post_meta($postId, '_hakuji_unapproved_' . $key, true)) === '') {
                $missing[] = '未承認医薬品等の表示：' . $label;
            }
        }
    }
    return $missing;
}

/** パンくず（表示と構造化データで同じ配列を使う） */
function hakuji_breadcrumbs(): array
{
    $items = [['label' => 'ホーム', 'url' => home_url('/')]];
    if (is_post_type_archive('treatment') || is_singular('treatment') || is_tax('concern')) {
        $items[] = ['label' => '施術一覧', 'url' => get_post_type_archive_link('treatment')];
    }
    if (is_singular()) {
        $items[] = ['label' => get_the_title(), 'url' => get_permalink()];
    } elseif (is_tax('concern')) {
        $items[] = ['label' => single_term_title('', false), 'url' => get_term_link(get_queried_object())];
    }
    return $items;
}

function hakuji_the_breadcrumbs(): void
{
    $items = hakuji_breadcrumbs();
    if (count($items) < 2) {
        return;
    }
    echo '<nav class="c-breadcrumb" aria-label="パンくずリスト"><ol>';
    foreach ($items as $i => $item) {
        $last = $i === count($items) - 1;
        echo '<li>' . ($last
            ? '<span aria-current="page">' . esc_html($item['label']) . '</span>'
            : '<a href="' . esc_url($item['url']) . '">' . esc_html($item['label']) . '</a>') . '</li>';
    }
    echo '</ol></nav>';
}
