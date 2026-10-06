<?php
/**
 * 差し替え前提の写真枠（撮影素材が入るまでの仮画像とキャプション）
 * @var string $src     assets/ からの画像パス
 * @var int    $width
 * @var int    $height
 * @var string $caption 例: 院内写真（撮影素材に差し替え）
 * @var string|null $ratio CSS の aspect-ratio（省略時は画像の比率）
 * @var string|null $class 追加のクラス
 */
$ratio = !empty($ratio) ? $ratio : $width . ' / ' . $height;
?>
<figure class="c-photo<?= !empty($class) ? ' ' . e($class) : '' ?>">
  <div class="c-photo__frame" style="aspect-ratio: <?= e($ratio) ?>">
    <img src="<?= e(asset($src)) ?>" width="<?= e($width) ?>" height="<?= e($height) ?>" alt="" loading="lazy" decoding="async">
    <span class="c-photo__mark" aria-hidden="true">Photo</span>
  </div>
  <figcaption class="c-photo__caption"><?= e($caption) ?></figcaption>
</figure>
