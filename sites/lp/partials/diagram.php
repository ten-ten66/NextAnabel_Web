<?php
/**
 * 「医療脱毛のしくみ」の図（皮膚の断面と毛周期）
 * 止まった状態で完成している。JavaScript が画面内に入ったときに is-play を付けると、
 * レーザーの光が毛根まで届き、成長期の毛根だけが熱を持つ演出を1回だけ再生する。
 */
$follicles = [
    // x, 毛の上端, 毛根の位置, 毛根の大きさ, 毛乳頭の位置, 名前, 成長期か
    [70, 22, 230, 11, 240, '成長期', true],
    [180, 40, 180, 7.5, 214, '退行期', false],
    [290, 56, 138, 6.5, 178, '休止期', false],
];
?>
<svg class="p-diagram js-diagram" viewBox="0 0 360 300" width="360" height="300" role="img" aria-labelledby="diagram-title diagram-desc">
  <title id="diagram-title">毛周期とレーザーの作用</title>
  <desc id="diagram-desc">レーザーの光は3本の毛すべてに当たりますが、熱が毛根まで伝わりやすいのは、毛根とつながっている成長期の毛だけであることを示した図です。</desc>
  <defs>
    <linearGradient id="diagram-beam" x1="0" y1="0" x2="0" y2="1">
      <stop class="p-diagram__stop-strong" offset="0"/>
      <stop class="p-diagram__stop-fade" offset="1"/>
    </linearGradient>
    <radialGradient id="diagram-heat">
      <stop class="p-diagram__stop-heat" offset="0"/>
      <stop class="p-diagram__stop-heat-mid" offset=".55"/>
      <stop class="p-diagram__stop-none" offset="1"/>
    </radialGradient>
  </defs>
  <rect class="p-diagram__dermis" x="0" y="92" width="360" height="208" rx="10"/>
  <rect class="p-diagram__epidermis" x="0" y="76" width="360" height="18"/>
  <path class="p-diagram__surface" d="M0 76H360"/>
<?php foreach ($follicles as [$x, $top, $bulb, $r, $papilla, $name, $growing]): ?>
  <g class="p-diagram__follicle<?= $growing ? ' is-growing' : '' ?>">
    <rect class="p-diagram__beam" x="<?= $x - 17 ?>" y="0" width="34" height="<?= $bulb + 4 ?>"/>
    <path class="p-diagram__sheath" d="M<?= $x - 9 ?> 80V<?= $bulb - 4 ?>Q<?= $x ?> <?= $bulb + $r + 14 ?> <?= $x + 9 ?> <?= $bulb - 4 ?>V80"/>
    <ellipse class="p-diagram__papilla" cx="<?= $x ?>" cy="<?= $papilla ?>" rx="<?= $growing ? 7 : 5 ?>" ry="<?= $growing ? 4.5 : 3.5 ?>"/>
<?php if ($growing): ?>
    <circle class="p-diagram__heat" cx="<?= $x ?>" cy="<?= $bulb ?>" r="30"/>
<?php endif; ?>
    <path class="p-diagram__hair" d="M<?= $x ?> <?= $top ?>V<?= $bulb - $r + 2 ?>"/>
    <ellipse class="p-diagram__bulb" cx="<?= $x ?>" cy="<?= $bulb ?>" rx="<?= $r ?>" ry="<?= $r - 1.5 ?>"/>
    <rect class="p-diagram__pulse" x="<?= $x - 17 ?>" y="0" width="34" height="30" style="--travel: <?= $bulb - 24 ?>px"/>
    <text class="p-diagram__label<?= $growing ? ' is-growing' : '' ?>" x="<?= $x ?>" y="283"><?= e($name) ?></text>
  </g>
<?php endforeach; ?>
  <text class="p-diagram__note" x="104" y="244">熱が毛根に伝わる</text>
  <path class="p-diagram__leader" d="M100 240H84"/>
  <text class="p-diagram__laser" x="8" y="18">レーザー</text>
</svg>
