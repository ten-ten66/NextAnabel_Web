<?php

declare(strict_types=1);

namespace Core\Form;

/** JSON API 用の補助 */
final class Http
{
    /** @param array<string, mixed> $data */
    public static function json(int $status, array $data): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store');
        header('X-Content-Type-Options: nosniff');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        exit;
    }

    /** リクエスト元が同じオリジンか（Origin、無ければ Referer で判定） */
    public static function sameOrigin(): bool
    {
        $source = $_SERVER['HTTP_ORIGIN'] ?? ($_SERVER['HTTP_REFERER'] ?? '');
        $parts = parse_url((string) $source);
        if (!is_array($parts) || empty($parts['host'])) {
            return false;
        }
        $sourceHost = strtolower($parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : ''));
        $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
        return $host !== '' && hash_equals($host, $sourceHost);
    }

    /** @return array<string, mixed> */
    public static function jsonBody(int $maxBytes = 16384): array
    {
        $raw = file_get_contents('php://input', false, null, 0, $maxBytes + 1);
        if ($raw === false || strlen($raw) > $maxBytes) {
            return [];
        }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }
}
