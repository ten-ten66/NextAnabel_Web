<?php
/**
 * 施術詳細（静的サイト版 sites/skin-clinic/treatment.php と同じ構成）
 *
 * 費用とリスク・副作用を同じブロックに並べ、回数・ダウンタイム・問い合わせ先・監修者を
 * 折りたたまずに表示する。表示項目はメタボックスで入力し、揃うまで公開できない（inc/meta-box.php）。
 *
 * @package hakuji
 */

get_header();
the_post();
$t = hakuji_treatment(get_post());
$hasQualifying = (bool) array_filter($t['courses'], static fn ($c) => $c['qualifying']);
$toc = array_filter([
    'for' => $t['for'] ? 'こんな方に' : null,
    'flow' => $t['flow'] ? '施術の流れ' : null,
    'cost' => '費用と主なリスク・副作用',
    'unapproved' => $t['unapproved'] ? '未承認医薬品等に関する表記' : null,
    'course' => '回数・期間とダウンタイム',
    'cooling-off' => $hasQualifying ? 'クーリング・オフと中途解約' : null,
    'contraindications' => $t['contraindications'] ? '受けられない方' : null,
    'aftercare' => $t['aftercare'] ? 'アフターケア' : null,
    'contact' => 'ご予約・お問い合わせ',
]);
$related = get_posts(['post_type' => 'treatment', 'numberposts' => 3, 'post__not_in' => [$t['id']], 'orderby' => 'menu_order', 'order' => 'ASC']);
?>
<main id="main">
  <div class="p-tx-hero">
    <div class="l-container">
      <?php hakuji_the_breadcrumbs(); ?>
      <div class="p-tx-hero__grid">
        <div class="p-tx-hero__body">
          <p class="p-tx-hero__tags">
            <?php foreach ($t['category_labels'] as $label) : ?>
              <span class="c-tag"><?php echo esc_html($label); ?></span>
            <?php endforeach; ?>
          </p>
          <h1 class="p-tx-hero__title"><?php echo hakuji_name_html($t['name']); ?></h1>
          <?php if ($t['en'] !== '') : ?>
            <p class="p-tx-hero__en" aria-hidden="true"><?php echo esc_html($t['en']); ?></p>
          <?php endif; ?>
          <p class="p-tx-hero__summary"><?php echo esc_html($t['summary']); ?></p>
          <dl class="p-tx-hero__facts">
            <div class="p-tx-hero__fact"><dt>施術時間</dt><dd><?php echo esc_html($t['duration']); ?></dd></div>
            <div class="p-tx-hero__fact"><dt>回数の目安</dt><dd><?php echo esc_html($t['sessions_short']); ?></dd></div>
            <div class="p-tx-hero__fact"><dt>ダウンタイム</dt><dd><?php echo esc_html($t['downtime_short']); ?></dd></div>
            <div class="p-tx-hero__fact"><dt>麻酔</dt><dd><?php echo esc_html($t['anesthesia']); ?></dd></div>
          </dl>
          <p class="c-reviewed" data-reviewed-by>監修：<?php echo esc_html(hakuji_reviewer_label()); ?>／最終確認日 <?php echo hakuji_ja_time_tag($t['reviewed']); ?></p>
        </div>
        <div class="p-tx-hero__visual<?php echo $t['mirror'] ? ' is-mirrored' : ''; ?>">
          <img src="<?php echo esc_url(HAKUJI_URI . '/assets/img/' . $t['image']); ?>" width="960" height="720" alt="" fetchpriority="high">
        </div>
      </div>
    </div>
  </div>

  <div class="l-container p-tx-layout">
    <nav class="p-tx-toc" aria-label="このページの目次">
      <p class="p-tx-toc__title">このページの内容</p>
      <ul class="p-tx-toc__list">
        <?php foreach ($toc as $anchor => $label) : ?>
          <li><a class="p-tx-toc__link" href="#<?php echo esc_attr($anchor); ?>"><?php echo esc_html($label); ?></a></li>
        <?php endforeach; ?>
      </ul>
    </nav>

    <div class="p-tx-content">
      <?php if ($t['for']) : ?>
      <section class="p-tx-section" id="for" aria-labelledby="for-title">
        <h2 class="p-tx-section__title" id="for-title">こんな方に</h2>
        <ul class="c-check-list c-check-list--large">
          <?php foreach ($t['for'] as $item) : ?>
            <li><?php echo esc_html($item); ?></li>
          <?php endforeach; ?>
        </ul>
      </section>

      <?php endif; ?>

      <?php if ($t['flow']) : ?>
      <section class="p-tx-section" id="flow" aria-labelledby="flow-title">
        <h2 class="p-tx-section__title" id="flow-title">施術の流れ</h2>
        <ol class="p-tx-flow">
          <?php foreach ($t['flow'] as $i => $step) : ?>
            <?php [$stepTitle, $stepText] = hakuji_split_heading($step); ?>
            <li class="p-tx-flow__step">
              <span class="p-tx-flow__num" aria-hidden="true"><?php echo esc_html(sprintf('%02d', $i + 1)); ?></span>
              <?php if ($stepTitle !== '') : ?>
                <h3 class="p-tx-flow__title"><?php echo esc_html($stepTitle); ?></h3>
              <?php endif; ?>
              <p class="p-tx-flow__text"><?php echo esc_html($stepText); ?></p>
            </li>
          <?php endforeach; ?>
        </ol>
      </section>
      <?php endif; ?>

      <?php if (trim(get_the_content()) !== '') : ?>
        <section class="p-tx-section" aria-label="補足">
          <div class="p-tx-section__body"><?php the_content(); ?></div>
        </section>
      <?php endif; ?>

      <section class="p-tx-section" id="cost" aria-labelledby="cost-title">
        <h2 class="p-tx-section__title" id="cost-title">費用と主なリスク・副作用</h2>
        <p class="p-tx-section__lead">費用とあわせて、起こりうるリスク・副作用をご確認ください。</p>
        <div class="p-tx-disclosure" data-disclosure-group>
          <div class="p-tx-disclosure__price" data-disclosure="price">
            <h3 class="p-tx-disclosure__title">費用（税込）</h3>
            <?php get_template_part('template-parts/price-table', null, ['t' => $t]); ?>
            <?php if ($t['price_note'] !== '') : ?>
              <ul class="c-note-list"><li><?php echo esc_html($t['price_note']); ?></li></ul>
            <?php endif; ?>
          </div>
          <div class="p-tx-disclosure__risks" data-disclosure="risks">
            <div class="p-tx-disclosure__inner">
              <h3 class="p-tx-disclosure__title p-tx-disclosure__title--notice">主なリスク・副作用</h3>
              <ul class="c-risk-list">
                <?php foreach ($t['risks'] as $risk) : ?>
                  <li><?php echo esc_html($risk); ?></li>
                <?php endforeach; ?>
              </ul>
              <p class="c-note">症状が出た場合は、診察のうえで必要な処置を行います。</p>
            </div>
          </div>
        </div>
      </section>

      <?php if ($t['unapproved']) : ?>
        <section class="c-unapproved" id="unapproved" data-unapproved aria-labelledby="unapproved-title">
          <h2 class="c-unapproved__title" id="unapproved-title">未承認医薬品等に関する表記（サンプルの記載例）</h2>
          <dl class="c-unapproved__list">
            <?php foreach (HAKUJI_UNAPPROVED_ITEMS as $key => $label) : ?>
              <div class="c-unapproved__item" data-unapproved-item="<?php echo esc_attr($key); ?>">
                <dt><?php echo esc_html($label); ?></dt>
                <dd><?php echo esc_html($t['unapproved_info'][$key]); ?></dd>
              </div>
            <?php endforeach; ?>
          </dl>
        </section>
      <?php endif; ?>

      <section class="p-tx-section" id="course" aria-labelledby="course-title">
        <h2 class="p-tx-section__title" id="course-title">回数・期間とダウンタイム</h2>
        <div class="p-tx-facts">
          <div class="p-tx-fact" data-disclosure="sessions">
            <h3 class="p-tx-fact__title">回数・期間の目安</h3>
            <p class="p-tx-fact__text"><?php echo esc_html($t['sessions']); ?></p>
          </div>
          <div class="p-tx-fact" data-disclosure="downtime">
            <h3 class="p-tx-fact__title">ダウンタイム</h3>
            <p class="p-tx-fact__text"><?php echo esc_html($t['downtime']); ?></p>
          </div>
        </div>
      </section>

      <?php if ($hasQualifying) : ?>
        <div id="cooling-off" class="p-tx-anchor">
          <?php get_template_part('template-parts/cooling-off'); ?>
        </div>
      <?php endif; ?>

      <?php if ($t['contraindications']) : ?>
      <section class="p-tx-section" id="contraindications" aria-labelledby="contraindications-title">
        <h2 class="p-tx-section__title" id="contraindications-title">受けられない方</h2>
        <ul class="c-dash-list c-dash-list--large">
          <?php foreach ($t['contraindications'] as $item) : ?>
            <li><?php echo esc_html($item); ?></li>
          <?php endforeach; ?>
        </ul>
      </section>

      <?php endif; ?>

      <?php if ($t['aftercare']) : ?>
      <section class="p-tx-section" id="aftercare" aria-labelledby="aftercare-title">
        <h2 class="p-tx-section__title" id="aftercare-title">アフターケア</h2>
        <ul class="c-check-list c-check-list--large">
          <?php foreach ($t['aftercare'] as $item) : ?>
            <li><?php echo esc_html($item); ?></li>
          <?php endforeach; ?>
        </ul>
      </section>
      <?php endif; ?>

      <section class="p-tx-contact" id="contact" data-disclosure="contact" aria-labelledby="contact-title">
        <h2 class="p-tx-contact__title" id="contact-title">ご予約・お問い合わせ</h2>
        <p class="p-tx-contact__text"><?php echo esc_html($t['name']); ?>についてのご相談・ご予約は、Webフォームまたはお電話で承ります。</p>
        <div class="p-tx-contact__actions">
          <a class="c-button c-button--primary" href="<?php echo esc_url(add_query_arg('menu', $t['slug'], hakuji_contact_url())); ?>">この施術についてWebで予約する<?php echo hakuji_icon('arrow'); ?></a>
          <a class="p-tx-contact__tel" href="<?php echo esc_url(hakuji_tel_href()); ?>"><?php echo hakuji_icon('tel'); ?><span class="p-tx-contact__tel-number"><?php echo esc_html(hakuji_clinic('tel')); ?></span></a>
        </div>
        <dl class="p-tx-contact__info">
          <div><dt>電話受付</dt><dd><?php echo esc_html(hakuji_hours_summary()); ?></dd></div>
          <div><dt>休診日</dt><dd><?php echo esc_html(hakuji_clinic('closed')); ?></dd></div>
          <div><dt>所在地</dt><dd>〒<?php echo esc_html(hakuji_clinic('postal_code')); ?> <?php echo esc_html(hakuji_full_address()); ?></dd></div>
        </dl>
      </section>
    </div>
  </div>

  <?php if ($related) : ?>
    <section class="l-section l-section--tint p-tx-related" aria-labelledby="related-title">
      <div class="l-container">
        <div class="p-tx-related__head">
          <h2 class="p-tx-related__title" id="related-title">ほかの施術</h2>
          <a class="c-text-link" href="<?php echo esc_url((string) get_post_type_archive_link('treatment')); ?>">施術一覧へ<?php echo hakuji_icon('arrow'); ?></a>
        </div>
        <ul class="c-tx-grid c-tx-grid--related">
          <?php foreach ($related as $other) : ?>
            <?php get_template_part('template-parts/tx-card', null, ['t' => hakuji_treatment($other)]); ?>
          <?php endforeach; ?>
        </ul>
      </div>
    </section>
  <?php endif; ?>

  <?php get_template_part('template-parts/cta'); ?>
</main>
<?php
get_footer();
