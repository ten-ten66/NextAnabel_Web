<?php
/**
 * 医療脱毛のしくみ（メラニンへの反応と毛周期）＋回数・期間の目安
 *
 * @var array<string, mixed> $plan
 * @var array<string, mixed> $content
 */
$course = $plan['course'];
?>
<section class="p-mechanism l-section" id="mechanism" aria-labelledby="mechanism-title">
  <div class="p-mechanism__bg" aria-hidden="true">
    <img src="<?= e(asset('img/section-bg.webp')) ?>" alt="" width="1200" height="800" loading="lazy" decoding="async">
  </div>
  <div class="l-container">
    <div class="c-section-head">
      <p class="c-section-head__label"><span class="c-section-head__num">02</span>医療脱毛のしくみ</p>
      <h2 class="c-section-head__title" id="mechanism-title">数回に分けて通うのは、毛に「周期」があるから。</h2>
    </div>

    <div class="p-mechanism__grid">
      <div class="p-mechanism__steps">
        <div class="p-mechanism__step">
          <h3 class="p-mechanism__step-title"><span class="p-mechanism__step-num">1</span>レーザーは、毛の黒い色素に反応します</h3>
          <p>医療レーザーの光は、毛に含まれる黒い色素（メラニン）に吸収されて熱に変わります。この熱で、毛を作り出す毛根の組織にダメージを与え、毛が生えにくい状態をめざします。</p>
        </div>
        <div class="p-mechanism__step">
          <h3 class="p-mechanism__step-title"><span class="p-mechanism__step-num">2</span>作用しやすいのは「成長期」の毛</h3>
          <p>毛は「成長期」「退行期」「休止期」をくり返しながら生え替わっています（毛周期）。レーザーが作用しやすいのは、毛根とつながっている成長期の毛です。1回の照射で反応するのは、その時点で成長期にある一部の毛のため、間隔をあけて数回照射します。</p>
        </div>
      </div>

      <figure class="p-mechanism__figure">
        <?php partial('diagram'); ?>
        <figcaption class="p-mechanism__caption">
          <ul class="p-mechanism__phases">
<?php foreach ($content['phases'] as $i => $phase): ?>
            <li class="p-mechanism__phase<?= $i === 0 ? ' is-growing' : '' ?>"><span class="p-mechanism__phase-name"><?= e($phase['name']) ?></span><?= e($phase['body']) ?></li>
<?php endforeach; ?>
          </ul>
        </figcaption>
      </figure>
    </div>

    <div class="p-sessions" data-disclosure="sessions">
      <div class="p-sessions__head">
        <h3 class="p-sessions__title">回数・期間の目安</h3>
        <p class="p-sessions__figure"><span class="p-sessions__num"><?= e((string) $course['count']) ?></span>回<span class="p-sessions__sep">・</span>約<span class="p-sessions__num"><?= e((string) $course['months']) ?></span>か月</p>
      </div>
      <ol class="p-sessions__timeline">
<?php for ($i = 1; $i <= (int) $course['count']; $i++): ?>
        <li class="p-sessions__step"><span class="p-sessions__dot" aria-hidden="true"></span><?= $i ?>回目</li>
<?php endfor; ?>
      </ol>
      <div class="p-sessions__text">
        <p><?= e($course['interval']) ?></p>
        <p class="p-sessions__note"><?= e($course['note']) ?></p>
      </div>
    </div>
  </div>
</section>
