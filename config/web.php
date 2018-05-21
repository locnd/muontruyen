<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';
$functions = require __DIR__ . '/functions.php';
$config = [
    'id' => 'basic',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'components' => [
        'request' => [
            'enableCsrfValidation' => true,
            'cookieValidationKey' => 'secret-l2o4c0n7g1u9y8e8n-iragon',
            'baseUrl' => '',
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => true,
        ],
        'errorHandler' => [
            'errorAction' => 'front/home/error',
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            'useFileTransport' => false,
            'transport' => [
                'class' => 'Swift_SmtpTransport',
                'host' => 'smtp.gmail.com',
                'username' => 'loc.nd247@gmail.com',
                'password' => 'GmTaranga13',
                'port' => '587',
                'encryption' => 'tls',
            ],
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,

        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            //'suffix' => '.html',
            'rules' => [
                'api/v1/<action:\w+>' => 'apiv1/<action>',
                'ajax/<action:\w+>' => 'ajax/<action>',

                'admin' => 'admin/dashboard/index',
                'admin/login' => 'admin/dashboard/login',
                'admin/logout' => 'admin/dashboard/logout',
                'admin/<controller:\w+>' => 'admin/<controller>/index',
                'admin/<controller:\w+>/<action:\w+>' => 'admin/<controller>/<action>',
                'admin/<controller:\w+>/<action:\w+>/<id:\d+>' => 'admin/<controller>/<action>',

                '' => 'front/home/index',
                '<controller:\w+>/<action:\w+>' => 'front/<controller>/<action>',
                'login' => 'front/home/login',
                'logout' => 'front/home/logout',
                '<slug:\w+>' => 'front/book/view',
            ],
        ],

    ],
    'params' => $params,
    'timeZone' => 'Asia/Ho_Chi_Minh'
];

if (YII_ENV_DEV) {
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];
}

return $config;
