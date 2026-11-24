<?php

/** @var yii\web\View $this */
/** @var common\models\Movimiento $model */
/** @var array $categorias */
/** @var array $mediosPago */

use common\models\Movimiento;
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

?>
<div class="movimiento-form card shadow-sm" style="max-width: 560px;">
    <div class="card-body p-4">
        <?php $form = ActiveForm::begin(['id' => 'movimiento-form']); ?>

        <div class="mb-3">
            <label class="form-label d-block"><?= $model->getAttributeLabel('tipo') ?></label>
            <div class="btn-group w-100" role="group">
                <?= Html::radio('Movimiento[tipo]', $model->tipo === Movimiento::TIPO_INGRESO, [
                    'value' => Movimiento::TIPO_INGRESO, 'id' => 'tipo-ingreso', 'class' => 'btn-check',
                ]) ?>
                <label class="btn btn-outline-success" for="tipo-ingreso">&#8593; Ingreso (entra plata)</label>

                <?= Html::radio('Movimiento[tipo]', $model->tipo === Movimiento::TIPO_EGRESO, [
                    'value' => Movimiento::TIPO_EGRESO, 'id' => 'tipo-egreso', 'class' => 'btn-check',
                ]) ?>
                <label class="btn btn-outline-danger" for="tipo-egreso">&#8595; Egreso (sale plata)</label>
            </div>
            <?= Html::error($model, 'tipo', ['class' => 'text-danger small mt-1']) ?>
        </div>

        <?= $form->field($model, 'monto')->input('number', [
            'step' => '0.01', 'min' => '0.01', 'placeholder' => '0', 'autofocus' => true,
        ]) ?>

        <?= $form->field($model, 'motivo')->textInput([
            'maxlength' => true, 'placeholder' => 'Ej: Supermercado, sueldo, arriendo...',
        ]) ?>

        <?= $form->field($model, 'categoria_id')->dropDownList($categorias, ['prompt' => '-- Selecciona --'])
            ->hint(Html::a('Administrar categorías', ['categoria/index'])) ?>

        <?= $form->field($model, 'medio_pago_id')->dropDownList($mediosPago, ['prompt' => '-- Sin especificar --'])
            ->hint(Html::a('Administrar medios de pago', ['medio-pago/index'])) ?>

        <div class="mb-3">
            <label class="form-label"><?= $model->getAttributeLabel('fecha') ?></label>
            <input type="text" class="form-control" disabled value="<?= $model->isNewRecord
                ? 'Automática (' . date('d/m/Y H:i') . ')'
                : date('d/m/Y H:i', strtotime($model->fecha)) ?>">
        </div>

        <div class="d-flex gap-2">
            <?= Html::submitButton('Guardar', ['class' => 'btn btn-primary']) ?>
            <?= Html::a('Cancelar', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>
