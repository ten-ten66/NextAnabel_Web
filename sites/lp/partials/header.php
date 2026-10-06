<header class="l-header">
  <div class="l-container l-header__inner">
    <a class="l-header__brand" href="<?= e(url('index')) ?>">
      <svg class="c-mark l-header__mark" aria-hidden="true" width="36" height="36" viewBox="0 0 36 36">
        <circle class="c-mark__disc" cx="18" cy="18" r="17"/>
        <path class="c-mark__arc" d="M18 7.5c5.8 0 10.5 4.7 10.5 10.5S23.8 28.5 18 28.5"/>
        <path class="c-mark__beam" d="M9 27L25.5 8"/>
        <circle class="c-mark__dot" cx="18" cy="18" r="3.2"/>
      </svg>
      <span class="l-header__names">
        <span class="l-header__name"><?= e(site('name')) ?></span>
        <span class="l-header__tag">医療レーザー脱毛</span>
      </span>
    </a>
    <div class="l-header__actions">
      <a class="l-header__tel" href="<?= e(lp_tel_href()) ?>" data-cta="header-tel">
        <?= lp_icon('phone') ?>
        <span class="l-header__tel-label">電話で予約・相談</span>
        <span class="l-header__tel-num"><?= e(site('tel')) ?></span>
      </a>
      <a class="c-button c-button--primary c-button--sm" href="#reserve" data-cta="header">予約する</a>
    </div>
  </div>
</header>
