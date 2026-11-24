<?php

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var \common\models\LoginForm $model */

use yii\bootstrap5\Html;
use yii\bootstrap5\ActiveForm;

$this->title = 'Iniciar sesión';
?>
<div class="site-login card shadow-sm w-100" style="max-width: 380px;">
    <div class="card-body p-4">
        <h1 class="h4 mb-4 text-center"><?= Html::encode(Yii::$app->name) ?></h1>

        <?php $form = ActiveForm::begin(['id' => 'login-form']); ?>

            <?= $form->field($model, 'username')->textInput(['autofocus' => true])->label('Usuario') ?>

            <?= $form->field($model, 'password')->passwordInput()->label('Contraseña') ?>

            <?= $form->field($model, 'rememberMe')->checkbox()->label('Recordarme') ?>

            <?= Html::submitButton('Ingresar', ['class' => 'btn btn-primary w-100', 'name' => 'login-button']) ?>

        <?php ActiveForm::end(); ?>
    </div>
</div>
