<?php

use yii\helpers\Html;
use yii\grid\GridView;

/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var biz\accounting\models\searchs\AccPeriode $searchModel
 */

$this->title = 'Acc Periodes';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="acc-periode-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <p>
        <?= Html::a('Create Acc Periode', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'nm_periode',
            'dateFrom',
            'dateTo',
            'nmStatus',

            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>

</div>
