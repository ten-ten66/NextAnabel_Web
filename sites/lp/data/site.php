<?php
/**
 * 医療脱毛LPのサイト設定
 *
 * 医院の基本情報（名称・住所・電話番号・診療時間・監修医）は、コーポレートサイトの
 * sites/skin-clinic/data/site.php をそのまま読み込み、LP 固有の値だけを上書きする。
 * NAP（Name / Address / Phone）が2つのサイトで食い違わないようにするため、ここに医院情報を書かないこと。
 */

$clinic = require dirname(__DIR__, 2) . '/skin-clinic/data/site.php';

return array_replace($clinic, [
    'base' => 'lp',
    'corporate_base' => $clinic['base'],
    'title_top' => '医療レーザー脱毛｜白磁スキンクリニック',
    'description' => '南青山の白磁スキンクリニックの医療レーザー脱毛。部位を選ぶと、5回コースの税込総額と通院回数の目安をその場で試算できます。医師の診察、リスク・副作用、クーリング・オフまで、契約の前に知っておきたいことをまとめました。',
    'theme_color' => '#F7F8FB',
    'og_image' => 'img/ogp.png',
]);
