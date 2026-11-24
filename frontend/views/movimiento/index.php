<?php

/** @var yii\web\View $this */
/** @var frontend\models\MovimientoSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var array $totales */
/** @var array $categorias */
/** @var array $mediosPago */

use common\models\Movimiento;
use common\models\Prestamo;
use yii\bootstrap5\Html;
use yii\bootstrap5\LinkPager;
use yii\grid\ActionColumn;
use yii\grid\GridView;

$mes = $searchModel->mes;
$esMesActual = $mes === Movimiento::mesActual();
$fechaMes = new DateTimeImmutable($mes . '-01');
$anterior = $fechaMes->modify('-1 month')->format('Y-m');
$siguiente = $fechaMes->modify('+1 month')->format('Y-m');
$nombreMes = Prestamo::nombreMes($fechaMes);

$this->title = 'Movimientos de ' . $nombreMes;
?>
<div class="movimiento-index">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div class="d-flex align-items-center gap-2">
            <?= Html::a('&#8249;', ['index', 'mes' => $anterior], ['class' => 'btn btn-outline-secondary', 'title' => 'Mes anterior']) ?>
            <div>
                <h1 class="h3 m-0"><?= Html::encode($nombreMes) ?></h1>
                <div class="small <?= $esMesActual ? 'text-primary' : 'text-warning' ?>">
                    <?= $esMesActual ? 'Mes actual' : 'Historial · mes cerrado' ?>
                </div>
            </div>
            <?php if (!$esMesActual): ?>
                <?= Html::a('&#8250;', ['index', 'mes' => $siguiente], ['class' => 'btn btn-outline-secondary', 'title' => 'Mes siguiente']) ?>
            <?php endif; ?>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <?php if (!$esMesActual): ?>
                <?= Html::a('Volver al mes actual', ['index'], ['class' => 'btn btn-outline-primary']) ?>
            <?php endif; ?>
            <?= Html::a('Historial', ['historial'], ['class' => 'btn btn-outline-secondary']) ?>
            <?= Html::a('&#8593; Ingreso', ['create', 'tipo' => Movimiento::TIPO_INGRESO], ['class' => 'btn btn-success']) ?>
            <?= Html::a('&#8595; Egreso', ['create', 'tipo' => Movimiento::TIPO_EGRESO], ['class' => 'btn btn-danger']) ?>
        </div>
    </div>

    <?= $this->render('_totales', ['totales' => $totales, 'cerrado' => !$esMesActual]) ?>

    <?= Html::beginForm(['index'], 'get', ['class' => 'row g-2 align-items-end mb-3']) ?>
        <div class="col-sm-6 col-md-2">
            <label class="form-label small mb-1">Tipo</label>
            <?= Html::dropDownList('tipo', $searchModel->tipo, [
                Movimiento::TIPO_INGRESO => 'Ingresos',
                Movimiento::TIPO_EGRESO => 'Egresos',
            ], ['class' => 'form-select', 'prompt' => 'Todos']) ?>
        </div>
        <div class="col-sm-6 col-md-2">
            <label class="form-label small mb-1">Categoría</label>
            <?= Html::dropDownList('categoria_id', $searchModel->categoria_id, $categorias, [
                'class' => 'form-select', 'prompt' => 'Todas',
            ]) ?>
        </div>
        <div class="col-sm-6 col-md-2">
            <label class="form-label small mb-1">Medio de pago</label>
            <?= Html::dropDownList('medio_pago_id', $searchModel->medio_pago_id, $mediosPago, [
                'class' => 'form-select', 'prompt' => 'Todos',
            ]) ?>
        </div>
        <div class="col-sm-6 col-md-2">
            <label class="form-label small mb-1">Motivo</label>
            <?= Html::textInput('motivo', $searchModel->motivo, ['class' => 'form-control', 'placeholder' => 'Buscar...']) ?>
        </div>
        <div class="col-sm-6 col-md-2">
            <label class="form-label small mb-1">Mes</label>
            <?= Html::input('month', 'mes', $mes, ['class' => 'form-control', 'max' => Movimiento::mesActual()]) ?>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <?= Html::submitButton('Filtrar', ['class' => 'btn btn-primary flex-fill']) ?>
            <?= Html::a('Limpiar', ['index', 'mes' => $mes], ['class' => 'btn btn-outline-secondary']) ?>
        </div>
    <?= Html::endForm() ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'tableOptions' => ['class' => 'table table-hover align-middle'],
        'summary' => 'Mostrando <strong>{begin}-{end}</strong> de <strong>{totalCount}</strong> movimientos',
        'emptyText' => 'No hay movimientos en ' . $nombreMes . '.',
        'pager' => [
            'class' => LinkPager::class,
            'options' => ['aria-label' => 'Páginas de movimientos'],
        ],
        'columns' => [
            [
                'attribute' => 'fecha',
                'value' => function (Movimiento $m) {
                    return date('d/m/Y H:i', strtotime($m->fecha));
                },
            ],
            [
                'attribute' => 'tipo',
                'format' => 'raw',
                'value' => function (Movimiento $m) {
                    return $m->esIngreso()
                        ? '<span class="badge bg-success">Ingreso</span>'
                        : '<span class="badge bg-danger">Egreso</span>';
                },
            ],
            'motivo',
            [
                'attribute' => 'categoria_id',
                'value' => 'categoria.nombre',
            ],
            [
                'attribute' => 'medio_pago_id',
                'value' => function (Movimiento $m) {
                    return $m->medioPago ? $m->medioPago->nombre : '—';
                },
            ],
            [
                'attribute' => 'monto',
                'format' => 'raw',
                'contentOptions' => ['class' => 'text-end text-nowrap'],
                'headerOptions' => ['class' => 'text-end'],
                'value' => function (Movimiento $m) {
                    return Html::tag(
                        'span',
                        ($m->esIngreso() ? '+ ' : '- ') . Movimiento::formatoMonto($m->monto),
                        ['class' => $m->esIngreso() ? 'text-success fw-semibold' : 'text-danger fw-semibold']
                    );
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
                    'delete' => function ($url) {
                        return Html::a('Eliminar', $url, [
                            'class' => 'btn btn-sm btn-outline-danger',
                            'data-method' => 'post',
                            'data-confirm' => '¿Eliminar este movimiento?',
                        ]);
                    },
                ],
            ],
        ],
    ]) ?>
</div>
