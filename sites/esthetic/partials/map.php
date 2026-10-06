<?php
/**
 * 駅からの道のり（手描き風のイラスト地図）
 * 外部の地図は埋め込まない（Artifact の制約と表示速度のため）。正確な場所は地図アプリへのリンクで補う。
 */
?>
<svg class="p-map" viewBox="0 0 640 440" role="img" aria-labelledby="map-title map-desc">
  <title id="map-title">自由が丘駅から苔と麻までの道のり（イメージ図）</title>
  <desc id="map-desc">自由が丘駅の正面口を出て北へ進み、ひとつ目の大きな通りを右へ。2本目の路地を左に入った先の建物の2階が苔と麻です。徒歩約6分。</desc>
  <rect class="p-map__ground" width="640" height="440"/>
  <path class="p-map__green" d="M0 338c96-22 182 10 296-6s214-34 344-14v44c-122-18-214 6-334 18S96 368 0 384Z"/>
  <g class="p-map__roads">
    <path d="M-10 112c160-8 330-16 660-12"/>
    <path d="M-10 214c140 2 280-6 420-8s160 0 240-6"/>
    <path d="M300 280V-10"/>
    <path d="M470 280V-10"/>
    <path d="M300 212c60-40 140-96 236-136"/>
    <path d="M150 280c-6-60-2-180 8-290"/>
    <path d="M396 210c2-30 2-62-2-98"/>
    <path d="M-10 410c150 6 300-4 660 8"/>
    <path d="M560 300v150"/>
  </g>
  <g class="p-map__rail">
    <path class="p-map__rail-base" d="M-10 300h660"/>
    <path class="p-map__rail-ties" d="M-10 300h660"/>
    <path class="p-map__rail-base" d="M170 452 470 102"/>
    <path class="p-map__rail-ties" d="M170 452 470 102"/>
  </g>
  <path class="p-map__route js-map-route" d="M300 278v-66h126v-72"/>
  <rect class="p-map__station" x="234" y="282" width="132" height="40" rx="10"/>
  <text class="p-map__station-label" x="300" y="308" text-anchor="middle">自由が丘駅</text>
  <circle class="p-map__exit" cx="300" cy="280" r="7"/>
  <text class="p-map__label" x="288" y="266" text-anchor="end">正面口</text>
  <text class="p-map__label p-map__label--route" x="352" y="200" text-anchor="middle">徒歩6分</text>
  <text class="p-map__label p-map__label--soft" x="40" y="368">緑道</text>
  <text class="p-map__label p-map__label--soft p-map__label--small" x="18" y="290">東急東横線</text>
  <text class="p-map__label p-map__label--soft p-map__label--small" x="200" y="438" transform="rotate(-49.4 200 438)">東急大井町線</text>
  <g class="p-map__pin">
    <path class="p-map__pin-shape" d="M426 140c-14-16-22-30-22-42a22 22 0 0 1 44 0c0 12-8 26-22 42Z"/>
    <path class="p-map__pin-leaf" d="M426 110V96"/>
    <path class="p-map__pin-leaf" d="M426 101.5c-5.4.2-9-3.3-9.4-8.8 5.4-.2 9 3.3 9.4 8.8Z"/>
    <path class="p-map__pin-leaf" d="M426 99c.3-5 3.7-8.4 8.9-8.6-.2 5.2-3.6 8.5-8.9 8.6Z"/>
    <rect class="p-map__pin-tag" x="456" y="76" width="120" height="40" rx="20"/>
    <text class="p-map__pin-label" x="516" y="102" text-anchor="middle">苔と麻 2F</text>
  </g>
  <g class="p-map__compass">
    <circle cx="46" cy="50" r="22"/>
    <path d="M46 34l7 20h-14Z"/>
    <text x="46" y="88" text-anchor="middle">N</text>
  </g>
</svg>
