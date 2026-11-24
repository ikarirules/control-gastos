<?php

/** @var yii\web\View $this */
/** @var array $pagados */
/** @var array $pendientes */
/** @var DateTimeImmutable $hoy */
/** @var common\models\Prestamo[] $prestamos */
/** @var float $totalPagado */
/** @var float $totalRestante */
/** @var array $calendario */
/** @var int $mesesAtras */
/** @var int $mesesAdelante */

use common\models\Movimiento;
use common\models\PagoCuota;
use common\models\Prestamo;
use yii\bootstrap5\Html;

$this->title = 'Cuotas';
$f = function ($monto) {
    return Movimiento::formatoMonto($monto);
};
$this->registerCss('
.cuota-index .flecha { display: inline-block; transition: transform .15s; }
.cuota-index [aria-expanded="true"] .flecha { transform: rotate(90deg); }
');
?>
<div class="cuota-index">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 m-0"><?= Html::encode($this->title) ?></h1>
            <div class="small text-muted">Préstamos, créditos y compras con tarjeta en cuotas</div>
        </div>
        <?= Html::a('Agregar cuota', ['create'], ['class' => 'btn btn-primary']) ?>
    </div>

    <?= $this->render('_recordatorios', ['pendientes' => $pendientes]) ?>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-primary h-100"><div class="card-body">
                <div class="text-muted small">Cuotas de <?= Prestamo::nombreMes($hoy) ?></div>
                <div class="fs-4 fw-semibold text-primary"><?= $f($calendario[$hoy->format('Y-m')]['total']) ?></div>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card border-success h-100"><div class="card-body">
                <div class="text-muted small">Pagado hasta hoy</div>
                <div class="fs-4 fw-semibold text-success"><?= $f($totalPagado) ?></div>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card border-danger h-100"><div class="card-body">
                <div class="text-muted small">Restante por pagar</div>
                <div class="fs-4 fw-semibold text-danger"><?= $f($totalRestante) ?></div>
            </div></div>
        </div>
    </div>

    <?php
    $activos = array_filter($prestamos, function ($p) use ($hoy) { return !$p->terminado($hoy); });
    $terminados = array_filter($prestamos, function ($p) use ($hoy) { return $p->terminado($hoy); });
    $terminanEsteMes = count(array_filter($activos, function ($p) use ($hoy) { return $p->terminaEsteMes($hoy); }));
    ?>
    <div class="card mb-4">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-activos" type="button" role="tab" aria-controls="tab-activos" aria-selected="true">
                        Activos <span class="badge text-bg-primary"><?= count($activos) ?></span>
                        <?php if ($terminanEsteMes): ?>
                            <span class="badge text-bg-warning" title="Terminan este mes"><?= $terminanEsteMes ?> terminan</span>
                        <?php endif; ?>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-terminados" type="button" role="tab" aria-controls="tab-terminados" aria-selected="false">
                        Terminados <span class="badge text-bg-secondary"><?= count($terminados) ?></span>
                    </button>
                </li>
            </ul>
        </div>
        <div class="tab-content">
        <?php foreach (['tab-activos' => $activos, 'tab-terminados' => $terminados] as $tabId => $lista): ?>
        <div class="tab-pane fade <?= $tabId === 'tab-activos' ? 'show active' : '' ?>" id="<?= $tabId ?>" role="tabpanel">
        <?php if (!$lista): ?>
            <div class="card-body text-muted">
                <?= $tabId === 'tab-activos'
                    ? ($prestamos ? 'No tenés cuotas activas.' : 'Todavía no cargaste ninguna cuota.')
                    : 'Todavía no terminaste ninguna cuota.' ?>
            </div>
        <?php else: ?>
            <ul class="list-group list-group-flush">
                <?php foreach ($lista as $p):
                    $pagadas = $p->cuotasPagadas($hoy);
                    $total = (int) $p->cantidad_cuotas;
                    $restantes = max(0, $total - $pagadas);
                    $porcentaje = $total > 0 ? min(100, round($pagadas / $total * 100)) : 0;
                    $terminado = $p->terminado($hoy);
                    $terminaEsteMes = $p->terminaEsteMes($hoy);
                ?>
                <li class="list-group-item py-3 <?= $terminaEsteMes ? 'border-start border-4 border-warning' : '' ?>">
                    <div class="d-flex flex-wrap justify-content-between gap-2">
                        <div>
                            <strong><?= Html::encode($p->nombre) ?></strong>
                            <?php if ($terminado): ?>
                                <span class="badge bg-success ms-1">Terminado en <?= Prestamo::nombreMes(new DateTimeImmutable($p->mesFinal() . '-01')) ?></span>
                            <?php elseif ($terminaEsteMes): ?>
                                <span class="badge text-bg-warning ms-1">&#127881; Termina este mes</span>
                            <?php endif; ?>
                            <div class="small text-muted">
                                desde <?= Prestamo::nombreMes($p->fechaInicio()) ?> ·
                                <?= $f($p->monto_cuota) ?> x <?= $total ?> cuotas ·
                                <?= $p->medioPago ? Html::encode($p->medioPago->nombre) : 'sin medio de pago' ?> ·
                                vence día <?= $p->diaVencimiento() ?>
                            </div>
                        </div>
                        <div class="d-flex gap-1 align-items-start">
                            <?= Html::a('Modificar', ['update', 'id' => $p->id], ['class' => 'btn btn-sm btn-outline-primary']) ?>
                            <?= Html::a('Eliminar', ['delete', 'id' => $p->id], [
                                'class' => 'btn btn-sm btn-outline-danger',
                                'data-method' => 'post',
                                'data-confirm' => '¿Eliminar "' . $p->nombre . '"?',
                            ]) ?>
                        </div>
                    </div>
                    <div class="progress my-2" style="height: 8px;">
                        <div class="progress-bar <?= $terminado ? 'bg-success' : '' ?>" style="width: <?= $porcentaje ?>%"></div>
                    </div>
                    <div class="d-flex flex-wrap gap-3 small">
                        <span><?= $pagadas ?> / <?= $total ?> cuotas pagadas (<?= $porcentaje ?>%)</span>
                        <span>Pagado: <?= $f($pagadas * (float) $p->monto_cuota) ?></span>
                        <span>Restante: <?= $f($restantes * (float) $p->monto_cuota) ?></span>
                        <span>Total: <?= $f($p->montoTotal()) ?></span>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        </div>
        <?php endforeach; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <span class="fw-semibold">Calendario de pagos por mes</span>
            <span class="small text-muted ms-2">últimos <?= $mesesAtras ?> y próximos <?= $mesesAdelante ?> meses</span>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr><th>Mes</th><th>Detalle</th><th class="text-end">Total del mes</th><th>Estado</th></tr>
                </thead>
                <tbody>
                <?php foreach ($calendario as $mes):
                    // Estado de cada cuota: pagada / vencida sin confirmar / por vencer
                    $estados = [];
                    foreach ($mes['items'] as $i => $item) {
                        $estados[$i] = PagoCuota::estaPagado($pagados, $mes['clave'], $item['grupo'])
                            ? 'pagada'
                            : ($item['vence'] <= $hoy ? 'vencida' : 'pendiente');
                    }
                    $cuenta = array_count_values($estados);
                ?>
                    <?php
                    $idDetalle = 'detalle-' . $mes['clave'];
                    $abierto = $mes['es_actual'];
                    // Resumen por medio de pago para la fila cerrada
                    $porMedio = [];
                    foreach ($mes['items'] as $item) {
                        $clave = $item['medio'] ?: 'Sin medio de pago';
                        $porMedio[$clave] = ($porMedio[$clave] ?? 0) + $item['monto'];
                    }
                    ?>
                    <tr class="<?= $mes['es_actual'] ? 'table-primary' : ($mes['es_pasado'] ? 'text-muted' : '') ?>">
                        <td class="text-nowrap"><?= Prestamo::nombreMes($mes['fecha']) ?></td>
                        <td class="small">
                            <?php if (!$mes['items']): ?>
                                —
                            <?php else: ?>
                                <button type="button"
                                        class="btn btn-sm btn-link p-0 text-decoration-none <?= $abierto ? '' : 'collapsed' ?>"
                                        data-bs-toggle="collapse" data-bs-target="#<?= $idDetalle ?>"
                                        aria-expanded="<?= $abierto ? 'true' : 'false' ?>" aria-controls="<?= $idDetalle ?>">
                                    <span class="flecha">&#9656;</span>
                                    <?= count($mes['items']) ?> producto<?= count($mes['items']) === 1 ? '' : 's' ?>
                                </button>
                                <div class="text-muted">
                                    <?= implode(' · ', array_map(function ($medio, $monto) use ($f) {
                                        return Html::encode($medio) . ' ' . $f($monto);
                                    }, array_keys($porMedio), $porMedio)) ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="text-end text-nowrap fw-semibold"><?= $f($mes['total']) ?></td>
                        <td class="text-nowrap">
                            <?php if (!$mes['items']): ?>
                                <span class="text-muted">—</span>
                            <?php elseif (!empty($cuenta['vencida'])): ?>
                                <span class="badge bg-danger">Falta confirmar pago</span>
                            <?php elseif (($cuenta['pagada'] ?? 0) === count($estados)): ?>
                                <span class="badge bg-success">Pagado</span>
                            <?php elseif (!empty($cuenta['pagada'])): ?>
                                <span class="badge bg-info text-dark">Pagado en parte</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">Pendiente</span>
                            <?php endif; ?>
                            <?php if ($mes['es_actual']): ?>
                                <div class="small text-primary">Mes actual</div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php if ($mes['items']): ?>
                    <tr class="collapse <?= $abierto ? 'show' : '' ?>" id="<?= $idDetalle ?>">
                        <td colspan="4" class="bg-body-tertiary p-0">
                            <table class="table table-sm mb-0 small bg-transparent">
                                <thead>
                                    <tr class="text-muted">
                                        <th class="ps-4">Producto</th>
                                        <th>Medio de pago</th>
                                        <th>Cuota</th>
                                        <th>Vence</th>
                                        <th class="text-end">Monto</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($mes['items'] as $i => $item): ?>
                                    <tr>
                                        <td class="ps-4"><?= Html::encode($item['nombre']) ?></td>
                                        <td>
                                            <?php if ($item['medio']): ?>
                                                <span class="badge text-bg-secondary"><?= Html::encode($item['medio']) ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">sin medio</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-nowrap"><?= $item['numero'] ?>/<?= $item['cantidad_cuotas'] ?></td>
                                        <td class="text-nowrap"><?= $item['vence']->format('d/m') ?></td>
                                        <td class="text-end text-nowrap"><?= $f($item['monto']) ?></td>
                                        <td class="text-nowrap">
                                            <?php if ($estados[$i] === 'pagada'): ?>
                                                <span class="text-success">&#10004; Pagada</span>
                                            <?php elseif ($estados[$i] === 'vencida'): ?>
                                                <span class="text-danger">&#9888; Sin confirmar</span>
                                            <?php else: ?>
                                                <span class="text-muted">Por vencer</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
