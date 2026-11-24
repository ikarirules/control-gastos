<?php

namespace common\models;

use DateTimeImmutable;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * Medio de pago (efectivo, tarjeta, transferencia...). Cada usuario tiene los suyos.
 *
 * @property int $id
 * @property int $user_id
 * @property string $nombre
 * @property int|null $dia_vencimiento día del mes en que vence (aproximado)
 * @property int $created_at
 * @property int $updated_at
 *
 * @property Movimiento[] $movimientos
 * @property Prestamo[] $prestamos
 */
class MedioPago extends ActiveRecord
{
    /** Medios que recibe cada usuario nuevo: nombre => día de vencimiento */
    const POR_DEFECTO = [
        'Efectivo' => null,
        'Tarjeta de débito' => null,
        'Transferencia' => null,
        'Tarjeta de crédito' => 10,
    ];

    /** Cantidad máxima de medios de pago por usuario. */
    const MAXIMO_POR_USUARIO = 20;

    public static function tableName()
    {
        return '{{%medio_pago}}';
    }

    public function behaviors()
    {
        return [TimestampBehavior::class];
    }

    public function rules()
    {
        return [
            ['nombre', 'trim'],
            ['nombre', 'required'],
            ['nombre', 'string', 'max' => 100],
            [
                'nombre', 'unique',
                'targetAttribute' => ['user_id', 'nombre'],
                'message' => 'Ya tenés un medio de pago con ese nombre.',
            ],
            ['nombre', 'validarMaximo', 'when' => function () {
                return $this->isNewRecord;
            }],
            ['dia_vencimiento', 'default', 'value' => null],
            ['dia_vencimiento', 'integer', 'min' => 1, 'max' => 31],
        ];
    }

    public function validarMaximo($attribute)
    {
        if (static::limiteAlcanzado($this->user_id)) {
            $this->addError(
                $attribute,
                'Llegaste al máximo de ' . self::MAXIMO_POR_USUARIO . ' medios de pago. Eliminá o renombrá uno existente.'
            );
        }
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'nombre' => 'Nombre',
            'dia_vencimiento' => 'Día de vencimiento',
        ];
    }

    public function getMovimientos()
    {
        return $this->hasMany(Movimiento::class, ['medio_pago_id' => 'id']);
    }

    public function getPrestamos()
    {
        return $this->hasMany(Prestamo::class, ['medio_pago_id' => 'id']);
    }

    public function enUso()
    {
        return $this->getMovimientos()->exists() || $this->getPrestamos()->exists();
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public static function delUsuario($userId = null)
    {
        return static::find()->where(['user_id' => $userId ?? Yii::$app->user->id]);
    }

    public static function lista($userId = null)
    {
        return ArrayHelper::map(static::delUsuario($userId)->orderBy('nombre')->all(), 'id', 'nombre');
    }

    public static function limiteAlcanzado($userId = null)
    {
        return static::delUsuario($userId)->count() >= self::MAXIMO_POR_USUARIO;
    }

    public static function crearPorDefecto($userId)
    {
        $now = time();
        $filas = [];
        foreach (self::POR_DEFECTO as $nombre => $dia) {
            $filas[] = [$userId, $nombre, $dia, $now, $now];
        }
        Yii::$app->db->createCommand()->batchInsert(
            static::tableName(),
            ['user_id', 'nombre', 'dia_vencimiento', 'created_at', 'updated_at'],
            $filas
        )->execute();
    }

    /**
     * Fecha en un mes dado para un día del mes, ajustada a meses cortos
     * (día 31 en febrero => 28/29).
     *
     * @param string $mes 'Y-m'
     */
    public static function fechaEnMes($mes, $dia)
    {
        $primero = new DateTimeImmutable($mes . '-01');
        $dia = min((int) $dia, (int) $primero->format('t'));
        return $primero->setDate((int) $primero->format('Y'), (int) $primero->format('m'), $dia);
    }

    public function textoVencimiento()
    {
        return $this->dia_vencimiento ? 'Día ' . $this->dia_vencimiento : 'No vence';
    }
}
