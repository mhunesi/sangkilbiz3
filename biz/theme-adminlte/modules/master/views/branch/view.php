<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/**
 * @var yii\web\View $this
 * @var biz\models\Branch $model
 */
$this->title = 'Branch #' . $model->id_branch;
$this->params['breadcrumbs'][] = ['label' => 'Branches', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="branch-view col-lg-8">
    <div class="box box-info">
        <div class="box-body no-padding">
            <?php
            echo DetailView::widget([
                'model' => $model,
                'attributes' => [
                    'id_branch',
                    'id_orgn',
                    'cd_branch',
                    'nm_branch',
                    'create_at',
                    'update_at',
                    'update_by',
                    'create_by',
                ],
            ]);
            ?>
        </div>
        <div class="box-footer">
            <?= Html::a('Update', ['update', 'id' => $model->id_branch], ['class' => 'btn btn-primary']) ?>
            <?php
            echo Html::a('Delete', ['delete', 'id' => $model->id_branch], [
                'class' => 'btn btn-danger',
                'data-confirm' => Yii::t('app', 'Are you sure to delete this item?'),
                'data-method' => 'post',
            ]);
            ?>
        </div>
    </div>

</div>
