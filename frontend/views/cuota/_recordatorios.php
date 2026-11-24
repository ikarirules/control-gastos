<?php

/** @var yii\web\View $this */
/** @var array $pendientes grupos vencidos sin pagar (PagoCuota::pendientes()) */

use common\models\Movimiento;
use common\models\Prestamo;
use yii\bootstrap5\Html;

if (!$pendientes) {
    return;
}
?>
<div class="card border-warning mb-4">
    <div class="card-header bg-warning-subtle fw-semibold">
        &#9888; ¿Ya pagaste? <span class="fw-normal small">Estos vencimientos ya pasaron</span>
    </div>
    <ul class="list-group list-group-flush">
        <?php foreach ($pendientes as $g): ?>
            <li class="list-group-item d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <div>
                        <strong><?= Html::encode($g['nombre']) ?></strong>
                        <?php if (!empty($g['fijo'])): ?><span class="badge text-bg-secondary ms-1">Pago fijo</span><?php endif; ?>
                        · <?= Prestamo::nombreMes(new DateTimeImmutable($g['mes'] . '-01')) ?>
                        <span class="text-danger small ms-1">venció el <?= $g['vence']->format('d/m') ?></span>
                    </div>
                    <div class="small text-muted"><?= Html::encode(implode(', ', $g['items'])) ?></div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <?= Html::beginForm(['cuota/pagar'], 'post', ['class' => 'd-flex align-items-center gap-2']) ?>
                        <?= Html::hiddenInput('mes', $g['mes']) ?>
                        <?= Html::hiddenInput('grupo', $g['grupo']) ?>
                        <?= Html::hiddenInput('egreso', 1) ?>
                        <?php if (!empty($g['fijo'])): ?>
                            <div class="input-group input-group-sm" style="width: 150px;" title="Monto estimado: podés cambiarlo por el real">
                                <span class="input-group-text">$</span>
                                <?= Html::input('number', 'monto', round($g['total'], 2), [
                                    'class' => 'form-control', 'step' => '0.01', 'min' => '0.01', 'required' => true,
                                ]) ?>
                            </div>
                            <?= Html::submitButton('Sí, lo pagué', [
                                'class' => 'btn btn-sm btn-success',
                                'data-confirm' => 'Se registrará el egreso en la caja con el monto indicado. ¿Continuar?',
                            ]) ?>
                        <?php else: ?>
                            <span class="fw-semibold text-nowrap me-1"><?= Movimiento::formatoMonto($g['total']) ?></span>
                            <?= Html::submitButton('Sí, lo pagué', [
                                'class' => 'btn btn-sm btn-success',
                                'data-confirm' => 'Se registrará un egreso de ' . Movimiento::formatoMonto($g['total']) . ' en la caja. ¿Continuar?',
                            ]) ?>
                        <?php endif; ?>
                    <?= Html::endForm() ?>
                    <?= Html::beginForm(['cuota/pagar'], 'post', ['class' => 'd-inline']) ?>
                        <?= Html::hiddenInput('mes', $g['mes']) ?>
                        <?= Html::hiddenInput('grupo', $g['grupo']) ?>
                        <?= Html::hiddenInput('egreso', 0) ?>
                        <?= Html::submitButton('Ya lo cargué', [
                            'class' => 'btn btn-sm btn-link text-muted p-0',
                            'title' => 'Marcar como pagado sin registrar egreso (si ya lo cargaste a mano)',
                            'data-confirm' => '¿Marcar como pagado sin registrar egreso?',
                        ]) ?>
                    <?= Html::endForm() ?>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
