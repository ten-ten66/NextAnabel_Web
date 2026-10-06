<?php
/**
 * 本日の受付状況（JavaScript が inc/clinic.php の診療時間から計算して1分ごとに更新する）
 * JavaScript が動かないときは、最終受付の案内だけを表示し、曜日ごとの診療時間は表で確認できる。
 */
$last_entry = (string) (int) hakuji_clinic('last_entry_minutes');
?>
<div class="c-reception js-reception" data-reception="<?php echo esc_attr(hakuji_reception_json()); ?>" data-state="static">
  <div class="c-reception__dial">
    <svg class="c-reception__ring" viewBox="0 0 120 120" width="120" height="120" aria-hidden="true">
      <circle class="c-reception__track" cx="60" cy="60" r="54"/>
      <circle class="c-reception__bar js-reception-bar" cx="60" cy="60" r="54" pathLength="100" stroke-dasharray="0 100" transform="rotate(-90 60 60)"/>
    </svg>
    <p class="c-reception__center">
      <span class="c-reception__pre js-reception-pre">最終受付</span>
      <span class="c-reception__value js-reception-value"><?php echo esc_html($last_entry); ?></span>
      <span class="c-reception__unit js-reception-unit">分前</span>
    </p>
  </div>
  <div class="c-reception__text">
    <p class="c-reception__label js-reception-label">受付時間のご案内</p>
    <p class="c-reception__state js-reception-state">最終受付は、診療終了の<?php echo esc_html($last_entry); ?>分前です。</p>
    <p class="c-reception__detail js-reception-detail">曜日ごとの診療時間は、診療時間の表をご覧ください。</p>
  </div>
</div>
