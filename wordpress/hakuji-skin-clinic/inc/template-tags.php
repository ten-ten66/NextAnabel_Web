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
    echo '<nav class="c-breadcrumb" aria-label="パンくずリスト"><ol class="c-breadcrumb__list">';
    foreach ($items as $i => $item) {
        $last = $i === count($items) - 1;
        echo '<li class="c-breadcrumb__item">' . ($last
            ? '<span aria-current="page">' . esc_html($item['label']) . '</span>'
            : '<a href="' . esc_url($item['url']) . '">' . esc_html($item['label']) . '</a>') . '</li>';
    }
    echo '</ol></nav>';
}

// ---------------------------------------------------------------------------
// 静的サイト版（sites/skin-clinic）と同じマークアップを出すための関数
// ---------------------------------------------------------------------------

const HAKUJI_WEEKDAYS = ['日', '月', '火', '水', '木', '金', '土'];

/** 線のアイコン（静的サイト版と同じ図形） */
function hakuji_icon(string $name): string
{
    $paths = [
        'arrow' => '<path d="M4 12h15M13.5 6.5 19 12l-5.5 5.5"/>',
        'tel' => '<path d="M8.6 3.8 6.4 4.4c-1.3.4-2.1 1.7-1.8 3 1.6 6.1 6 10.5 12 12 1.3.3 2.6-.5 3-1.8l.6-2.2-4-2.3-2 1.6a9.9 9.9 0 0 1-5-5l1.6-2z"/>',
        'close' => '<path d="M6 6l12 12M18 6 6 18"/>',
        'calendar' => '<path d="M4.5 7.5h15v12h-15zM4.5 11h15M9 4.5v4M15 4.5v4"/>',
        'external' => '<path d="M14 4h6v6M20 4l-8.5 8.5M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/>',
        'pause' => '<path d="M9 6v12M15 6v12"/>',
        'play' => '<path d="M8 5.5v13l10-6.5z"/>',
    ];
    return '<svg class="c-icon c-icon--' . esc_attr($name) . '" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">'
        . ($paths[$name] ?? '') . '</svg>';
}

/** 22000 → 22,000円（税込） を装飾用の span で出す */
function hakuji_price_html(int $amount): string
{
    return '<span class="c-price"><span class="c-price__num">' . esc_html(number_format($amount)) . '</span>'
        . '<span class="c-price__unit">円</span><span class="c-price__tax">（税込）</span></span>';
}

/** 表の中など、見出しで税込を示している場所の金額 */
function hakuji_yen_html(int $amount): string
{
    return '<span class="c-price"><span class="c-price__num">' . esc_html(number_format($amount)) . '</span><span class="c-price__unit">円</span></span>';
}

/** 「HIFU（高密度焦点式超音波）」の括弧部分を小さく表示する */
function hakuji_name_html(string $name): string
{
    $pos = mb_strpos($name, '（');
    if ($pos === false || $pos === 0) {
        return esc_html($name);
    }
    return esc_html(mb_substr($name, 0, $pos)) . '<span class="c-name-sub">' . esc_html(mb_substr($name, $pos)) . '</span>';
}

/** 「見出し：説明」を [見出し, 説明] に分ける */
function hakuji_split_heading(string $text): array
{
    $parts = explode('：', $text, 2);
    return count($parts) === 2 ? $parts : ['', $text];
}

function hakuji_ja_time_tag(string $ymd): string
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $ymd);
    return $date ? '<time datetime="' . esc_attr($ymd) . '">' . esc_html($date->format('Y年n月j日')) . '</time>' : '';
}

function hakuji_tel_href(): string
{
    return 'tel:' . preg_replace('/\D/', '', (string) hakuji_clinic('tel'));
}

function hakuji_full_address(): string
{
    return hakuji_clinic('region') . hakuji_clinic('locality') . hakuji_clinic('street');
}

/** 月曜の診療時間を基準に、異なる曜日だけを注記する（例: 10:00〜19:00（日曜は17:00まで）） */
function hakuji_hours_summary(): string
{
    $schedule = (array) hakuji_clinic('schedule');
    $base = $schedule[1] ?? null;
    if (!is_array($base)) {
        return '';
    }
    $notes = [];
    foreach ([1, 2, 3, 4, 5, 6, 0] as $day) {
        $slot = $schedule[$day] ?? null;
        if (is_array($slot) && ($slot['open'] !== $base['open'] || $slot['close'] !== $base['close'])) {
            $notes[] = HAKUJI_WEEKDAYS[$day] . '曜は' . $slot['close'] . 'まで';
        }
    }
    return $base['open'] . '〜' . $base['close'] . ($notes ? '（' . implode('、', $notes) . '）' : '');
}

