<?php

/** @var \yii\web\View $this */
/** @var string $content */

use common\models\PagoCuota;
use common\widgets\Alert;
use frontend\assets\AppAsset;
use yii\bootstrap5\Html;
use yii\bootstrap5\Nav;
use yii\bootstrap5\NavBar;

AppAsset::register($this);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" class="h-100" data-bs-theme="dark">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <?php $this->registerCsrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= Yii::getAlias('@web') ?>/favicon.svg?v=1">
    <?php $this->head() ?>
</head>
<body class="d-flex flex-column h-100">
<?php $this->beginBody() ?>

<header>
    <?php
    NavBar::begin([
        'brandLabel' => Yii::$app->name,
        'brandUrl' => Yii::$app->homeUrl,
        'options' => ['class' => 'navbar navbar-expand-md navbar-dark bg-dark fixed-top'],
    ]);
    if (!Yii::$app->user->isGuest) {
        $sinMarcar = count(PagoCuota::pendientesDelUsuario());
        echo Nav::widget([
            'options' => ['class' => 'navbar-nav me-auto mb-2 mb-md-0'],
            'items' => [
                ['label' => 'Inicio', 'url' => ['/site/index']],
                ['label' => 'Movimientos', 'url' => ['/movimiento/index'], 'active' => Yii::$app->controller->id === 'movimiento' && Yii::$app->controller->action->id !== 'historial'],
                ['label' => 'Historial', 'url' => ['/movimiento/historial']],
                ['label' => 'Cuotas', 'url' => ['/cuota/index'], 'active' => Yii::$app->controller->id === 'cuota'],
                ['label' => 'Proyección', 'url' => ['/proyeccion/index']],
                ['label' => 'Billeteras', 'url' => ['/billetera/index'], 'active' => Yii::$app->controller->id === 'billetera'],
                [
                    'label' => 'Ajustes',
                    'active' => in_array(Yii::$app->controller->id, ['categoria', 'medio-pago', 'pago-fijo'], true),
                    'items' => [
                        ['label' => 'Categorías', 'url' => ['/categoria/index']],
                        ['label' => 'Medios de pago', 'url' => ['/medio-pago/index']],
                        ['label' => 'Pagos fijos', 'url' => ['/pago-fijo/index']],
                    ],
                ],
            ],
        ]);
        // Campana de recordatorios: cantidad de pagos vencidos sin marcar
        $campana = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">'
            . '<path d="M8 16a2 2 0 0 0 2-2H6a2 2 0 0 0 2 2zm.995-14.901a1 1 0 1 0-1.99 0A5.002 5.002 0 0 0 3 6c0 1.098-.5 6-2 7h14c-1.5-1-2-5.902-2-7 0-2.42-1.72-4.44-4.005-4.901z"/>'
            . '</svg>';
        $titulo = $sinMarcar
            ? "Recordatorios: $sinMarcar pago" . ($sinMarcar === 1 ? '' : 's') . ' sin marcar'
            : 'Recordatorios: estás al día';
        echo Html::beginTag('div', ['class' => 'd-flex align-items-center ms-auto gap-1']);
        echo Html::a(
            $campana . ($sinMarcar
                ? Html::tag('span', $sinMarcar > 99 ? '99+' : $sinMarcar, ['class' => 'campana-numero badge rounded-pill bg-danger'])
                : ''),
            ['/recordatorio/index'],
            [
                'class' => 'campana nav-link position-relative px-2' . ($sinMarcar ? ' text-warning' : '')
                    . (Yii::$app->controller->id === 'recordatorio' ? ' active' : ''),
                'title' => $titulo,
                'aria-label' => $titulo,
            ]
        );
        echo Html::beginForm(['/site/logout'], 'post', ['class' => 'd-flex'])
            . Html::submitButton(
                'Cerrar sesión (' . Html::encode(Yii::$app->user->identity->username) . ')',
                ['class' => 'btn btn-link logout text-decoration-none']
            )
            . Html::endForm();
        echo Html::endTag('div');
    }
    NavBar::end();
    ?>
</header>

<main role="main" class="flex-shrink-0">
    <div class="container">
        <?= Alert::widget() ?>
        <?= $content ?>
    </div>
</main>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage();
