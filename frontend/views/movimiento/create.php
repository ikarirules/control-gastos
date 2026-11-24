<?php

/** @var yii\web\View $this */
/** @var common\models\Movimiento $model */
/** @var array $categorias */

use yii\bootstrap5\Html;

$this->title = 'Nuevo movimiento';
?>
<h1 class="h3 mb-3"><?= Html::encode($this->title) ?></h1>

<?= $this->render('_form', ['model' => $model, 'categorias' => $categorias, 'mediosPago' => $mediosPago]) ?>
