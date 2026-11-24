<?php

/** @var yii\web\View $this */
/** @var common\models\PagoFijo $model */
/** @var array $categorias */
/** @var array $mediosPago */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

?>
<div class="pago-fijo-form card shadow-sm" style="max-width: 520px;">
    <div class="card-body p-4">
        <?php $form = ActiveForm::begin(['id' => 'pago-fijo-form']); ?>

        <?= $form->field($model, 'nombre')->textInput([
            'maxlength' => true, 'autofocus' => true, 'placeholder' => 'Ej: Alquiler, Luz, Internet, Gimnasio',
        ]) ?>

        <div class="row">
            <div class="col-sm-7">
                <?= $form->field($model, 'monto', [
                    'inputTemplate' => '<div class="input-group"><span class="input-group-text">$</span>{input}</div>',
                ])->input('number', ['step' => '0.01', 'min' => '0.01', 'placeholder' => '0,00'])
                    ->hint('Aproximado. Al pagarlo podés poner el monto real.') ?>
            </div>
            <div class="col-sm-5">
                <?= $form->field($model, 'dia_vencimiento')->input('number', ['min' => 1, 'max' => 31, 'placeholder' => 'Ej: 10'])
                    ->hint('Día del mes (estimado)') ?>
            </div>
        </div>

        <?= $form->field($model, 'categoria_id')->dropDownList($categorias, ['prompt' => 'Gasto fijo (por defecto)']) ?>

        <?= $form->field($model, 'medio_pago_id')->dropDownList($mediosPago, ['prompt' => 'Sin medio de pago']) ?>

        <?= $form->field($model, 'activo')->checkbox()
            ->hint('Si lo desactivás deja de recordarse, pero queda guardado.') ?>

        <div class="d-flex gap-2">
            <?= Html::submitButton('Guardar', ['class' => 'btn btn-primary']) ?>
            <?= Html::a('Cancelar', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>
