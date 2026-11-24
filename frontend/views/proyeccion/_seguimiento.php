<?php

/** @var yii\web\View $this */
/** @var array $seguimiento Presupuesto::seguimiento() */

use common\models\Movimiento;
use common\models\Prestamo;
use yii\bootstrap5\Html;

$s = $seguimiento;
$avance = round($s['avance'] * 100);
$estados = [
    'bien' => ['bg-success', 'text-success', 'vas bien'],
    'rapido' => ['bg-warning', 'text-warning', 'vas rápido'],
    'pasado' => ['bg-danger', 'text-danger', 'te pasaste'],
];
?>
<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span>
            <span class="fw-semibold">Gastos variables de <?= Prestamo::nombreMes(Prestamo::hoy()) ?></span>
            <span class="small text-muted ms-1">· va el <?= $avance ?>% del mes</span>
        </span>
        <?= Html::a('Editar estimados', ['proyeccion/index'], ['class' => 'small']) ?>
    </div>
    <?php if (!$s['filas']): ?>
        <div class="card-body text-muted">
            Cargá cuánto pensás gastar por mes en súper, combustible, verdulería... y acá vas a ver cómo venís.
            <?= Html::a('Cargar estimados', ['proyeccion/index']) ?>
        </div>
    <?php else: ?>
        <ul class="list-group list-group-flush">
            <?php foreach ($s['filas'] as $fila):
                [$barra, $texto, $leyenda] = $estados[$fila['estado']];
                $ancho = min(100, round($fila['porcentaje'] * 100));
            ?>
                <li class="list-group-item">
                    <div class="d-flex flex-wrap justify-content-between small mb-1 gap-2">
                        <span>
                            <strong><?= Html::encode($fila['categoria']) ?></strong>
                            <span class="<?= $texto ?> ms-1">· <?= $leyenda ?></span>
                        </span>
                        <span class="text-nowrap">
                            <?= Movimiento::formatoMonto($fila['gastado']) ?>
                            <span class="text-muted">de <?= Movimiento::formatoMonto($fila['estimado']) ?></span>
                            (<?= round($fila['porcentaje'] * 100) ?>%)
                        </span>
                    </div>
                    <div class="progress position-relative" style="height: 8px;">
                        <div class="progress-bar <?= $barra ?>" style="width: <?= $ancho ?>%"></div>
                        <div class="position-absolute top-0 bottom-0 border-start border-2 border-light opacity-75"
                             style="left: <?= $avance ?>%" title="Hoy (<?= $avance ?>% del mes)"></div>
                    </div>
                    <?php if ($fila['gastado'] > 0 && $fila['estado'] !== 'bien'): ?>
                        <div class="small text-muted mt-1">
                            A este ritmo terminás el mes en <?= Movimiento::formatoMonto($fila['ritmo']) ?>
                        </div>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
            <li class="list-group-item d-flex justify-content-between small fw-semibold">
                <span>Total variables</span>
                <span>
                    <?= Movimiento::formatoMonto($s['gastado']) ?>
                    <span class="text-muted fw-normal">de <?= Movimiento::formatoMonto($s['estimado']) ?>
                    · queda <?= Movimiento::formatoMonto($s['falta']) ?></span>
                </span>
            </li>
        </ul>
    <?php endif; ?>
</div>
