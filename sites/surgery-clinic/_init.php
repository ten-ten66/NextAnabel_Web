<?php
/**
 * サイトの初期化。各ページの先頭で require する。
 * このサイトだけで使う小さなヘルパーも、ここで定義する（接頭辞 sc_）。
 */

declare(strict_types=1);

define('SITE_DIR', __DIR__);
require dirname(__DIR__, 2) . '/core/bootstrap.php';
site_config(require __DIR__ . '/data/site.php');

/** 施術データ（data/treatments.php）を一度だけ読み込む */
function sc_treatments(): array
{
    static $list = null;
    return $list ??= require SITE_DIR . '/data/treatments.php';
}

function sc_treatment(string $slug): ?array
{
    foreach (sc_treatments() as $treatment) {
        if ($treatment['slug'] === $slug) {
            return $treatment;
        }
    }
    return null;
}

/** 03-0000-0001 → tel:0300000001 */
function sc_tel_href(): string
{
    return 'tel:' . preg_replace('/\D/', '', (string) site('tel'));
}

/** 「見出し：説明」を [見出し, 説明] に分ける（区切りが無ければ見出しは空） */
function sc_split_step(string $text): array
{
    $parts = explode('：', $text, 2);
    return count($parts) === 2 ? [$parts[0], $parts[1]] : ['', $text];
}

/** 代表価格「165,000円（税込）〜」 */
function sc_price_from(array $treatment): string
{
    $card = $treatment['card_price'] ?? null;
    $amount = $card['amount'] ?? min(array_column($treatment['prices'], 'amount'));
    return tax_in((int) $amount) . '〜';
}

/** 2桁の通し番号（01, 02 …） */
function sc_num(int $index): string
{
    return sprintf('%02d', $index);
}

/** 2026-09-30 → <time datetime="2026-09-30">2026年9月30日</time> */
function sc_date(string $ymd): string
{
    return time_tag($ymd, 'Y年n月j日');
}

/** 監修者の表記「院長 桐生 遼（形成外科専門医）」 */
function sc_reviewer_label(): string
{
    $reviewer = (array) site('reviewer');
    return sprintf('%s %s（%s）', $reviewer['role'] ?? '', $reviewer['name'] ?? '', $reviewer['license'] ?? '');
}
