<?php
/**
 * 曜日ごとの診療時間の表（本日の行は JavaScript で強調する）
 * @var string|null $caption
 */
$lastEntry = (int) site('last_entry_minutes', 0);
?>
<table class="c-hours js-hours">
  <caption class="c-hours__caption"><?= e($caption ?? '診療時間') ?><span class="c-hours__note">最終受付は診療終了の<?= e($lastEntry) ?>分前です</span></caption>
  <thead>
    <tr>
      <th scope="col">曜日</th>
      <th scope="col">診療時間</th>
      <th scope="col">最終受付</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach (schedule_rows() as $row): ?>
      <tr class="c-hours__row<?= $row['open'] === null ? ' is-closed' : '' ?>" data-day="<?= e($row['day']) ?>">
        <th scope="row"><?= e($row['label']) ?></th>
        <?php if ($row['open'] !== null): ?>
          <td><time datetime="<?= e($row['open']) ?>"><?= e($row['open']) ?></time>〜<time datetime="<?= e($row['close']) ?>"><?= e($row['close']) ?></time></td>
          <td><time datetime="<?= e($row['last']) ?>"><?= e($row['last']) ?></time></td>
        <?php else: ?>
          <td colspan="2">休診</td>
        <?php endif; ?>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
