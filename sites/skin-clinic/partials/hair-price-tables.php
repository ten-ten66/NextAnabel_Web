<?php
/**
 * 医療レーザー脱毛の部位別・セットの料金表（data/hair_removal.php から組み立てる。LP と共通のデータ）
 * 期間1か月超かつ5万円超のコースのセルに data-course="qualifying" と＊印を付ける。
 */
$hair = site_data('hair_removal');
$count = (int) $hair['course']['count'];
$months = (int) $hair['course']['months'];
$tables = [
    ['caption' => '部位ごとの料金（税込）', 'head' => '部位', 'rows' => $hair['parts']],
    ['caption' => '全身のセット料金（税込）', 'head' => 'プラン', 'rows' => $hair['sets']],
];
?>
<?php foreach ($tables as $table): ?>
  <table class="c-price-table c-price-table--three" data-price-context="tax-included">
    <caption class="c-price-table__caption"><?= e($table['caption']) ?></caption>
    <thead>
      <tr>
        <th scope="col"><?= e($table['head']) ?></th>
        <th scope="col">1回</th>
        <th scope="col"><?= e($count) ?>回コース</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($table['rows'] as $row): ?>
        <?php $qualifying = is_qualifying_course($months, $row['course']); ?>
        <tr>
          <th scope="row"><?= e($row['label']) ?></th>
          <td><?= yen_html($row['once']) ?></td>
          <td<?= $qualifying ? ' data-course="qualifying"' : '' ?>><?= yen_html($row['course']) ?><?php if ($qualifying): ?><span class="c-mark" aria-hidden="true">＊</span><span class="u-visually-hidden">クーリング・オフ対象</span><?php endif; ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php endforeach; ?>
