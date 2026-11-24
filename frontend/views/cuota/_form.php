<?php

/** @var yii\web\View $this */
/** @var common\models\Prestamo $model */
/** @var array $mediosPago */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

?>
<div class="cuota-form card shadow-sm" style="max-width: 560px;">
    <div class="card-body p-4">
        <?php $form = ActiveForm::begin(['id' => 'cuota-form']); ?>

        <?= $form->field($model, 'nombre')->textInput([
            'maxlength' => true, 'autofocus' => true, 'placeholder' => 'Ej: Moto Honda CB1, Visa - heladera',
        ]) ?>

        <?= $form->field($model, 'fecha_primera_cuota')->input('date') ?>

        <div class="row">
            <div class="col-sm-6">
                <?= $form->field($model, 'monto_cuota')->input('number', ['step' => '0.01', 'min' => '0.01']) ?>
            </div>
            <div class="col-sm-6">
                <?= $form->field($model, 'cantidad_cuotas')->input('number', ['step' => '1', 'min' => '1']) ?>
            </div>
        </div>

        <?= $form->field($model, 'medio_pago_id')->dropDownList($mediosPago, ['prompt' => '-- Sin especificar --'])
            ->hint('Las cuotas vencen el día configurado en el medio de pago; si no tiene, el día de la primera cuota. '
                . Html::a('Administrar medios de pago', ['medio-pago/index'])) ?>

        <div class="d-flex gap-2">
            <?= Html::submitButton($model->isNewRecord ? 'Agregar' : 'Guardar cambios', ['class' => 'btn btn-primary']) ?>
            <?= Html::a('Cancelar', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>
