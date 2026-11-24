<?php

/** @var yii\web\View $this */
/** @var common\models\PagoFijo[] $fijos */

use common\models\Movimiento;
use common\models\PagoCuota;
use common\models\PagoFijo;
use common\models\Prestamo;
use yii\bootstrap5\Html;

$this->title = 'Pagos fijos';
$total = count($fijos);
$limite = PagoFijo::MAXIMO_POR_USUARIO;
$hoy = Prestamo::hoy();
$mes = $hoy->format('Y-m');
$pagados = PagoCuota::pagados();
$totalMensual = array_sum(array_map(function (PagoFijo $f) {
    return $f->activo ? (float) $f->monto : 0;
}, $fijos));
?>
<div class="pago-fijo-index" style="max-width: 960px;">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 m-0"><?= Html::encode($this->title) ?></h1>
            <div class="small <?= $total >= $limite ? 'text-danger' : 'text-muted' ?>">
                <?= $total ?> de <?= $limite ?> · total estimado por mes: <strong><?= Movimiento::formatoMonto($totalMensual) ?></strong>
            </div>
        </div>
        <?php if ($total < $limite): ?>
            <?= Html::a('Nuevo pago fijo', ['create'], ['class' => 'btn btn-primary']) ?>
        <?php else: ?>
            <span class="btn btn-primary disabled" title="Llegaste al máximo de <?= $limite ?> pagos fijos">Nuevo pago fijo</span>
        <?php endif; ?>
    </div>

    <div class="card">
        <?php if (!$fijos): ?>
            <div class="card-body text-muted">
                Todavía no cargaste pagos fijos. Cargá el alquiler, la luz, internet... y te los recuerdo cada mes cuando vencen.
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th class="text-end">Monto estimado</th>
                        <th>Vence</th>
                        <th>Categoría</th>
                        <th>Medio de pago</th>
                        <th><?= Prestamo::nombreMes($hoy) ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($fijos as $f):
                    $vence = \common\models\MedioPago::fechaEnMes($mes, $f->dia_vencimiento);
                ?>
                    <tr class="<?= $f->activo ? '' : 'text-muted' ?>">
                        <td>
                            <?= Html::encode($f->nombre) ?>
                            <?php if (!$f->activo): ?><span class="badge text-bg-secondary ms-1">Inactivo</span><?php endif; ?>
                        </td>
                        <td class="text-end text-nowrap"><?= Movimiento::formatoMonto($f->monto) ?></td>
                        <td class="text-nowrap">Día <?= (int) $f->dia_vencimiento ?></td>
                        <td><?= $f->categoria ? Html::encode($f->categoria->nombre) : '<span class="text-muted">' . PagoFijo::CATEGORIA . '</span>' ?></td>
                        <td><?= $f->medioPago ? Html::encode($f->medioPago->nombre) : '<span class="text-muted">—</span>' ?></td>
                        <td class="text-nowrap">
                            <?php if (!$f->activo): ?>
                                <span class="text-muted">—</span>
                            <?php elseif (!empty($pagados[$mes][$f->claveGrupo()])): ?>
                                <span class="badge bg-success">Pagado</span>
                            <?php elseif ($vence <= $hoy): ?>
                                <span class="badge bg-danger">Falta confirmar</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">Vence el <?= $vence->format('d/m') ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end text-nowrap">
                            <?= Html::a('Editar', ['update', 'id' => $f->id], ['class' => 'btn btn-sm btn-outline-primary']) ?>
                            <?= Html::a('Eliminar', ['delete', 'id' => $f->id], [
                                'class' => 'btn btn-sm btn-outline-danger',
                                'data-method' => 'post',
                                'data-confirm' => '¿Eliminar el pago fijo "' . $f->nombre . '"? Los egresos ya cargados quedan en la caja.',
                            ]) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
