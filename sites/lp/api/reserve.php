<?php
/**
 * カウンセリング予約の受付 API（サーバー版専用。静的ビルドには含めない）
 *
 *   POST api/reserve.php  Content-Type: application/json
 *   {"_token": "...", "name": "...", "tel": "...", "email": "...", "parts": ["face"],
 *    "date1": "2026-10-20", "time1": "morning", "message": "...", "consent": "1", "website": ""}
 *
 * 応答（JSON）
 *   200 {"ok": true}
 *   403 {"ok": false, "message": "..."}               別オリジンからの送信・CSRF トークン不一致
 *   405 {"ok": false, "message": "..."}               POST 以外
 *   422 {"ok": false, "errors": {"name": "..."}, ...}  入力エラー（項目名 => メッセージ）
 *   429 {"ok": false, "message": "..."}               短時間の連続送信
 *   500 {"ok": false, "message": "..."}               メール送信の失敗
 */

declare(strict_types=1);

require dirname(__DIR__) . '/_init.php';

use Core\Form\Csrf;
use Core\Form\Guard;
use Core\Form\Http;
use Core\Form\MailerFactory;
use Core\Form\RateLimit;
use Core\Form\Session;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Allow: POST');
    Http::json(405, ['ok' => false, 'message' => 'このURLは予約フォームの送信専用です。']);
}

if (!Http::sameOrigin()) {
    Http::json(403, ['ok' => false, 'message' => '送信元を確認できませんでした。ページを開き直してから、もう一度お試しください。']);
}

Session::start();
$input = Http::jsonBody();

if (!Csrf::verify($input['_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null))) {
    Http::json(403, ['ok' => false, 'message' => '一定時間が経過したため、送信内容を確認できませんでした。お手数ですが、ページを再読み込みしてからもう一度お試しください。']);
}

// 機械的な投稿は、判定基準を学習させないよう成功したように見せて何もしない
if (Guard::isBot($input, LP_FORM_ID)) {
    Http::json(200, ['ok' => true]);
}

[$values, $errors] = lp_validate_reservation($input);
if ($errors) {
    Http::json(422, ['ok' => false, 'errors' => $errors, 'message' => '入力内容に誤りがあります。各項目のメッセージをご確認ください。']);
}

$limit = new RateLimit(LP_FORM_ID, 30);
if ($limit->tooSoon()) {
    Http::json(429, ['ok' => false, 'message' => '短時間に続けて送信されました。しばらく時間をおいてから、もう一度お試しください。']);
}

$mailer = MailerFactory::create();
[$adminMail, $replyMail] = lp_reservation_mails($values);
if (!$mailer->send($adminMail)) {
    Http::json(500, ['ok' => false, 'message' => '送信できませんでした。時間をおいてもう一度お試しいただくか、お電話でご予約ください。']);
}
$limit->hit();
// 自動返信の失敗は予約の受付に影響させない（院内には届いているため、こちらから連絡できる）
$mailer->send($replyMail);

Http::json(200, ['ok' => true]);
