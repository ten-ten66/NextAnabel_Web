<?php
/**
 * 施術の料金表。表全体が税込の文脈で、期間1か月超かつ5万円超のコースには＊印を付ける。
 *
 * @var array{t: array<string, mixed>} $args
 */
$t = $args['t'];
?>
<table class="c-price-table" data-price-context="tax-included">
  <caption class="u-visually-hidden"><?php echo esc_html($t['name']); ?>の料金（税込）</caption>
  <thead>
    <tr>
      <th scope="col">メニュー</th>
      <th scope="col">料金（税込）</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($t['prices'] as $price) : ?>
      <tr>
        <th scope="row"><?php echo esc_html($price['label']); ?></th>
        <td><?php echo hakuji_yen_html($price['amount']); ?></td>
      </tr>
    <?php endforeach; ?>
    <?php foreach ($t['courses'] as $course) : ?>
      <tr<?php echo $course['qualifying'] ? ' data-course="qualifying"' : ''; ?>>
        <th scope="row"><?php echo esc_html($course['label']); ?><span class="c-price-table__sub">期間の目安 <?php echo esc_html((string) $course['months']); ?>か月</span></th>
        <td><?php echo hakuji_yen_html($course['amount']); ?><?php if ($course['qualifying']) : ?><span class="c-mark" aria-hidden="true">＊</span><span class="u-visually-hidden">クーリング・オフ対象</span><?php endif; ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
