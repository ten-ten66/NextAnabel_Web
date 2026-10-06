<?php
/**
 * 表現チェックの回帰テスト
 *   php tests/lint.test.php
 * NG 例（tests/fixtures/lint/sample/ng.html）は違反を検出し、OK 例は違反 0 件であること。
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($root . '/tools/lint-compliance.php') . ' '
    . escapeshellarg($root . '/tests/fixtures/lint') . ' 2>&1';
exec($cmd, $output, $code);
$log = implode("\n", $output);

$expected = [
    '最上級・No.1表現',
    '費用の強調（キャンペーン・割引・月額）',
    'リスクがないかのような表現',
    '総額表示（税込）がない価格',
    '折りたたみ（details）の中にあります',
    'クーリング・オフの案内',
];
$failures = [];
if ($code !== 1) {
    $failures[] = "終了コードが 1 ではありません（{$code}）";
}
foreach ($expected as $needle) {
    if (!str_contains($log, $needle)) {
        $failures[] = "検出されるべき違反が出ていません: {$needle}";
    }
}
if (str_contains($log, 'ok.html')) {
    $failures[] = 'OK 例で違反が検出されました';
}
if (str_contains($log, '検査対象外')) {
    $failures[] = 'data-lint-ignore の要素が検査されています';
}

if ($failures) {
    fwrite(STDERR, "lint.test: 失敗\n  - " . implode("\n  - ", $failures) . "\n\n{$log}\n");
    exit(1);
}
echo "lint.test: OK\n";
