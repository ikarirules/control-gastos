<?php

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

use common\models\MedioPago;
use yii\bootstrap5\Html;
use yii\grid\ActionColumn;
use yii\grid\GridView;

$this->title = 'Medios de pago';
$total = $dataProvider->getTotalCount();
$limite = MedioPago::MAXIMO_POR_USUARIO;
?>
<div class="medio-pago-index" style="max-width: 820px;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 m-0"><?= Html::encode($this->title) ?></h1>
            <div class="small <?= $total >= $limite ? 'text-danger' : 'text-muted' ?>"><?= $total ?> de <?= $limite ?></div>
        </div>
        <?php if ($total < $limite): ?>
            <?= Html::a('Nuevo medio de pago', ['create'], ['class' => 'btn btn-primary']) ?>
        <?php else: ?>
            <span class="btn btn-primary disabled" title="Llegaste al máximo de <?= $limite ?> medios de pago">Nuevo medio de pago</span>
        <?php endif; ?>
    </div>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'tableOptions' => ['class' => 'table table-hover align-middle'],
        'summary' => false,
        'emptyText' => 'No hay medios de pago.',
        'columns' => [
            'nombre',
            [
                'attribute' => 'dia_vencimiento',
                'label' => 'Vencimiento',
                'value' => function (MedioPago $m) {
                    return $m->textoVencimiento();
                },
            ],
            [
                'label' => 'En uso',
                'contentOptions' => ['class' => 'text-muted small'],
                'value' => function (MedioPago $m) {
                    $mov = $m->getMovimientos()->count();
                    $cuo = $m->getPrestamos()->count();
                    return "$mov movimiento" . ($mov === 1 ? '' : 's') . " · $cuo cuota" . ($cuo === 1 ? '' : 's');
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
                    'delete' => function ($url, MedioPago $m) {
                        return Html::a('Eliminar', $url, [
                            'class' => 'btn btn-sm btn-outline-danger',
                            'data-method' => 'post',
                            'data-confirm' => '¿Eliminar el medio de pago "' . $m->nombre . '"?',
                        ]);
                    },
                ],
            ],
        ],
    ]) ?>
</div>
