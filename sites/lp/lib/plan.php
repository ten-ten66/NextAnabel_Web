<?php
/**
 * 料金データ・本文データ・医院情報の参照と整形（index.php と api/reserve.php で共通）
 */

declare(strict_types=1);

/** 期間1か月超かつ5万円超のコース契約は特定継続的役務（特定商取引法）に当たる */
const LP_COOLING_OFF_AMOUNT = 50000;
const LP_COOLING_OFF_MONTHS = 1;

const LP_WEEKDAYS = ['日', '月', '火', '水', '木', '金', '土'];
const LP_SCHEMA_DAYS = ['Sunday' => 0, 'Monday' => 1, 'Tuesday' => 2, 'Wednesday' => 3, 'Thursday' => 4, 'Friday' => 5, 'Saturday' => 6];

/**
 * 医療レーザー脱毛の料金・回数データ。コーポレートサイトと同じファイルを読む（単一の情報源）。
 *
 * @return array{course: array<string, mixed>, parts: list<array<string, mixed>>, sets: list<array<string, mixed>>, included: list<string>, extra: list<array<string, mixed>>}
 */
function lp_plan(): array
{
    static $plan = null;
    return $plan ??= require dirname(SITE_DIR) . '/skin-clinic/data/hair_removal.php';
}

/** @return array<string, mixed> LP の本文データ */
function lp_content(): array
{
    static $content = null;
    return $content ??= require SITE_DIR . '/data/lp.php';
}

function lp_is_qualifying_course(int $amount, int $months): bool
{
    return $months > LP_COOLING_OFF_MONTHS && $amount > LP_COOLING_OFF_AMOUNT;
}

/** @return array<string, string> 部位 id => 部位名 */
function lp_part_options(): array
{
    $options = [];
    foreach (lp_plan()['parts'] as $part) {
        $options[(string) $part['id']] = (string) $part['label'];
    }
    return $options;
}

/**
 * 「顔（額・頬・鼻下・あご）」→ ['顔', '額・頬・鼻下・あご']
 *
 * @return array{0: string, 1: string}
 */
function lp_split_label(string $label): array
{
    if (preg_match('/\A(.+?)（(.+)）\z/u', $label, $m)) {
        return [$m[1], $m[2]];
    }
    return [$label, ''];
}

/**
 * 選んだ部位の料金を試算する（assets/js/main.js のシミュレーターと同じ計算）。
 * セットに含まれる部位をすべて選んだときは「セット料金＋残りの部位」を候補にし、
 * 候補のうち総額がもっとも低い組み合わせを採用する。
 *
 * @param list<string> $ids
 * @return array{course: int, once: int, set: ?array<string, mixed>, count: int}
 */
function lp_estimate(array $ids): array
{
    $plan = lp_plan();
    $parts = [];
    foreach ($plan['parts'] as $part) {
        $parts[(string) $part['id']] = $part;
    }
    $ids = array_values(array_intersect(array_keys($parts), $ids));
    $sum = static fn (array $list, string $key): int => array_sum(array_map(static fn ($id) => (int) $parts[$id][$key], $list));

    $best = ['course' => $sum($ids, 'course'), 'once' => $sum($ids, 'once'), 'set' => null];
    foreach ($plan['sets'] as $set) {
        if (array_diff($set['includes'], $ids)) {
            continue;
        }
        $rest = array_values(array_diff($ids, $set['includes']));
        $course = (int) $set['course'] + $sum($rest, 'course');
        if ($course < $best['course']) {
            $best = ['course' => $course, 'once' => (int) $set['once'] + $sum($rest, 'once'), 'set' => $set];
        }
    }
    return $best + ['count' => count($ids)];
}

/** 麻酔クリームの料金の文言（例:「1部位 3,300円（税込）」）。本文の {anesthesia} を置き換える */
function lp_anesthesia_text(): string
{
    foreach (lp_plan()['extra'] as $item) {
        if (str_starts_with((string) $item['label'], '麻酔クリーム') && (int) $item['amount'] > 0) {
            [, $unit] = lp_split_label((string) $item['label']);
            return trim($unit . ' ' . tax_in((int) $item['amount']));
        }
    }
    return '料金はカウンセリングでご案内します';
}

/** 本文データの文字列を、{anesthesia} を置き換えてからエスケープする */
function lp_text(string $text): string
{
    return e(strtr($text, ['{anesthesia}' => lp_anesthesia_text()]));
}

/** 「院長・皮膚科専門医」→「院長 汐見 透子（皮膚科専門医）」 */
function lp_reviewer_line(): string
{
    $name = (string) site('reviewer.name', '');
    $title = (string) site('reviewer.title', '');
    if (str_contains($title, '・')) {
        [$role, $qualification] = explode('・', $title, 2);
        return "{$role} {$name}（{$qualification}）";
    }
    return trim("{$title} {$name}");
}

/** コーポレートサイト（白磁スキンクリニック）のURL */
function lp_corporate_url(): string
{
    return SITE_ORIGIN . '/' . trim((string) site('corporate_base', 'skin-clinic'), '/') . '/';
}

/**
 * 診療時間の表示用の行
 *
 * @return list<array{days: string, time: string}>
 */
function lp_hours_rows(): array
{
    $rows = [];
    foreach ((array) site('hours', []) as $row) {
        $days = array_map(static fn ($day) => LP_WEEKDAYS[LP_SCHEMA_DAYS[$day] ?? 0], (array) $row['days']);
        $rows[] = ['days' => implode('・', $days), 'time' => $row['opens'] . '〜' . $row['closes']];
    }
    return $rows;
}

/** tel: リンク用の番号 */
function lp_tel_href(): string
{
    return 'tel:' . preg_replace('/[^0-9+]/', '', (string) site('tel'));
}

/**
 * アイコン（partials/icons.php のシンボルを参照する inline SVG）。装飾として読み上げから外す。
 */
function lp_icon(string $name, string $class = ''): string
{
    return '<svg class="c-icon' . ($class !== '' ? ' ' . e($class) : '') . '" aria-hidden="true" width="24" height="24"><use href="#i-' . e($name) . '"></use></svg>';
}
