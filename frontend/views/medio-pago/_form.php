<?php

/** @var yii\web\View $this */
/** @var common\models\MedioPago $model */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

?>
<div class="medio-pago-form card shadow-sm" style="max-width: 480px;">
    <div class="card-body p-4">
        <?php $form = ActiveForm::begin(['id' => 'medio-pago-form']); ?>

        <?= $form->field($model, 'nombre')->textInput([
            'maxlength' => true, 'autofocus' => true, 'placeholder' => 'Ej: Visa Galicia, Mercado Pago, Naranja X',
        ]) ?>

        <?= $form->field($model, 'dia_vencimiento')->input('number', ['min' => 1, 'max' => 31, 'placeholder' => 'Ej: 10'])
            ->hint('Día del mes en que vence (aproximado). Dejalo vacío si no vence, como el efectivo.') ?>

        <div class="d-flex gap-2">
            <?= Html::submitButton('Guardar', ['class' => 'btn btn-primary']) ?>
            <?= Html::a('Cancelar', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>
