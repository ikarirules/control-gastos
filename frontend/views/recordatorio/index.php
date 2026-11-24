<?php

/** @var yii\web\View $this */
/** @var DateTimeImmutable $hoy */
/** @var array $pendientes vencidos sin confirmar */
/** @var array $proximos vencen dentro de $dias días */
/** @var int $dias */

use common\models\Movimiento;
use yii\bootstrap5\Html;

$this->title = 'Recordatorios';
?>
<div class="recordatorio-index">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 m-0"><?= Html::encode($this->title) ?></h1>
            <div class="small text-muted">Cuotas y pagos fijos vencidos que todavía no marcaste como pagados</div>
        </div>
        <div class="d-flex gap-2">
            <?= Html::a('Pagos fijos', ['pago-fijo/index'], ['class' => 'btn btn-outline-secondary']) ?>
            <?= Html::a('Cuotas', ['cuota/index'], ['class' => 'btn btn-outline-secondary']) ?>
        </div>
    </div>

    <?php if ($pendientes): ?>
        <?= $this->render('//cuota/_recordatorios', ['pendientes' => $pendientes]) ?>
    <?php else: ?>
        <div class="card border-success mb-4">
            <div class="card-body text-success">
                &#10004; Estás al día: no hay pagos vencidos sin confirmar.
            </div>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header fw-semibold">
            Próximos vencimientos <span class="small text-muted fw-normal">· <?= $dias ?> días</span>
        </div>
        <ul class="list-group list-group-flush">
            <?php if (!$proximos): ?>
                <li class="list-group-item text-muted">No hay nada por vencer en los próximos <?= $dias ?> días.</li>
            <?php endif; ?>
            <?php foreach ($proximos as $v):
                $faltan = (int) $hoy->diff($v['vence'])->format('%a');
            ?>
                <li class="list-group-item d-flex justify-content-between align-items-center gap-2">
                    <div>
                        <div>
                            <span class="fw-semibold"><?= $v['vence']->format('d/m') ?></span>
                            · <?= Html::encode($v['nombre']) ?>
                            <?php if (!empty($v['fijo'])): ?><span class="badge text-bg-secondary ms-1">Pago fijo</span><?php endif; ?>
                            <?php if ($faltan <= 5): ?>
                                <span class="badge bg-danger ms-1"><?= $faltan === 0 ? 'hoy' : "en $faltan día" . ($faltan === 1 ? '' : 's') ?></span>
                            <?php else: ?>
                                <span class="small text-muted ms-1">en <?= $faltan ?> días</span>
                            <?php endif; ?>
                        </div>
                        <div class="small text-muted"><?= Html::encode(implode(', ', $v['items'])) ?></div>
                    </div>
                    <span class="fw-semibold text-nowrap"><?= Movimiento::formatoMonto($v['total']) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
