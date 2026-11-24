<?php

/** @var array $totales Movimiento::caja(): ['anterior', 'ingresos', 'egresos', 'saldo_mes', 'saldo'] */
/** @var bool $cerrado true si es un mes pasado (el saldo es el de cierre) */

use common\models\Movimiento;

$cerrado = $cerrado ?? false;
$conSigno = function ($monto) {
    return ($monto < 0 ? '- ' : '') . Movimiento::formatoMonto(abs($monto));
};
?>
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small">Saldo anterior</div>
                <div class="fs-5 fw-semibold <?= $totales['anterior'] < 0 ? 'text-danger' : 'text-body' ?>">
                    <?= $conSigno($totales['anterior']) ?>
                </div>            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-success h-100">
            <div class="card-body">
                <div class="text-muted small">Ingresos del mes</div>
                <div class="fs-5 fw-semibold text-success">+ <?= Movimiento::formatoMonto($totales['ingresos']) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-danger h-100">
            <div class="card-body">
                <div class="text-muted small">Egresos del mes</div>
                <div class="fs-5 fw-semibold text-danger">- <?= Movimiento::formatoMonto($totales['egresos']) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card h-100 <?= $totales['saldo'] < 0 ? 'border-danger' : 'border-primary' ?>">
            <div class="card-body">
                <div class="text-muted small"><?= $cerrado ? 'Saldo al cierre' : 'Saldo actual' ?></div>
                <div class="fs-5 fw-semibold <?= $totales['saldo'] < 0 ? 'text-danger' : 'text-primary' ?>">
                    <?= $conSigno($totales['saldo']) ?>
                </div>
                <div class="small text-muted">del mes: <?= $conSigno($totales['saldo_mes']) ?></div>
            </div>
        </div>
    </div>
</div>
