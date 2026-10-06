<?php
/**
 * 施術の料金表（1回の料金とコース）
 * 表全体が税込の文脈（data-price-context="tax-included"）で、見出しに「（税込）」を書く。
 * 期間1か月超かつ5万円超のコースには data-course="qualifying" と＊印を付ける。
 * @var array<string, mixed> $t
 * @var bool|null $showCaption キャプションを画面に表示するか（既定は読み上げのみ）
 */
?>
<table class="c-price-table" data-price-context="tax-included">
  <caption class="<?= !empty($showCaption) ? 'c-price-table__caption' : 'u-visually-hidden' ?>"><?= e($t['name']) ?>の料金（税込）</caption>
  <thead>
    <tr>
      <th scope="col">メニュー</th>
      <th scope="col">料金（税込）</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($t['prices'] as $price): ?>
      <tr>
        <th scope="row"><?= e($price['label']) ?></th>
        <td><?= yen_html($price['amount']) ?></td>
      </tr>
    <?php endforeach; ?>
    <?php foreach ($t['courses'] as $course): ?>
      <?php $qualifying = is_qualifying_course($course['months'], $course['amount']); ?>
      <tr<?= $qualifying ? ' data-course="qualifying"' : '' ?>>
        <th scope="row"><?= e($course['label']) ?><span class="c-price-table__sub">期間の目安 <?= e($course['months']) ?>か月</span></th>
        <td><?= yen_html($course['amount']) ?><?php if ($qualifying): ?><span class="c-mark" aria-hidden="true">＊</span><span class="u-visually-hidden">クーリング・オフ対象</span><?php endif; ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
