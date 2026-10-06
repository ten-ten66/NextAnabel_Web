<?php
require __DIR__ . '/_init.php';

$page = [
    'id' => 'index',
    'description' => '雛形のトップページです。',
    'jsonld' => [business_ld()],
];
partial('head', compact('page'));
partial('header', compact('page'));
?>
<main id="main">
  <h1><?= e(site('name')) ?></h1>
  <p>カウンセリング料：<?= e(tax_in(3300)) ?></p>
</main>
<?php partial('footer'); ?>
