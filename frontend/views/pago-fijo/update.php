<?php

/** @var yii\web\View $this */
/** @var common\models\PagoFijo $model */
/** @var array $categorias */
/** @var array $mediosPago */

use yii\bootstrap5\Html;

$this->title = 'Modificar pago fijo: ' . $model->nombre;
?>
<h1 class="h3 mb-3"><?= Html::encode($this->title) ?></h1>

<?= $this->render('_form', compact('model', 'categorias', 'mediosPago')) ?>
