<?php
/**
 * 料金シミュレーターの人体図（前面・背面）
 *
 * 部位の形は単純な図形の組み合わせで描く。どちら側で選べるかは hair_removal.php の side に従い、
 * 反対側にも見える部位（腕・脚など）は選択状態だけを映す飾り（p-map__mirror）として描く。
 * JavaScript が動くと、選べる部位（js-map-part）に role="button"・aria-pressed・aria-label を付ける。
 * 動かないときは装飾の絵として読み上げから外し、部位の一覧（チェックボックス）だけで操作する。
 *
 * @var string $side front|back
 */

$limbs = [
    'arms' => [
        '<rect class="p-map__shape" x="49" y="110" width="22" height="192" rx="11" transform="rotate(7 60 110)"/>',
        '<rect class="p-map__shape" x="169" y="110" width="22" height="192" rx="11" transform="rotate(-7 180 110)"/>',
    ],
    'hands' => [
        '<ellipse class="p-map__shape" cx="35" cy="316" rx="10" ry="15.5" transform="rotate(7 35 316)"/>',
        '<ellipse class="p-map__shape" cx="205" cy="316" rx="10" ry="15.5" transform="rotate(-7 205 316)"/>',
        '<circle class="p-map__hit" cx="35" cy="318" r="21"/>',
        '<circle class="p-map__hit" cx="205" cy="318" r="21"/>',
    ],
    'legs' => [
        '<path class="p-map__shape" d="M86 236H118L114 468C113.7 476 110 480 104 480H99C93 480 90.2 476 90 468Z"/>',
        '<path class="p-map__shape" d="M122 236H154L150 468C149.8 476 147 480 141 480H136C130 480 126.3 476 126 468Z"/>',
    ],
    'feet' => [
        '<ellipse class="p-map__shape" cx="101" cy="490" rx="14" ry="8"/>',
        '<ellipse class="p-map__shape" cx="139" cy="490" rx="14" ry="8"/>',
        '<circle class="p-map__hit" cx="101" cy="490" r="18"/>',
        '<circle class="p-map__hit" cx="139" cy="490" r="18"/>',
    ],
];
$neck = [
    '<path class="p-map__shape" d="M106 81Q120 93 134 81L137 106H103Z"/>',
    '<rect class="p-map__hit" x="100" y="82" width="40" height="27"/>',
];

// 描く順（後に書いたものが手前）。キーが _ で始まるものは飾り
$geometry = [
    'front' => $limbs + [
        'chest' => ['<path class="p-map__shape" d="M88 104H152C166 104 174 112 173 126L167 196C166 210 168 226 170 238C171 250 163 258 152 258H88C77 258 69 250 70 238C72 226 74 210 73 196L67 126C66 112 74 104 88 104Z"/>'],
        'face' => ['<ellipse class="p-map__shape" cx="120" cy="54" rx="27" ry="33"/>'],
        '_hair' => ['<path class="p-map__hair" d="M93 54C92 33 104 20 120 20C136 20 148 33 147 54C141 43 132 38 120 38C108 38 99 43 93 54Z"/>'],
        'neck' => $neck,
        'underarm' => [
            '<ellipse class="p-map__shape" cx="76" cy="133" rx="7" ry="12"/>',
            '<ellipse class="p-map__shape" cx="164" cy="133" rx="7" ry="12"/>',
            '<circle class="p-map__hit" cx="76" cy="133" r="15"/>',
            '<circle class="p-map__hit" cx="164" cy="133" r="15"/>',
        ],
        'vio' => ['<path class="p-map__shape" d="M98 234H142C141 252 132 266 120 272C108 266 99 252 98 234Z"/>'],
    ],
    'back' => $limbs + [
        'neck' => $neck,
        'back' => ['<path class="p-map__shape" d="M88 104H152C166 104 174 112 173 126L168 198H72L67 126C66 112 74 104 88 104Z"/>'],
        'hip' => ['<path class="p-map__shape" d="M72 198H168C167 212 168 226 170 238C172 255 161 266 147 266C135 266 126 261 120 253C114 261 105 266 93 266C79 266 68 255 70 238C72 226 73 212 72 198Z"/>'],
        '_head' => ['<ellipse class="p-map__hair" cx="120" cy="54" rx="27" ry="33"/>'],
        '_spine' => ['<path class="p-map__line" d="M120 118V186"/>'],
    ],
];

$labels = lp_part_options();
$sides = [];
foreach (lp_plan()['parts'] as $part) {
    $sides[(string) $part['id']] = (string) $part['side'];
}
?>
<svg class="p-map js-map" viewBox="0 0 240 520" width="240" height="520" aria-hidden="true" data-side="<?= e($side) ?>">
  <ellipse class="p-map__ground" cx="120" cy="504" rx="66" ry="7"/>
<?php foreach ($geometry[$side] as $key => $shapes): ?>
<?php if (str_starts_with($key, '_') || !isset($sides[$key])): ?>
  <g class="p-map__deco"><?= implode('', $shapes) ?></g>
<?php elseif ($sides[$key] === $side): ?>
  <g class="p-map__part js-map-part" data-part="<?= e($key) ?>" data-label="<?= e($labels[$key]) ?>"><?= implode('', $shapes) ?></g>
<?php else: ?>
  <g class="p-map__mirror js-map-mirror" data-part="<?= e($key) ?>"><?= implode('', $shapes) ?></g>
<?php endif; ?>
<?php endforeach; ?>
</svg>
