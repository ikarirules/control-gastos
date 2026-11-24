<?php

/** @var yii\web\View $this */
/** @var common\models\MedioPago $model */

use yii\bootstrap5\Html;

$this->title = 'Nuevo medio de pago';
?>
<h1 class="h3 mb-3"><?= Html::encode($this->title) ?></h1>

<?= $this->render('_form', ['model' => $model]) ?>
