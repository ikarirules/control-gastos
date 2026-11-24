<?php

/** @var yii\web\View $this */
/** @var common\models\Categoria[] $categorias */
/** @var array $montos categoria_id => monto estimado */
/** @var array $errores categoria_id => mensaje */
/** @var array $sugerencias Presupuesto::sugerencias() */
/** @var array $gastado categoria_id => gastado este mes */
/** @var array $proyeccion Presupuesto::proyeccion() */

use common\models\Movimiento;
use common\models\Prestamo;
use yii\bootstrap5\Html;

$this->title = 'Proyección';
$mesSug = $sugerencias['meses'];
$this->registerJs(<<<'JS'
document.querySelectorAll('.usar-sugerencia').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var input = document.getElementById(btn.dataset.target);
        input.value = btn.dataset.monto;
        input.focus();
    });
});
JS);
?>
<div class="proyeccion-index">
    <div class="mb-3">
        <h1 class="h3 m-0">Proyección de <?= Prestamo::nombreMes(Prestamo::hoy()) ?></h1>
        <div class="small text-muted">Cuánto pensás gastar este mes, sumado a las cuotas y los pagos fijos</div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <?= $this->render('_resumen', ['proyeccion' => $proyeccion]) ?>
        </div>
        <div class="col-lg-6">
            <div class="card h-100"><div class="card-body small">
                <div class="d-flex justify-content-between"><span>Saldo actual</span><strong><?= Movimiento::formatoMonto($proyeccion['saldo']) ?></strong></div>
                <div class="d-flex justify-content-between text-danger"><span>− Cuotas y pagos fijos sin pagar</span><span><?= Movimiento::formatoMonto($proyeccion['falta_cuotas_fijos']) ?></span></div>
                <div class="d-flex justify-content-between text-danger"><span>− Lo que queda de gastos variables</span><span><?= Movimiento::formatoMonto($proyeccion['falta_variables']) ?></span></div>
                <hr class="my-2">
                <div class="d-flex justify-content-between fs-6">
                    <span>Saldo estimado al cierre</span>
                    <strong class="<?= $proyeccion['saldo_cierre'] < 0 ? 'text-danger' : 'text-success' ?>">
                        <?= ($proyeccion['saldo_cierre'] < 0 ? '- ' : '') . Movimiento::formatoMonto(abs($proyeccion['saldo_cierre'])) ?>
                    </strong>
                </div>
                <div class="text-muted mt-2">
                    Si en una categoría ya gastaste más de lo estimado, se toma lo gastado (no resta más).
                </div>
            </div></div>
        </div>
    </div>

    <?= Html::beginForm(['index'], 'post') ?>
    <div class="card mb-4">
        <div class="card-header">
            <span class="fw-semibold">Estimados por categoría</span>
            <span class="small text-muted ms-2">
                <?= $mesSug
                    ? "Sugerencia = promedio de tus últimos $mesSug mes" . ($mesSug === 1 ? '' : 'es') . ' (sin cuotas ni pagos fijos)'
                    : 'Las sugerencias aparecen cuando tengas meses anteriores cargados' ?>
            </span>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Categoría</th>
                        <th class="text-end">Gastado este mes</th>
                        <th class="text-end">Sugerencia</th>
                        <th style="width: 210px;">Estimado por mes</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($categorias as $c):
                    $id = (int) $c->id;
                    $inputId = 'monto-' . $id;
                    $sug = $sugerencias['promedios'][$id] ?? null;
                    $valor = $montos[$id] ?? '';
                ?>
                    <tr>
                        <td><?= Html::encode($c->nombre) ?></td>
                        <td class="text-end text-nowrap <?= empty($gastado[$id]) ? 'text-muted' : '' ?>">
                            <?= Movimiento::formatoMonto($gastado[$id] ?? 0) ?>
                        </td>
                        <td class="text-end text-nowrap">
                            <?php if ($sug): ?>
                                <?= Movimiento::formatoMonto($sug) ?>
                                <button type="button" class="btn btn-sm btn-link p-0 ms-1 usar-sugerencia"
                                        data-target="<?= $inputId ?>" data-monto="<?= round($sug) ?>">usar</button>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="input-group input-group-sm has-validation">
                                <span class="input-group-text">$</span>
                                <?= Html::input('number', "monto[$id]", $valor === '' ? '' : $valor + 0, [
                                    'id' => $inputId,
                                    'class' => 'form-control' . (isset($errores[$id]) ? ' is-invalid' : ''),
                                    'step' => '0.01', 'min' => '0', 'placeholder' => 'sin estimar',
                                ]) ?>
                                <?php if (isset($errores[$id])): ?>
                                    <div class="invalid-feedback"><?= Html::encode($errores[$id]) ?></div>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span class="small text-muted">Dejá vacío o en 0 las categorías que no querés estimar (ej.: Sueldo).</span>
            <?= Html::submitButton('Guardar estimados', ['class' => 'btn btn-primary']) ?>
        </div>
    </div>
    <?= Html::endForm() ?>

    <?= $this->render('_seguimiento', ['seguimiento' => $proyeccion['seguimiento']]) ?>
</div>
