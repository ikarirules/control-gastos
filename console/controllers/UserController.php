<?php

namespace console\controllers;

use common\models\User;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

/**
 * Administración de usuarios desde consola.
 */
class UserController extends Controller
{
    /**
     * Crea un usuario activo.
     * Uso: php yii user/create <username> <email> <password>
     */
    public function actionCreate($username, $email, $password)
    {
        if (User::find()->where(['username' => $username])->orWhere(['email' => $email])->exists()) {
            $this->stderr("Ya existe un usuario con ese username o email.\n", Console::FG_RED);
            return ExitCode::DATAERR;
        }

        $user = new User();
        $user->username = $username;
        $user->email = $email;
        $user->status = User::STATUS_ACTIVE;
        $user->setPassword($password);
        $user->generateAuthKey();

        if (!$user->save()) {
            foreach ($user->getFirstErrors() as $error) {
                $this->stderr("$error\n", Console::FG_RED);
            }
            return ExitCode::DATAERR;
        }

        $this->stdout("Usuario '{$username}' creado (id {$user->id}).\n", Console::FG_GREEN);
        return ExitCode::OK;
    }
}
