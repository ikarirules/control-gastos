<?php

namespace frontend\models;

use common\models\LoginForm as BaseLoginForm;

/**
 * Login form del frontend con captcha.
 */
class LoginForm extends BaseLoginForm
{
    public $verifyCode;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        // El captcha se valida antes que la contraseña: si falla,
        // validatePassword() no se ejecuta (verifica hasErrors()).
        return array_merge([
            ['verifyCode', 'required', 'message' => 'Ingrese el código de verificación.'],
            ['verifyCode', 'captcha', 'captchaAction' => 'site/captcha', 'message' => 'El código de verificación es incorrecto.'],
        ], parent::rules());
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'verifyCode' => 'Código de verificación',
        ];
    }
}
