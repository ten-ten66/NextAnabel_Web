<?php
/**
 * サイトの初期化。各ページの先頭で require する。
 */

declare(strict_types=1);

define('SITE_DIR', __DIR__);
require dirname(__DIR__, 2) . '/core/bootstrap.php';
site_config(require __DIR__ . '/data/site.php');

// ---------------------------------------------------------------------------
// このサイトだけで使う小さなヘルパー
// ---------------------------------------------------------------------------

/**
 * ナビゲーションのリンク先
 * トップページ内のセクション（index#flow など）は、トップにいるときは #flow だけにして再読み込みを避ける。
 */
function nav_href(string $href, string $currentId): string
{
    if ($currentId === 'index' && str_starts_with($href, 'index#')) {
        return substr($href, 5);
    }
    return url($href);
}

/** 特定継続的役務に当たるコースか（期間1か月超かつ5万円超） */
function is_qualifying_course(array $course): bool
{
    return (int) $course['months'] > 1 && (int) $course['amount'] > 50000;
}

/**
 * 香りのこよみから、指定日に当たる組を返す（年をまたぐ組にも対応）
 *
 * @param list<array{sekki: string, start: string, scent: string, note: string}> $seasons
 * @return array{sekki: string, start: string, scent: string, note: string}
 */
function current_season(array $seasons, DateTimeImmutable $date): array
{
    $today = $date->format('m-d');
    $match = $seasons[count($seasons) - 1];
    foreach ($seasons as $season) {
        if ($season['start'] <= $today) {
            $match = $season;
        }
    }
    return $match;
}

/** 電話番号の tel: リンク */
function tel_href(): string
{
    return 'tel:' . str_replace('-', '', (string) site('tel'));
}

/** 60 → 60分 */
function minutes(int $value): string
{
    return $value . '分';
}
