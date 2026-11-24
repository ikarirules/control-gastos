<?php

/** @var yii\web\View $this */

use common\models\Movimiento;
use common\models\PagoCuota;
use common\models\PagoFijo;
use common\models\Presupuesto;
use common\models\Prestamo;
use yii\bootstrap5\Html;

$this->title = Yii::$app->name;

// Solo el mes actual: el día 1 arranca de cero (lo anterior queda en el historial)
$totales = Movimiento::caja();
$ultimos = Movimiento::delMes()
    ->with('categoria')
    ->orderBy(['fecha' => SORT_DESC, 'id' => SORT_DESC])
    ->limit(5)
    ->all();

// Proyección con las cuotas: mes actual + próximos 6
$hoy = Prestamo::hoy();
$prestamos = Prestamo::delUsuario()->with('medioPago')->all();
$calendario = Prestamo::calendario($prestamos, 0, 6, $hoy);
$pagados = PagoCuota::pagados();
$fijos = PagoFijo::activos();
$pendientes = PagoCuota::pendientes($prestamos, $pagados, $hoy, $fijos);
$vencimientos = Prestamo::proximosVencimientos($prestamos, 45, $hoy, $pagados, $fijos);

// A pagar este mes (cuotas + pagos fijos): lo ya pagado ya está descontado del saldo por su egreso
$mesActual = $hoy->format('Y-m');
$cuotasMes = $calendario[$mesActual]['total'];
$fijosMes = 0.0;
$aPagarMes = 0.0;
$faltaPagarMes = 0.0;
foreach (PagoCuota::grupos($prestamos, $fijos, $mesActual) as $g) {
    $aPagarMes += $g['total'];
    if (!empty($g['fijo'])) {
        $fijosMes += $g['total'];
    }
    if (!PagoCuota::estaPagado($pagados, $mesActual, $g['grupo'])) {
        $faltaPagarMes += $g['total'];
    }
}
$saldoLibre = $totales['saldo'] - $faltaPagarMes;
$proyeccion = Presupuesto::proyeccion();
$proximos = array_slice($calendario, 1, null, true);
$maxProximo = max(array_merge([1], array_column($proximos, 'total')));
?>
<div class="site-index">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="h3 m-0">Hola, <?= Html::encode(Yii::$app->user->identity->username) ?></h1>
        <div class="d-flex gap-2">
            <?= Html::a('&#8593; Ingreso', ['movimiento/create', 'tipo' => Movimiento::TIPO_INGRESO], ['class' => 'btn btn-success']) ?>
            <?= Html::a('&#8595; Egreso', ['movimiento/create', 'tipo' => Movimiento::TIPO_EGRESO], ['class' => 'btn btn-danger']) ?>
        </div>
    </div>

    <?= $this->render('//cuota/_recordatorios', ['pendientes' => $pendientes]) ?>

    <div class="d-flex justify-content-between align-items-baseline mb-2">
        <span class="text-muted small">Caja de <?= Prestamo::nombreMes($hoy) ?> · arranca con el saldo anterior cada día 1</span>
        <?= Html::a('Ver historial', ['movimiento/historial'], ['class' => 'small']) ?>
    </div>
    <?= $this->render('//movimiento/_totales', ['totales' => $totales]) ?>

    <div class="row g-3 mb-4">
        <div class="col-lg-4">
            <div class="card h-100"><div class="card-body">
                <div class="text-muted small">A pagar en <?= Prestamo::nombreMes($hoy) ?></div>
                <div class="fs-4 fw-semibold text-warning-emphasis"><?= Movimiento::formatoMonto($aPagarMes) ?></div>
                <div class="small text-muted mb-1">
                    <?= Html::a('Cuotas', ['cuota/index']) ?> <?= Movimiento::formatoMonto($cuotasMes) ?>
                    · <?= Html::a('Pagos fijos', ['pago-fijo/index']) ?> <?= Movimiento::formatoMonto($fijosMes) ?>
                </div>
                <div class="small">
                    <span class="text-success">Pagado <?= Movimiento::formatoMonto($aPagarMes - $faltaPagarMes) ?></span>
                    · <span class="<?= $faltaPagarMes > 0 ? 'text-danger' : 'text-muted' ?>">Falta <?= Movimiento::formatoMonto($faltaPagarMes) ?></span>
                </div>
            </div></div>
        </div>
        <div class="col-lg-4">
            <?= $this->render('//proyeccion/_resumen', ['proyeccion' => $proyeccion]) ?>
        </div>
        <div class="col-lg-4">
            <div class="card h-100 <?= $saldoLibre < 0 ? 'border-danger' : 'border-success' ?>"><div class="card-body">
                <div class="text-muted small">Saldo libre (saldo actual − lo que falta pagar)</div>
                <div class="fs-4 fw-semibold <?= $saldoLibre < 0 ? 'text-danger' : 'text-success' ?>">
                    <?= ($saldoLibre < 0 ? '- ' : '') . Movimiento::formatoMonto(abs($saldoLibre)) ?>
                </div>
                <div class="small text-muted">Lo que te queda después de pagar lo que falta de este mes</div>
            </div></div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-semibold">Próximos vencimientos <span class="small text-muted fw-normal">· 45 días</span></span>
            <?= Html::a('Medios de pago', ['medio-pago/index'], ['class' => 'small']) ?>
        </div>
        <ul class="list-group list-group-flush">
            <?php if (!$vencimientos): ?>
                <li class="list-group-item text-muted">No hay cuotas por vencer en los próximos 45 días.</li>
            <?php endif; ?>
            <?php foreach ($vencimientos as $v):
                $dias = (int) $hoy->diff($v['vence'])->format('%a');
            ?>
                <li class="list-group-item d-flex justify-content-between align-items-center gap-2">
                    <div>
                        <div>
                            <span class="fw-semibold"><?= $v['vence']->format('d/m') ?></span>
                            · <?= Html::encode($v['nombre']) ?>
                            <?php if ($dias <= 5): ?>
                                <span class="badge bg-danger ms-1"><?= $dias === 0 ? 'hoy' : "en $dias día" . ($dias === 1 ? '' : 's') ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="small text-muted"><?= Html::encode(implode(', ', $v['items'])) ?></div>
                    </div>
                    <span class="fw-semibold text-nowrap"><?= Movimiento::formatoMonto($v['total']) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">Cuotas de los próximos meses</span>
                    <?= Html::a('Calendario completo', ['cuota/index'], ['class' => 'small']) ?>
                </div>
                <ul class="list-group list-group-flush">
                    <?php foreach ($proximos as $mes): ?>
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between small mb-1">
                                <span><?= Prestamo::nombreMes($mes['fecha']) ?>
                                    <span class="text-muted">· <?= count($mes['items']) ?> cuota<?= count($mes['items']) === 1 ? '' : 's' ?></span>
                                </span>
                                <span class="fw-semibold"><?= Movimiento::formatoMonto($mes['total']) ?></span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-warning" style="width: <?= round($mes['total'] / $maxProximo * 100) ?>%"></div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">Últimos movimientos</span>
                    <?= Html::a('Ver todos', ['movimiento/index'], ['class' => 'small']) ?>
                </div>
                <ul class="list-group list-group-flush">
                    <?php if (!$ultimos): ?>
                        <li class="list-group-item text-muted">Todavía no hay movimientos este mes.</li>
                    <?php endif; ?>
                    <?php foreach ($ultimos as $m): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <div><?= Html::encode($m->motivo) ?></div>
                                <div class="small text-muted">
                                    <?= date('d/m/Y H:i', strtotime($m->fecha)) ?> · <?= Html::encode($m->categoria->nombre) ?>
                                </div>
                            </div>
                            <span class="fw-semibold text-nowrap <?= $m->esIngreso() ? 'text-success' : 'text-danger' ?>">
                                <?= ($m->esIngreso() ? '+ ' : '- ') . Movimiento::formatoMonto($m->monto) ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <?= $this->render('//proyeccion/_seguimiento', ['seguimiento' => $proyeccion['seguimiento']]) ?>
    </div>
</div>
