<?php

$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php',
    require __DIR__ . '/params.php',
    require __DIR__ . '/params-local.php'
);

return [
    'id' => 'app-frontend',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'frontend\controllers',
    // Todo el sitio requiere sesión iniciada, excepto el login (y su captcha) y la página de error
    'as access' => [
        'class' => \yii\filters\AccessControl::class,
        'except' => ['site/login', 'site/captcha', 'site/error', 'debug/*', 'gii/*'],
        'rules' => [
            ['allow' => true, 'roles' => ['@']],
        ],
    ],
    // Paginador con botones de Bootstrap 5 en todas las tablas
    'container' => [
        'definitions' => [
            \yii\grid\GridView::class => [
                'pager' => ['class' => \yii\bootstrap5\LinkPager::class],
                'summaryOptions' => ['class' => 'small text-muted'],
                'layout' => "<div class=\"table-responsive\">{items}</div>\n"
                    . "<div class=\"d-flex flex-wrap justify-content-between align-items-center gap-2 mt-2 mb-4\">{summary}{pager}</div>",
            ],
            \yii\bootstrap5\LinkPager::class => [
                'listOptions' => ['class' => ['pagination', 'flex-wrap', 'mb-0']],
                'firstPageLabel' => '&laquo; Primera',
                'lastPageLabel' => 'Última &raquo;',
                'prevPageLabel' => '&lsaquo; Anterior',
                'nextPageLabel' => 'Siguiente &rsaquo;',
                'maxButtonCount' => 5,
            ],
        ],
    ],
    'components' => [
        'request' => [
            'csrfParam' => '_csrf-cg-frontend',
        ],
        'user' => [
            'identityClass' => 'common\models\User',
            'enableAutoLogin' => true,
            'identityCookie' => ['name' => '_identity-cg-frontend', 'httpOnly' => true],
        ],
        'session' => [
            // this is the name of the session cookie used for login on the frontend
            'name' => 'control-gastos-frontend',
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => \yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        /*
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
            ],
        ],
        */
    ],
    'params' => $params,
];
