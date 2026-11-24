<?php

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * Saldo que tenía una billetera el día que se cargó.
 *
 * @property int $id
 * @property int $billetera_id
 * @property string $fecha 'Y-m-d'
 * @property string $monto
 * @property int $created_at
 * @property int $updated_at
 *
 * @property Billetera $billetera
 */
class BilleteraSaldo extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%billetera_saldo}}';
    }

    public function behaviors()
    {
        return [TimestampBehavior::class];
    }

    public function rules()
    {
        return [
            [['billetera_id', 'fecha', 'monto'], 'required'],
            ['monto', 'number', 'min' => 0, 'max' => 999999999999],
            ['fecha', 'date', 'format' => 'php:Y-m-d'],
        ];
    }

    public function getBilletera()
    {
        return $this->hasOne(Billetera::class, ['id' => 'billetera_id']);
    }
}
