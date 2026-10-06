<?php
/**
 * アクセスマップ（位置関係を示すイラスト。地図の埋め込みは使わない）
 *
 * @var array{prefix?: string} $args title / desc の id の接頭辞（同じページに複数置く場合に変える）
 */
$prefix = $args['prefix'] ?? 'map';
?>
<svg class="c-map" viewBox="0 0 640 440" width="640" height="440" role="img" aria-labelledby="<?php echo esc_attr($prefix); ?>-title <?php echo esc_attr($prefix); ?>-desc">
  <title id="<?php echo esc_attr($prefix); ?>-title"><?php echo esc_html(hakuji_clinic('name')); ?>までの道順（イラスト）</title>
  <desc id="<?php echo esc_attr($prefix); ?>-desc">表参道駅A4出口を出て青山通りを外苑前方面へ進み、1本目の角を右に曲がった通り沿いにクリニックがあります。外苑前駅1a出口からは、青山通りを表参道方面へ進み、同じ角を左に曲がります。</desc>
  <rect class="c-map__ground" width="640" height="440"/>
  <g class="c-map__streets">
    <path class="c-map__street-edge" d="M0 425 640 215M0 220 640 10M0 120 366 0M26 6 170 443M411 -5 557 442M553 -9 703 447M360 212 432 440"/>
    <path class="c-map__street" d="M0 425 640 215M0 220 640 10M0 120 366 0M26 6 170 443M411 -5 557 442M553 -9 703 447M360 212 432 440"/>
  </g>
  <g class="c-map__roads">
    <path class="c-map__road-edge" d="M-10 333 650 117M90 0 330 440"/>
    <path class="c-map__road" d="M-10 333 650 117M90 0 330 440"/>
  </g>
  <rect class="c-map__building" x="396" y="284" width="34" height="26" transform="rotate(-18 413 297)"/>
  <path class="c-map__route c-map__route--sub" d="M566 170 372 234"/>
  <path class="c-map__route" d="M250 276 368 238 386 296"/>
  <g class="c-map__station">
    <circle cx="229" cy="255" r="11"/>
    <circle cx="560" cy="146" r="11"/>
  </g>
  <g class="c-map__exit">
    <rect x="243" y="270" width="14" height="14" rx="2"/>
    <rect x="559" y="163" width="14" height="14" rx="2"/>
  </g>
  <g class="c-map__pin">
    <circle cx="413" cy="297" r="9"/>
    <circle class="c-map__pin-core" cx="413" cy="297" r="3.2"/>
  </g>
  <g class="c-map__labels">
    <text class="c-map__road-label" transform="translate(34 324) rotate(-18.1)">青山通り</text>
    <text class="c-map__road-label" transform="translate(118 46) rotate(61.4)">表参道</text>
    <text class="c-map__station-label" x="150" y="236">表参道駅</text>
    <text class="c-map__exit-label" x="230" y="306">A4出口</text>
    <text class="c-map__station-label" x="510" y="124">外苑前駅</text>
    <text class="c-map__exit-label" x="574" y="198">1a出口</text>
    <text class="c-map__clinic-label" x="432" y="335">白磁スキンクリニック</text>
    <text class="c-map__clinic-sub" x="432" y="358">サンプルビル3階</text>
  </g>
  <g class="c-map__compass" transform="translate(600 46)">
    <circle r="18"/>
    <path d="M0 -12 5 4 0 1 -5 4z"/>
    <text y="-24">N</text>
  </g>
</svg>
