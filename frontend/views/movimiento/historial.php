<?php

/** @var yii\web\View $this */
/** @var array $meses Movimiento::historial() */

use common\models\Movimiento;
use common\models\Prestamo;
use yii\bootstrap5\Html;
use yii\bootstrap5\LinkPager;
use yii\data\Pagination;

$this->title = 'Historial';
$actual = Movimiento::mesActual();
// 12 meses (un año) por página
$paginas = new Pagination(['totalCount' => count($meses), 'pageSize' => 12]);
$meses = array_slice($meses, $paginas->offset, $paginas->limit);
$f = function ($monto) {
    return Movimiento::formatoMonto(abs($monto));
};
?>
<div class="movimiento-historial">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 m-0">Historial</h1>
            <div class="small text-muted">Cada mes arranca el día 1 con el saldo que dejó el anterior</div>
        </div>
        <?= Html::a('Volver al mes actual', ['index'], ['class' => 'btn btn-outline-primary']) ?>
    </div>

    <div class="card">
        <?php if (!$meses): ?>
            <div class="card-body text-muted">Todavía no hay movimientos.</div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Mes</th>
                        <th class="text-end">Saldo anterior</th>
                        <th class="text-end">Ingresos</th>
                        <th class="text-end">Egresos</th>
                        <th class="text-end">Resultado del mes</th>
                        <th class="text-end">Saldo al cierre</th>
                        <th class="text-end">Movimientos</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($meses as $m): ?>
                    <tr class="<?= $m['mes'] === $actual ? 'table-primary' : '' ?>">
                        <td class="text-nowrap">
                            <?= Prestamo::nombreMes(new DateTimeImmutable($m['mes'] . '-01')) ?>
                            <?php if ($m['mes'] === $actual): ?>
                                <span class="badge text-bg-primary ms-1">Actual</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end text-nowrap <?= $m['anterior'] < 0 ? 'text-danger' : 'text-muted' ?>">
                            <?= ($m['anterior'] < 0 ? '- ' : '') . $f($m['anterior']) ?>
                        </td>
                        <td class="text-end text-nowrap text-success"><?= $f($m['ingresos']) ?></td>
                        <td class="text-end text-nowrap text-danger"><?= $f($m['egresos']) ?></td>
                        <td class="text-end text-nowrap <?= $m['saldo_mes'] < 0 ? 'text-danger' : 'text-success' ?>">
                            <?= ($m['saldo_mes'] < 0 ? '- ' : '+ ') . $f($m['saldo_mes']) ?>
                        </td>
                        <td class="text-end text-nowrap fw-semibold <?= $m['saldo'] < 0 ? 'text-danger' : 'text-primary' ?>">
                            <?= ($m['saldo'] < 0 ? '- ' : '') . $f($m['saldo']) ?>
                        </td>
                        <td class="text-end"><?= $m['cantidad'] ?></td>
                        <td class="text-end">
                            <?= Html::a('Ver', ['index', 'mes' => $m['mes']], ['class' => 'btn btn-sm btn-outline-secondary']) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
    <?php if ($paginas->pageCount > 1): ?>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-2 mb-4">
            <span class="small text-muted">Mostrando <strong><?= $paginas->offset + 1 ?>-<?= $paginas->offset + count($meses) ?></strong> de <strong><?= $paginas->totalCount ?></strong> meses</span>
            <?= LinkPager::widget(['pagination' => $paginas, 'options' => ['aria-label' => 'Páginas del historial']]) ?>
        </div>
    <?php endif; ?>
</div>
