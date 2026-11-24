<?php

/** @var yii\web\View $this */
/** @var frontend\models\BilleteraForm $form */
/** @var array $lista id => nombre */
/** @var bool $limite */
/** @var array $resumen Billetera::resumen() */
/** @var array $meses Billetera::porMes() */
/** @var array $caja Movimiento::caja() */

use common\models\Movimiento;
use common\models\Prestamo;
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = 'Billeteras';
// Al escribir una billetera nueva, volver a validar el desplegable (deja de ser obligatorio)
$this->registerJs(<<<'JS'
$('#billeteraform-nueva').on('input', function () {
    if ($('#billeteraform-billetera_id').length) {
        $('#billetera-form').yiiActiveForm('validateAttribute', 'billeteraform-billetera_id');
    }
});
JS);

$fecha = function ($f) {
    return date('d/m/Y', strtotime($f));
};
$monto = function ($m) {
    return ($m < 0 ? '- ' : '') . Movimiento::formatoMonto(abs($m));
};
// Diferencia contra el registro anterior: "+ $ 20" en verde, "- $ 500" en rojo
$diferencia = function ($d, $dias = null) {
    if ($d === null) {
        return '<span class="text-muted">primer registro</span>';
    }
    $dias = $dias === null ? '' : ' <span class="text-muted">en ' . $dias . ' día' . ($dias === 1 ? '' : 's') . '</span>';
    if (abs($d) < 0.005) {
        return '<span class="text-muted">sin cambios</span>' . $dias;
    }
    return Html::tag('span', ($d > 0 ? '▲ + ' : '▼ - ') . Movimiento::formatoMonto(abs($d)), [
        'class' => 'fw-semibold ' . ($d > 0 ? 'text-success' : 'text-danger'),
    ]) . $dias;
};
$hayDatos = $resumen['billeteras'] && array_filter(array_column($resumen['billeteras'], 'ultimo'));
$diferenciaCaja = $resumen['total'] - $caja['saldo'];
?>
<div class="billetera-index">
    <div class="mb-3">
        <h1 class="h3 m-0">Billeteras</h1>
        <div class="small text-muted">Cargá cuánto tenés en cada una y mirá cómo cambió desde la vez anterior</div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header fw-semibold">Cargar saldo de hoy · <?= date('d/m/Y') ?></div>
                <div class="card-body">
                    <?php $f = ActiveForm::begin(['id' => 'billetera-form', 'action' => ['index']]); ?>
                    <?php if ($lista): ?>
                        <?= $f->field($form, 'billetera_id')->dropDownList($lista, ['prompt' => 'Elegí una billetera...']) ?>
                    <?php endif; ?>
                    <?php if (!$limite): ?>
                        <?= $f->field($form, 'nueva')->textInput([
                            'maxlength' => true,
                            'placeholder' => 'Ej: Mercado Pago, Ualá, Banco Nación',
                        ])->label($lista ? 'O agregá una nueva' : 'Nombre de la billetera')
                            ->hint($lista ? 'Si escribís un nombre, se crea la billetera y se ignora la elegida arriba.' : null) ?>
                    <?php endif; ?>
                    <?= $f->field($form, 'monto', [
                        'inputTemplate' => '<div class="input-group"><span class="input-group-text">$</span>{input}</div>',
                    ])->input('number', ['step' => '0.01', 'min' => '0', 'placeholder' => '0']) ?>
                    <?= Html::submitButton('Guardar', ['class' => 'btn btn-primary']) ?>
                    <div class="small text-muted mt-2">Si ya cargaste esa billetera hoy, se reemplaza el monto.</div>
                    <?php ActiveForm::end(); ?>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card h-100 border-primary">
                <div class="card-body">
                    <div class="text-muted small">Total en billeteras</div>
                    <div class="fs-3 fw-semibold text-primary"><?= $monto($resumen['total']) ?></div>
                    <?php if ($hayDatos): ?>
                        <div class="small mb-3">Contra la carga anterior de cada una: <?= $diferencia($resumen['diferencia']) ?></div>
                        <div class="small border-top pt-2">
                            <div class="d-flex justify-content-between">
                                <span>Saldo según tus movimientos</span><span><?= $monto($caja['saldo']) ?></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Diferencia</span>
                                <span class="<?= abs($diferenciaCaja) < 0.005 ? 'text-success' : 'text-warning' ?>">
                                    <?= abs($diferenciaCaja) < 0.005 ? 'coinciden ✔' : ($diferenciaCaja > 0 ? '+ ' : '- ') . Movimiento::formatoMonto(abs($diferenciaCaja)) ?>
                                </span>
                            </div>
                            <div class="text-muted mt-1">
                                Sirve de control: si no coinciden, puede faltar cargar algún movimiento
                                (o tenés plata fuera de las billeteras, como efectivo).
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="small text-muted">Todavía no cargaste ningún saldo. Empezá con el formulario de la izquierda.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if ($resumen['billeteras']): ?>
        <div class="row g-3 mb-4">
            <?php foreach ($resumen['billeteras'] as $fila):
                $b = $fila['billetera'];
                $ultimo = $fila['ultimo'];
                $idHist = 'hist-billetera-' . $b->id;
            ?>
                <div class="col-md-6 col-xl-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <span class="fw-semibold"><?= Html::encode($b->nombre) ?></span>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-link text-muted p-0 px-1" data-bs-toggle="dropdown" aria-label="Opciones">⋯</button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><?= Html::a('Renombrar', ['update', 'id' => $b->id], ['class' => 'dropdown-item']) ?></li>
                                        <li><?= Html::a('Eliminar', ['delete', 'id' => $b->id], [
                                            'class' => 'dropdown-item text-danger',
                                            'data-method' => 'post',
                                            'data-confirm' => '¿Eliminar "' . $b->nombre . '" y todos sus saldos cargados?',
                                        ]) ?></li>
                                    </ul>
                                </div>
                            </div>
                            <?php if ($ultimo): ?>
                                <div class="fs-4 fw-semibold"><?= $monto($ultimo->monto) ?></div>
                                <div class="small text-muted">al <?= $fecha($ultimo->fecha) ?></div>
                                <div class="small mt-1"><?= $diferencia($fila['diferencia'], $fila['dias']) ?></div>
                            <?php else: ?>
                                <div class="small text-muted mt-2">Sin saldos cargados.</div>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer d-flex justify-content-between align-items-center">
                            <?= Html::a('Cargar saldo', ['index', 'billetera' => $b->id, '#' => 'billetera-form'], ['class' => 'btn btn-sm btn-outline-primary']) ?>
                            <?php if ($fila['registros']): ?>
                                <button class="btn btn-sm btn-link text-decoration-none" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#<?= $idHist ?>">
                                    Historial (<?= count($fila['registros']) ?>)
                                </button>
                            <?php endif; ?>
                        </div>
                        <?php if ($fila['registros']): ?>
                            <div class="collapse" id="<?= $idHist ?>">
                                <ul class="list-group list-group-flush small">
                                    <?php foreach ($fila['registros'] as $r): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center gap-2">
                                            <span class="text-muted text-nowrap"><?= $fecha($r['saldo']->fecha) ?></span>
                                            <span class="text-end">
                                                <?= $monto($r['saldo']->monto) ?><br>
                                                <?= $diferencia($r['diferencia'], $r['dias']) ?>
                                            </span>
                                            <?= Html::a('✕', ['delete-saldo', 'id' => $r['saldo']->id], [
                                                'class' => 'btn btn-sm btn-link text-danger p-0',
                                                'title' => 'Eliminar este registro',
                                                'data-method' => 'post',
                                                'data-confirm' => '¿Eliminar el saldo del ' . $fecha($r['saldo']->fecha) . '?',
                                            ]) ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($meses): ?>
        <div class="card mb-4">
            <div class="card-header">
                <span class="fw-semibold">Comienzo y cierre por mes</span>
                <span class="small text-muted ms-2">Comienzo = primera carga del mes · Cierre = última carga del mes</span>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Billetera</th>
                            <th class="text-end">Comienzo</th>
                            <th class="text-end">Cierre</th>
                            <th class="text-end">Variación</th>
                        </tr>
                    </thead>
                    <?php foreach ($meses as $m): ?>
                        <tbody>
                            <tr class="table-active">
                                <th colspan="2"><?= Prestamo::nombreMes(new DateTimeImmutable($m['mes'] . '-01')) ?></th>
                                <th class="text-end">Total <?= $monto($m['total']) ?></th>
                                <th class="text-end"><?= $m['variacion'] === null ? '' : $diferencia($m['variacion']) ?></th>
                            </tr>
                            <?php foreach ($m['filas'] as $fila): ?>
                                <tr>
                                    <td><?= Html::encode($fila['billetera']->nombre) ?></td>
                                    <td class="text-end text-nowrap">
                                        <?= $monto($fila['inicio']->monto) ?>
                                        <div class="small text-muted"><?= $fecha($fila['inicio']->fecha) ?></div>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <?= $monto($fila['cierre']->monto) ?>
                                        <div class="small text-muted"><?= $fecha($fila['cierre']->fecha) ?></div>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <?= $diferencia($fila['variacion']) ?>
                                        <div class="small text-muted">contra <?= $fila['contra'] ?></div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>
