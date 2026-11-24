<?php

/** @var yii\web\View $this */
/** @var common\models\Categoria $model */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

?>
<div class="categoria-form card shadow-sm" style="max-width: 480px;">
    <div class="card-body p-4">
        <?php $form = ActiveForm::begin(['id' => 'categoria-form']); ?>

        <?= $form->field($model, 'nombre')->textInput(['maxlength' => true, 'autofocus' => true]) ?>

        <div class="d-flex gap-2">
            <?= Html::submitButton('Guardar', ['class' => 'btn btn-primary']) ?>
            <?= Html::a('Cancelar', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>
