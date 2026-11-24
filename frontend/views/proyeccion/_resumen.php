<?php

/** @var yii\web\View $this */
/** @var array $proyeccion Presupuesto::proyeccion() */

use common\models\Movimiento;
use yii\bootstrap5\Html;

$p = $proyeccion;
$f = function ($monto) {
    return ($monto < 0 ? '- ' : '') . Movimiento::formatoMonto(abs($monto));
};
?>
<div class="card h-100 border-info">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <span class="text-muted small">Proyección del mes</span>
            <?= Html::a('Estimados', ['proyeccion/index'], ['class' => 'small']) ?>
        </div>
        <div class="fs-4 fw-semibold text-info"><?= Movimiento::formatoMonto($p['total']) ?></div>
        <div class="small text-muted mb-1">
            Cuotas <?= Movimiento::formatoMonto($p['cuotas']) ?>
            · Fijos <?= Movimiento::formatoMonto($p['fijos']) ?>
            · Variables <?= Movimiento::formatoMonto($p['variables']) ?>
        </div>
        <div class="small">
            Falta <span class="<?= $p['falta'] > 0 ? 'text-danger' : 'text-muted' ?>"><?= Movimiento::formatoMonto($p['falta']) ?></span>
            · Al cierre te quedarían
            <strong class="<?= $p['saldo_cierre'] < 0 ? 'text-danger' : 'text-success' ?>"><?= $f($p['saldo_cierre']) ?></strong>
        </div>
        <?php if ($p['variables'] == 0): ?>
            <div class="small text-warning mt-1">
                Todavía no cargaste estimados de gastos variables. <?= Html::a('Cargalos acá', ['proyeccion/index']) ?>.
            </div>
        <?php endif; ?>
    </div>
</div>
