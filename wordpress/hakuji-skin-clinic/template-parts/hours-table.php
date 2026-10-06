<?php
/**
 * 曜日ごとの診療時間の表（本日の行は JavaScript で強調する）
 */
$last_entry = (string) (int) hakuji_clinic('last_entry_minutes');
?>
<table class="c-hours js-hours" data-reception="<?php echo esc_attr(hakuji_reception_json()); ?>">
  <caption class="c-hours__caption">診療時間<span class="c-hours__note">最終受付は診療終了の<?php echo esc_html($last_entry); ?>分前です</span></caption>
  <thead>
    <tr>
      <th scope="col">曜日</th>
      <th scope="col">診療時間</th>
      <th scope="col">最終受付</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach (hakuji_schedule_rows() as $row) : ?>
      <tr class="c-hours__row<?php echo $row['open'] === null ? ' is-closed' : ''; ?>" data-day="<?php echo esc_attr($row['day']); ?>">
        <th scope="row"><?php echo esc_html($row['label']); ?></th>
        <?php if ($row['open'] !== null) : ?>
          <td><time datetime="<?php echo esc_attr($row['open']); ?>"><?php echo esc_html($row['open']); ?></time>〜<time datetime="<?php echo esc_attr((string) $row['close']); ?>"><?php echo esc_html((string) $row['close']); ?></time></td>
          <td><time datetime="<?php echo esc_attr((string) $row['last']); ?>"><?php echo esc_html((string) $row['last']); ?></time></td>
        <?php else : ?>
          <td colspan="2">休診</td>
        <?php endif; ?>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
