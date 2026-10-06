<?php
/**
 * 白磁スキンクリニック専用のテンプレート補助関数
 * 共通の処理は core/helpers.php にあり、ここにはこのサイトの表示に固有のものだけを置く。
 */

declare(strict_types=1);

const WEEKDAYS_JA = ['日', '月', '火', '水', '木', '金', '土'];

/**
 * data/<name>.php を1回だけ読み込む
 *
 * @return array<mixed>
 */
function site_data(string $name): array
{
    static $cache = [];
    if (!isset($cache[$name])) {
        $data = require SITE_DIR . '/data/' . $name . '.php';
        $cache[$name] = is_array($data) ? $data : [];
    }
    return $cache[$name];
}

/** @return list<array<string, mixed>> */
function treatments(): array
{
    return site_data('treatments');
}

/** @return array<string, mixed>|null */
function find_treatment(string $slug): ?array
{
    foreach (treatments() as $treatment) {
        if ($treatment['slug'] === $slug) {
            return $treatment;
        }
    }
    return null;
}

/** @return array<string, array<string, string>> id をキーにしたカテゴリ */
function categories(): array
{
    $map = [];
    foreach (site_data('categories') as $category) {
        $map[$category['id']] = $category;
    }
    return $map;
}

function category_label(string $id): string
{
    return categories()[$id]['label'] ?? '';
}

/**
 * 料金（税込）の表示
 * 文言は tax_in() と同じ「22,000円（税込）」のまま、数字だけを別の書体で組めるよう要素を分ける。
 */
function price_html(int $amount): string
{
    [$number, $rest] = explode('円', tax_in($amount), 2);
    return '<span class="c-price"><span class="c-price__num">' . e($number) . '</span>'
        . '<span class="c-price__unit">円</span><span class="c-price__tax">' . e($rest) . '</span></span>';
}

/** 税込の文脈（data-price-context="tax-included" の表）で使う金額。見出しに「（税込）」を書くこと */
function yen_html(int $amount): string
{
    [$number] = explode('円', yen($amount), 2);
    return '<span class="c-price"><span class="c-price__num">' . e($number) . '</span><span class="c-price__unit">円</span></span>';
}

/** 特定継続的役務（期間1か月超かつ5万円超）に当たるコースか */
function is_qualifying_course(int $months, int $amount): bool
{
    return $months > 1 && $amount > 50000;
}

/** 2026-09-30 → 2026年9月30日（$withWeekday で曜日を添える） */
function ja_date(string $ymd, bool $withWeekday = false): string
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $ymd);
    if ($date === false) {
        throw new InvalidArgumentException("日付の形式が不正です: {$ymd}");
    }
    $text = $date->format('Y年n月j日');
    return $withWeekday ? $text . '（' . WEEKDAYS_JA[(int) $date->format('w')] . '）' : $text;
}

/** 日本語表記の <time> 要素 */
function ja_time_tag(string $ymd, bool $withWeekday = false): string
{
    return '<time datetime="' . e($ymd) . '">' . e(ja_date($ymd, $withWeekday)) . '</time>';
}

/** "19:00" から分を引いた時刻（最終受付の計算） */
function time_minus(string $hhmm, int $minutes): string
{
    [$hour, $minute] = array_map('intval', explode(':', $hhmm));
    $total = $hour * 60 + $minute - $minutes;
    return sprintf('%02d:%02d', intdiv($total, 60), $total % 60);
}

/**
 * 曜日ごとの診療時間（月曜始まり＋祝日の行）
 *
 * @return list<array{day: string, label: string, short: string, open: ?string, close: ?string, last: ?string}>
 */
function schedule_rows(): array
{
    $schedule = (array) site('schedule', []);
    $lastEntry = (int) site('last_entry_minutes', 0);
    $rows = [];
    foreach ([1, 2, 3, 4, 5, 6, 0] as $day) {
        $slot = $schedule[$day] ?? null;
        $rows[] = [
            'day' => (string) $day,
            'label' => WEEKDAYS_JA[$day] . '曜日',
            'short' => WEEKDAYS_JA[$day],
            'open' => $slot['open'] ?? null,
            'close' => $slot['close'] ?? null,
            'last' => $slot ? time_minus((string) $slot['close'], $lastEntry) : null,
        ];
    }
    $rows[] = ['day' => 'holiday', 'label' => '祝日', 'short' => '祝', 'open' => null, 'close' => null, 'last' => null];
    return $rows;
}

