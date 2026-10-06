<?php
/**
 * ロゴ（芽のマーク＋和文＋欧文）
 *
 * @var string $class 追加の class
 */
$class ??= '';
?>
<span class="c-logo <?= e($class) ?>">
  <svg class="c-logo__mark" viewBox="0 0 32 32" aria-hidden="true" focusable="false">
    <path class="c-logo__stem" d="M16 27.5V15"/>
    <path class="c-logo__leaf c-logo__leaf--moss" d="M16 18.2C10.4 18.4 6.6 14.6 6 8.6c5.8-.2 9.6 3.5 10 9.6Z"/>
    <path class="c-logo__leaf c-logo__leaf--mauve" d="M16 15.6c.3-5.6 4.1-9.3 9.9-9.4-.1 5.9-3.9 9.5-9.9 9.4Z"/>
  </svg>
  <span class="c-logo__ja"><?= e(site('name')) ?></span>
  <span class="c-logo__en" lang="en"><?= e(site('name_en')) ?></span>
</span>