/** 受付状況・診療時間の表を JavaScript で更新するためのデータ（静的サイト版の reception_json() と同じ形） */
function hakuji_reception_json(): string
{
    return (string) wp_json_encode([
        'schedule' => hakuji_clinic('schedule'),
        'lastEntry' => (int) hakuji_clinic('last_entry_minutes'),
        'closed' => (array) hakuji_clinic('closed_dates'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

/** "19:00" から分を引いた時刻 */
function hakuji_time_minus(string $time, int $minutes): string
{
    [$h, $m] = array_map('intval', explode(':', $time));
    $total = $h * 60 + $m - $minutes;
    return sprintf('%02d:%02d', intdiv($total, 60), $total % 60);
}

/**
 * 曜日ごとの診療時間（月曜始まり・最後に祝日）
 *
 * @return list<array{day: string, label: string, open: ?string, close: ?string, last: ?string}>
 */
function hakuji_schedule_rows(): array
{
    $schedule = (array) hakuji_clinic('schedule');
    $lastEntry = (int) hakuji_clinic('last_entry_minutes');
    $rows = [];
    foreach ([1, 2, 3, 4, 5, 6, 0] as $day) {
        $slot = $schedule[$day] ?? null;
        $rows[] = [
            'day' => (string) $day,
            'label' => HAKUJI_WEEKDAYS[$day] . '曜日',
            'open' => $slot['open'] ?? null,
            'close' => $slot['close'] ?? null,
            'last' => $slot ? hakuji_time_minus((string) $slot['close'], $lastEntry) : null,
        ];
    }
    $rows[] = ['day' => 'holiday', 'label' => '祝日', 'open' => null, 'close' => null, 'last' => null];
    return $rows;
}

/** 地図アプリでの検索URL（建物名を除いた住所で検索する） */
function hakuji_map_url(): string
{
    $street = explode(' ', (string) hakuji_clinic('street'))[0];
    return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode(hakuji_clinic('region') . hakuji_clinic('locality') . $street);
}

function hakuji_reviewer_label(): string
{
    $r = (array) hakuji_clinic('reviewer');
    return $r['role'] . ' ' . $r['name'] . '（' . $r['specialty'] . '）';
}

/**
 * 施術投稿を、テンプレートで扱いやすい配列にまとめる
 *
 * @return array<string, mixed>
 */
function hakuji_treatment(WP_Post|int $post): array
{
    $post = get_post($post);
    $id = $post->ID;
    $meta = static fn (string $key): string => (string) get_post_meta($id, '_hakuji_' . $key, true);
    $unapprovedInfo = [];
    foreach (array_keys(HAKUJI_UNAPPROVED_ITEMS) as $key) {
        $unapprovedInfo[$key] = $meta('unapproved_' . $key);
    }
    $terms = get_the_terms($id, 'concern');
    return [
        'id' => $id,
        'slug' => $post->post_name,
        'url' => get_permalink($id),
        'name' => get_the_title($id),
        'en' => $meta('en'),
        'lead' => $meta('lead'),
        'image' => $meta('image') ?: 'pearl-spots.webp',
        'mirror' => $meta('mirror') === '1',
        'summary' => (string) $post->post_excerpt,
        'categories' => is_array($terms) ? wp_list_pluck($terms, 'slug') : [],
        'category_labels' => is_array($terms) ? wp_list_pluck($terms, 'name') : [],
        'for' => hakuji_lines($meta('for')),
        'flow' => hakuji_lines($meta('flow')),
        'prices' => hakuji_prices($id),
        'courses' => hakuji_courses($id),
        'price_note' => $meta('price_note'),
        'sessions' => $meta('sessions'),
        'sessions_short' => $meta('sessions_short'),
        'duration' => $meta('duration'),
        'anesthesia' => $meta('anesthesia'),
        'downtime' => $meta('downtime'),
        'downtime_short' => $meta('downtime_short'),
        'risks' => hakuji_lines($meta('risks')),
        'risks_short' => $meta('risks_short'),
        'contraindications' => hakuji_lines($meta('contraindications')),
        'aftercare' => hakuji_lines($meta('aftercare')),
        'unapproved' => $meta('unapproved') === '1',
        'unapproved_info' => $unapprovedInfo,
        'reviewed' => $meta('reviewed'),
    ];
}

/** 下層ページの見出し */
function hakuji_page_head(string $title, string $en = '', string $lead = ''): void
{
    get_template_part('template-parts/page-head', null, ['title' => $title, 'en' => $en, 'lead' => $lead]);
}

/** 予約ページの URL（固定ページ contact がなければトップ） */
function hakuji_contact_url(): string
{
    $page = get_page_by_path('contact');
    return $page ? (string) get_permalink($page) : home_url('/');
}

/**
 * グローバルナビ。メニュー「メインメニュー」が設定されていればそれを、なければ既定の項目を使う。
 *
 * @return list<array{label: string, en: string, url: string, current: bool}>
 */
function hakuji_nav_items(): array
{
    $locations = get_nav_menu_locations();
    if (!empty($locations['primary']) && ($items = wp_get_nav_menu_items($locations['primary']))) {
        return array_map(static fn ($item) => [
            'label' => $item->title,
            'en' => (string) $item->attr_title,
            'url' => $item->url,
            'current' => untrailingslashit($item->url) === untrailingslashit((string) home_url(add_query_arg([]))),
        ], $items);
    }
    $items = [
        ['label' => '施術一覧', 'en' => 'Treatments', 'url' => (string) get_post_type_archive_link('treatment'), 'current' => is_post_type_archive('treatment') || is_singular('treatment') || is_tax('concern')],
    ];
    if ((int) get_option('page_for_posts') > 0) {
        $items[] = ['label' => 'お知らせ', 'en' => 'News', 'url' => (string) get_permalink((int) get_option('page_for_posts')), 'current' => is_home() || is_singular('post')];
    }
    $items[] = ['label' => '診療時間・アクセス', 'en' => 'Hours & Access', 'url' => home_url('/#access'), 'current' => false];
    $items[] = ['label' => 'ご予約・お問い合わせ', 'en' => 'Reservation', 'url' => hakuji_contact_url(), 'current' => is_page('contact')];
    return $items;
}
