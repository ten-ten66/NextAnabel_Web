<?php
/**
 * 人体図（前面・背面）
 *
 * 部位の形は単純な図形の組み合わせで描く。どちら側で選べるかは hair_removal.php の side に従い、
 * 反対側にも見える部位（腕・脚など）は選択状態だけを映す飾り（p-map__mirror）として描く。
 *
 * interactive（料金シミュレーター）
 *   JavaScript が動くと、選べる部位（js-map-part）に role="button"・aria-pressed・aria-label を付ける。
 *   動かないときは装飾の絵として読み上げから外し、部位の一覧（チェックボックス）だけで操作する。
 * decorative（ファーストビューの挿絵）
 *   操作できない飾り。$highlight の部位を強調色で重ねる。
 *
 * @var string $side front|back
 * @var string $mode interactive|decorative（省略時 interactive）
 * @var list<string> $highlight decorative のときに強調する部位
 */

$mode ??= 'interactive';
$highlight ??= [];
$interactive = $mode === 'interactive';

$limbs = [
    'arms' => [
        '<path class="p-map__shape" d="M79 108C69 109 62 117 61 129C59 152 56 176 53 200C50 228 47 256 45 282C45 287 49 290 52 290C55 290 58 288 58.5 284C61.5 258 65 230 69 204C72 182 77 160 80.5 138Z"/>',
        '<path class="p-map__shape" d="M161 108C171 109 178 117 179 129C181 152 184 176 187 200C190 228 193 256 195 282C195 287 191 290 188 290C185 290 182 288 181.5 284C178.5 258 175 230 171 204C168 182 163 160 159.5 138Z"/>',
    ],
    'hands' => [
        '<path class="p-map__shape" d="M45.5 283C41 290 39.5 300 41 308C42.5 316 51 318 54.5 311C57 305 58.5 295 58.5 284Z"/>',
        '<path class="p-map__shape" d="M194.5 283C199 290 200.5 300 199 308C197.5 316 189 318 185.5 311C183 305 181.5 295 181.5 284Z"/>',
        '<circle class="p-map__hit" cx="49" cy="301" r="21"/>',
        '<circle class="p-map__hit" cx="191" cy="301" r="21"/>',
    ],
    'legs' => [
        '<path class="p-map__shape" d="M80 234C80 280 86 320 89 352C92 390 95 430 97 466C97.5 471 100.5 474 104 474C107.5 474 110 471 110.5 466C111.5 430 113 392 114.5 354C116 320 118.5 290 120 258Z"/>',
        '<path class="p-map__shape" d="M160 234C160 280 154 320 151 352C148 390 145 430 143 466C142.5 471 139.5 474 136 474C132.5 474 130 471 129.5 466C128.5 430 127 392 125.5 354C124 320 121.5 290 120 258Z"/>',
    ],
    'feet' => [
        '<path class="p-map__shape" d="M97.5 464C93 469 87 475 88 480.5C89 486 104 487.5 109.5 483.5C112.5 481 112 472 110.5 464Z"/>',
        '<path class="p-map__shape" d="M142.5 464C147 469 153 475 152 480.5C151 486 136 487.5 130.5 483.5C127.5 481 128 472 129.5 464Z"/>',
        '<circle class="p-map__hit" cx="100" cy="478" r="19"/>',
        '<circle class="p-map__hit" cx="140" cy="478" r="19"/>',
    ],
];
$neck = [
    '<path class="p-map__shape" d="M108 70C109.5 80 108.5 89 103 101H137C131.5 89 130.5 80 132 70Z"/>',
    '<rect class="p-map__hit" x="100" y="74" width="40" height="28"/>',
];
$torso = 'M106 98C92 99 80 103 75 113C71 121 73 131 78.5 139C82 160 85 180 84 198';
$torsoRight = 'L156 198C155 180 158 160 161.5 139C167 131 169 121 165 113C160 103 148 99 134 98Z';

// 描く順（後に書いたものが手前）。キーが _ で始まるものは飾り
$geometry = [
    'front' => $limbs + [
        'neck' => $neck,
        'chest' => ['<path class="p-map__shape" d="M106 98C92 99 80 103 75 113C71 121 73 131 78.5 139C82 160 85 180 84 198C83 214 78 226 79 240C80 252 90 259 104 260H136C150 259 160 252 161 240C162 226 157 214 156 198C155 180 158 160 161.5 139C167 131 169 121 165 113C160 103 148 99 134 98Z"/>'],
        'face' => ['<ellipse class="p-map__shape" cx="120" cy="47" rx="25" ry="30"/>'],
        '_hair' => ['<path class="p-map__hair" d="M95.6 53C93 33 104 17 120 17C136 17 147 33 144.4 53C142 44.5 138 39 132 36C124 40.5 113 40.5 106.5 36C101 39.5 97.5 45 95.6 53Z"/>'],
        'underarm' => [
            '<ellipse class="p-map__shape" cx="80" cy="129" rx="5.5" ry="10"/>',
            '<ellipse class="p-map__shape" cx="160" cy="129" rx="5.5" ry="10"/>',
            '<circle class="p-map__hit" cx="79" cy="130" r="16"/>',
            '<circle class="p-map__hit" cx="161" cy="130" r="16"/>',
        ],
        'vio' => ['<path class="p-map__shape" d="M103 240H137C136 252.5 129 261.5 120 266C111 261.5 104 252.5 103 240Z"/>'],
    ],
    'back' => $limbs + [
        'neck' => $neck,
        'back' => ['<path class="p-map__shape" d="' . $torso . $torsoRight . '"/>'],
        'hip' => ['<path class="p-map__shape" d="M84 198H156C157 214 162 226 161 240C160 256 150 266 136 266C128 266 123 262 120 257C117 262 112 266 104 266C90 266 80 256 79 240C78 226 83 214 84 198Z"/>'],
        '_head' => ['<ellipse class="p-map__hair" cx="120" cy="47" rx="25" ry="30"/>'],
        '_spine' => ['<path class="p-map__line" d="M120 112V190"/>'],
    ],
];

$labels = lp_part_options();
$sides = [];
foreach (lp_plan()['parts'] as $part) {
    $sides[(string) $part['id']] = (string) $part['side'];
}
$shapesOnly = static fn (array $shapes): string => implode('', array_filter($shapes, static fn ($s) => !str_contains($s, 'p-map__hit')));
?>
<svg class="p-map<?= $interactive ? ' js-map' : ' p-map--decorative' ?>" viewBox="0 0 240 520" width="240" height="520" aria-hidden="true" data-side="<?= e($side) ?>">
  <ellipse class="p-map__ground" cx="120" cy="490" rx="58" ry="6"/>
<?php foreach ($geometry[$side] as $key => $shapes): ?>
<?php if (str_starts_with($key, '_') || !isset($sides[$key])): ?>
  <g class="p-map__deco js-map-deco"><?= implode('', $shapes) ?></g>
<?php elseif (!$interactive): ?>
  <g class="p-map__base"><?= $shapesOnly($shapes) ?></g>
<?php if (in_array($key, $highlight, true)): ?>
  <g class="p-map__hl"><?= $shapesOnly($shapes) ?></g>
<?php endif; ?>
<?php elseif ($sides[$key] === $side): ?>
  <g class="p-map__part js-map-part" data-part="<?= e($key) ?>" data-label="<?= e($labels[$key]) ?>"><?= implode('', $shapes) ?></g>
<?php else: ?>
  <g class="p-map__mirror js-map-mirror" data-part="<?= e($key) ?>"><?= implode('', $shapes) ?></g>
<?php endif; ?>
<?php endforeach; ?>
</svg>
