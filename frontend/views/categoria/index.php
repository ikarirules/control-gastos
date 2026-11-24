<?php

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

use common\models\Categoria;
use yii\bootstrap5\Html;
use yii\grid\ActionColumn;
use yii\grid\GridView;

$this->title = 'Categorías';
$total = $dataProvider->getTotalCount();
$limite = Categoria::MAXIMO_POR_USUARIO;
?>
<div class="categoria-index" style="max-width: 720px;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 m-0"><?= Html::encode($this->title) ?></h1>
            <div class="small <?= $total >= $limite ? 'text-danger' : 'text-muted' ?>"><?= $total ?> de <?= $limite ?></div>
        </div>
        <?php if ($total < $limite): ?>
            <?= Html::a('Nueva categoría', ['create'], ['class' => 'btn btn-primary']) ?>
        <?php else: ?>
            <span class="btn btn-primary disabled" title="Llegaste al máximo de <?= $limite ?> categorías">Nueva categoría</span>
        <?php endif; ?>
    </div>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'tableOptions' => ['class' => 'table table-hover align-middle'],
        'summary' => false,
        'emptyText' => 'No hay categorías.',
        'columns' => [
            'nombre',
            [
                'label' => 'Movimientos',
                'contentOptions' => ['class' => 'text-muted'],
                'value' => function (Categoria $c) {
                    return $c->getMovimientos()->count();
                },
            ],
            [
                'class' => ActionColumn::class,
                'template' => '{update} {delete}',
                'contentOptions' => ['class' => 'text-end text-nowrap'],
                'buttons' => [
                    'update' => function ($url) {
                        return Html::a('Editar', $url, ['class' => 'btn btn-sm btn-outline-primary']);
                    },
                    'delete' => function ($url, Categoria $c) {
                        return Html::a('Eliminar', $url, [
                            'class' => 'btn btn-sm btn-outline-danger',
                            'data-method' => 'post',
                            'data-confirm' => '¿Eliminar la categoría "' . $c->nombre . '"?',
                        ]);
                    },
                ],
            ],
        ],
    ]) ?>
</div>