/** 診療時間の要約（例: 10:00〜19:00（日曜は17:00まで）） */
function hours_summary(): string
{
    $schedule = (array) site('schedule', []);
    $base = $schedule[1] ?? null; // 月曜を基準にし、異なる曜日だけを注記する
    if (!is_array($base)) {
        return '';
    }
    $notes = [];
    foreach ([1, 2, 3, 4, 5, 6, 0] as $day) {
        $slot = $schedule[$day] ?? null;
        if (is_array($slot) && ($slot['open'] !== $base['open'] || $slot['close'] !== $base['close'])) {
            $notes[] = WEEKDAYS_JA[$day] . '曜は' . ($slot['open'] !== $base['open'] ? $slot['open'] . '〜' : '') . $slot['close'] . 'まで';
        }
    }
    return $base['open'] . '〜' . $base['close'] . ($notes ? '（' . implode('、', $notes) . '）' : '');
}

/** 受付状況の計算に使う値（JavaScript に data 属性で渡す） */
function reception_json(): string
{
    return (string) json_encode([
        'schedule' => site('schedule', []),
        'lastEntry' => (int) site('last_entry_minutes', 0),
        'closed' => site('closed_dates', []),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
}

function tel_href(): string
{
    return 'tel:' . preg_replace('/\D/', '', (string) site('tel'));
}

function full_address(): string
{
    return (string) site('address.region') . site('address.locality') . site('address.street');
}

/** 地図アプリでの検索URL（建物名を除いた住所で検索する） */
function map_url(): string
{
    $street = explode(' ', (string) site('address.street'))[0];
    return 'https://www.google.com/maps/search/?api=1&query='
        . rawurlencode((string) site('address.region') . site('address.locality') . $street);
}

/** 「見出し：本文」を [見出し, 本文] に分ける（見出しがなければ空文字） */
function split_heading(string $text): array
{
    $parts = explode('：', $text, 2);
    return count($parts) === 2 ? $parts : ['', $text];
}

/** 監修者の表記（例: 院長 汐見 透子（皮膚科専門医）） */
function reviewer_label(): string
{
    $doctor = site_data('doctor');
    return $doctor['role'] . ' ' . $doctor['name'] . '（' . $doctor['specialty'] . '）';
}

/** 線画アイコン（装飾。読み上げ対象外） */
function icon(string $name): string
{
    $paths = [
        'arrow' => '<path d="M4 12h15M13.5 6.5 19 12l-5.5 5.5"/>',
        'tel' => '<path d="M8.6 3.8 6.4 4.4c-1.3.4-2.1 1.7-1.8 3 1.6 6.1 6 10.5 12 12 1.3.3 2.6-.5 3-1.8l.6-2.2-4-2.3-2 1.6a9.9 9.9 0 0 1-5-5l1.6-2z"/>',
        'external' => '<path d="M14 4h6v6M20 4l-8.5 8.5M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/>',
        'close' => '<path d="M6 6l12 12M18 6 6 18"/>',
        'calendar' => '<path d="M4.5 7.5h15v12h-15zM4.5 11h15M9 4.5v4M15 4.5v4"/>',
        'pause' => '<path d="M9 6v12M15 6v12"/>',
        'play' => '<path d="M8 5.5v13l10-6.5z"/>',
    ];
    return '<svg class="c-icon c-icon--' . e($name) . '" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">'
        . ($paths[$name] ?? '') . '</svg>';
}

/**
 * 施術詳細ページの構造化データ（MedicalWebPage）
 *
 * @param array<string, mixed> $t
 * @return array<string, mixed>
 */
function medical_webpage_ld(array $t, string $description): array
{
    return [
        '@type' => 'MedicalWebPage',
        '@id' => absolute_url() . '#webpage',
        'url' => absolute_url(),
        'name' => $t['name'],
        'description' => $description,
        'inLanguage' => 'ja',
        'lastReviewed' => $t['reviewed'],
        'reviewedBy' => reviewer_ld((array) site('reviewer')),
        'publisher' => ['@id' => absolute_url('') . '#business'],
        'about' => [
            '@type' => 'MedicalProcedure',
            'name' => $t['name'],
            'alternateName' => $t['en'],
            'description' => $t['summary'],
            'procedureType' => 'https://schema.org/' . $t['procedure_type'],
            'bodyLocation' => $t['body_location'],
            'followup' => implode(' ', $t['aftercare']),
        ],
    ];
}
