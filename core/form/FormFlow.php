<?php

declare(strict_types=1);

namespace Core\Form;

/**
 * 入力 → 確認 → 完了 の3段階フォーム
 *
 *   GET                 入力画面
 *   POST _action=confirm 検証。エラーなら入力画面、通れば確認画面
 *   POST _action=back    入力画面へ戻る（値はセッションから復元）
 *   POST _action=send    送信。成功したら 303 で ?step=complete へリダイレクト（PRG）
 *   GET  ?step=complete  完了画面（送信直後のみ表示できる）
 *
 * 送信する値は確認画面の hidden ではなくセッションから取り出すため、確認後の改ざんは効かない。
 * 送信後は CSRF トークンを破棄するので、ブラウザの再送信やダブルクリックでは二重送信されない。
 *
 * 使い方（出力より前に呼ぶこと）
 *   $form = (new FormFlow('contact', $fields, fn (array $v): bool => send_mails($v)))->handle();
 *   // $form['step'] に応じて入力・確認・完了画面を出し分ける
 */
final class FormFlow
{
    private const DATA = '_form_data';
    private const SENT = '_form_sent';

    /** @var callable(array<string, mixed>): bool */
    private $onSend;

    /**
     * @param array<string, array<string, mixed>> $fields Validator のフィールド定義
     * @param callable(array<string, mixed>): bool $onSend 送信処理。成功で true を返す
     */
    public function __construct(
        private readonly string $formId,
        private readonly array $fields,
        callable $onSend,
        private readonly int $minIntervalSec = 30,
    ) {
        $this->onSend = $onSend;
    }

    /**
     * @return array{step: string, values: array<string, mixed>, errors: array<string, string>, notice: ?string, token: string, fields: array<string, array<string, mixed>>}
     */
    public function handle(): array
    {
        Session::start();
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            return $this->post($_POST);
        }
        return $this->get($_GET);
    }

    /** @param array<string, mixed> $query */
    private function get(array $query): array
    {
        if (($query['step'] ?? '') === 'complete') {
            if (!empty($_SESSION[self::SENT][$this->formId])) {
                unset($_SESSION[self::SENT][$this->formId]);
                return $this->state('complete');
            }
            $this->redirect();
        }
        Guard::stamp($this->formId);
        return $this->state('input', $this->stored());
    }

    /** @param array<string, mixed> $input */
    private function post(array $input): array
    {
        if (!Csrf::verify($input['_token'] ?? null)) {
            http_response_code(400);
            Guard::stamp($this->formId);
            return $this->state(
                'input',
                $this->stored(),
                [],
                '一定時間が経過したため、送信内容を確認できませんでした。お手数ですが、もう一度ご入力ください。'
            );
        }
        return match ((string) ($input['_action'] ?? 'confirm')) {
            'back' => $this->back(),
            'send' => $this->send(),
            default => $this->confirm($input),
        };
    }

    /** @param array<string, mixed> $input */
    private function confirm(array $input): array
    {
        if (Guard::isBot($input, $this->formId)) {
            $this->markSent();
            $this->redirect('complete');
        }
        [$values, $errors] = (new Validator($this->fields))->validate($input);
        if ($errors) {
            http_response_code(422);
            return $this->state('input', $values, $errors, '入力内容に誤りがあります。各項目のメッセージをご確認ください。');
        }
        $_SESSION[self::DATA][$this->formId] = $values;
        Session::regenerate();
        return $this->state('confirm', $values);
    }

    private function back(): array
    {
        Guard::stamp($this->formId);
        return $this->state('input', $this->stored());
    }

    private function send(): array
    {
        $values = $this->stored();
        if (!$values) {
            $this->redirect();
        }
        $limit = new RateLimit($this->formId, $this->minIntervalSec);
        if ($limit->tooSoon()) {
            http_response_code(429);
            return $this->state('confirm', $values, [], '短時間に続けて送信されました。しばらく時間をおいてから送信してください。');
        }
        if (!($this->onSend)($values)) {
            http_response_code(500);
            return $this->state(
                'confirm',
                $values,
                [],
                '送信できませんでした。時間をおいて再度お試しいただくか、お電話でお問い合わせください。'
            );
        }
        $limit->hit();
        unset($_SESSION[self::DATA][$this->formId]);
        Csrf::rotate();
        $this->markSent();
        $this->redirect('complete');
    }

    /** @return array<string, mixed> */
    private function stored(): array
    {
        $values = $_SESSION[self::DATA][$this->formId] ?? [];
        return is_array($values) ? $values : [];
    }

    private function markSent(): void
    {
        $_SESSION[self::SENT][$this->formId] = true;
    }

    private function redirect(?string $step = null): never
    {
        $self = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        header('Location: ' . $self . ($step !== null ? '?step=' . rawurlencode($step) : ''), true, 303);
        exit;
    }

    /**
     * @param array<string, mixed> $values
     * @param array<string, string> $errors
     */
    private function state(string $step, array $values = [], array $errors = [], ?string $notice = null): array
    {
        return [
            'step' => $step,
            'values' => $values,
            'errors' => $errors,
            'notice' => $notice,
            'token' => Csrf::token(),
            'fields' => $this->fields,
        ];
    }
}
