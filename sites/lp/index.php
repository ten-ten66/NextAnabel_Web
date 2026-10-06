<?php
/**
 * 医療レーザー脱毛LP（1ページ構成）
 *
 * FV → 共感 → しくみ → 料金シミュレーター → 料金表 → 施術の流れ → リスク・副作用 → よくある質問 → 予約フォーム
 * 医院情報は skin-clinic/data/site.php、料金・回数は skin-clinic/data/hair_removal.php から読み込む。
 */

require __DIR__ . '/_init.php';

use Core\Form\Csrf;
use Core\Form\Guard;
use Core\Form\Session;

// サーバー版のみ: セッションを開始し、CSRF トークンとフォーム表示時刻（機械的な投稿の判定用）を記録する
$csrf = null;
if (!is_static()) {
    Session::start();
    Guard::stamp(LP_FORM_ID);
    $csrf = Csrf::token();
}

$plan = lp_plan();
$content = lp_content();
$reviewed = (string) $content['reviewed'];

// 医院は同じ1つの事業所として、コーポレートサイトの @id を参照する
$corporateUrl = lp_corporate_url();
$businessId = $corporateUrl . '#business';
$reviewer = reviewer_ld((array) site('reviewer'));
$reviewer['worksFor'] = ['@id' => $businessId];

$page = [
    'id' => 'index',
    'description' => (string) site('description'),
    'jsonld' => [
        business_ld(['@id' => $businessId, 'url' => $corporateUrl]),
        [
            '@type' => 'MedicalWebPage',
            '@id' => absolute_url('') . '#webpage',
            'url' => absolute_url(''),
            'name' => (string) site('title_top'),
            'description' => (string) site('description'),
            'inLanguage' => 'ja',
            'lastReviewed' => $reviewed,
            'reviewedBy' => $reviewer,
            'specialty' => 'https://schema.org/Dermatology',
            'publisher' => ['@id' => $businessId],
            'about' => [
                '@type' => 'MedicalProcedure',
                'name' => '医療レーザー脱毛',
                'procedureType' => 'https://schema.org/NoninvasiveProcedure',
                'bodyLocation' => '顔・首・ワキ・腕・手・胸・お腹・VIO・脚・足・背中・ヒップ',
                'howPerformed' => 'レーザーの光を毛の黒い色素（メラニン）に吸収させ、その熱で毛を作り出す組織にダメージを与える。毛周期に合わせ、間隔をあけて複数回照射する。',
            ],
        ],
    ],
];

partial('head', compact('page', 'csrf'));
partial('header');
?>
<main id="main">
<?php
partial('section-fv', compact('plan', 'content'));
partial('section-empathy', compact('content'));
partial('section-mechanism', compact('plan', 'content'));
partial('section-simulator', compact('plan', 'content'));
partial('section-price', compact('plan', 'content'));
partial('section-flow', compact('content'));
partial('section-risks', compact('content', 'reviewed'));
partial('section-faq', compact('content'));
partial('section-reserve', compact('content'));
?>
</main>
<?php partial('footer', compact('reviewed')); ?>
