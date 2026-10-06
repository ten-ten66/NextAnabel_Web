<?php
/**
 * 差し替え前提の画像枠（撮影素材が入るまでの仮画像＋キャプション）
 *
 * @var string $src      assets/ からの相対パス
 * @var int $width
 * @var int $height
 * @var string $caption  例: 医師写真（撮影素材に差し替え）
 * @var string $shape    portrait（4:5）| landscape（3:2）
 * @var ?string $class   追加のクラス
 */
$shape ??= 'landscape';
$class ??= '';
?>
<figure class="c-frame c-frame--<?= e($shape) ?> <?= e($class) ?>">
  <div class="c-frame__media">
    <img src="<?= e(asset($src)) ?>" width="<?= (int) $width ?>" height="<?= (int) $height ?>" alt="" loading="lazy" decoding="async" data-parallax>
    <span class="c-frame__marks" aria-hidden="true"></span>
  </div>
  <figcaption class="c-frame__caption"><?= e($caption) ?></figcaption>
</figure>
