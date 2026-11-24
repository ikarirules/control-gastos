<?php

namespace frontend\models;

use common\models\Billetera;
use common\models\BilleteraSaldo;
use Yii;
use yii\base\Model;

/**
 * Carga del saldo de una billetera (existente o nueva) con la fecha de hoy.
 */
class BilleteraForm extends Model
{
    public $billetera_id;
    public $nueva;
    public $monto;

    /** @var bool true si ya había un saldo cargado hoy y se reemplazó */
    public $reemplazado = false;

    public function rules()
    {
        return [
            [['nueva', 'monto'], 'trim'],
            ['monto', 'filter', 'filter' => function ($v) {
                return str_replace(',', '.', (string) $v);
            }],
            ['monto', 'required', 'message' => 'Ingresá el saldo.'],
            ['monto', 'number', 'min' => 0, 'max' => 999999999999, 'tooSmall' => 'El saldo no puede ser negativo.'],
            ['nueva', 'string', 'max' => 100],
            ['billetera_id', 'required', 'when' => function () {
                return $this->nueva === '' || $this->nueva === null;
            }, 'whenClient' => "function () {
                var nueva = document.getElementById('billeteraform-nueva');
                return !nueva || nueva.value.trim() === '';
            }", 'message' => 'Elegí una billetera o escribí el nombre de una nueva.'],
            ['billetera_id', 'exist', 'targetClass' => Billetera::class, 'targetAttribute' => ['billetera_id' => 'id'],
                'filter' => function ($q) {
                    $q->andWhere(['user_id' => Yii::$app->user->id]);
                }, 'when' => function () {
                    return $this->nueva === '' || $this->nueva === null;
                }],
        ];
    }

    public function attributeLabels()
    {
        return ['billetera_id' => 'Billetera', 'nueva' => 'Nueva billetera', 'monto' => 'Saldo actual'];
    }

    /**
     * Guarda el saldo con la fecha de hoy. Si se escribió una billetera nueva, la crea.
     * Si ya había un saldo de hoy para esa billetera, lo reemplaza.
     *
     * @return BilleteraSaldo|null
     */
    public function guardar()
    {
        if (!$this->validate()) {
            return null;
        }
        $userId = Yii::$app->user->id;
        $tx = Yii::$app->db->beginTransaction();
        try {
            if ($this->nueva !== '' && $this->nueva !== null) {
                $billetera = new Billetera(['user_id' => $userId, 'nombre' => $this->nueva]);
                if (!$billetera->save()) {
                    $this->addError('nueva', $billetera->getFirstError('nombre'));
                    $tx->rollBack();
                    return null;
                }
            } else {
                $billetera = Billetera::findOne(['id' => $this->billetera_id, 'user_id' => $userId]);
            }

            $hoy = date('Y-m-d');
            $saldo = BilleteraSaldo::findOne(['billetera_id' => $billetera->id, 'fecha' => $hoy]);
            $this->reemplazado = $saldo !== null;
            if (!$saldo) {
                $saldo = new BilleteraSaldo(['billetera_id' => $billetera->id, 'fecha' => $hoy]);
            }
            $saldo->monto = $this->monto;
            if (!$saldo->save()) {
                $this->addError('monto', $saldo->getFirstError('monto') ?: 'No se pudo guardar.');
                $tx->rollBack();
                return null;
            }
            $tx->commit();
            $this->billetera_id = $billetera->id;
            return $saldo;
        } catch (\Throwable $e) {
            $tx->rollBack();
            throw $e;
        }
    }
}
