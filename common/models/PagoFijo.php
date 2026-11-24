<?php

namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * Pago fijo mensual (alquiler, luz, internet...) con día de vencimiento estimado.
 *
 * @property int $id
 * @property int $user_id
 * @property string $nombre
 * @property string $monto monto estimado
 * @property int $dia_vencimiento
 * @property int|null $categoria_id
 * @property int|null $medio_pago_id
 * @property int $activo
 * @property int $created_at
 * @property int $updated_at
 *
 * @property Categoria|null $categoria
 * @property MedioPago|null $medioPago
 */
class PagoFijo extends ActiveRecord
{
    /** Cantidad máxima de pagos fijos por usuario. */
    const MAXIMO_POR_USUARIO = 20;

    /** Categoría que se usa si el pago fijo no tiene una. */
    const CATEGORIA = 'Gasto fijo';

    public static function tableName()
    {
        return '{{%pago_fijo}}';
    }

    public function behaviors()
    {
        return [TimestampBehavior::class];
    }

    public function rules()
    {
        return [
            ['nombre', 'trim'],
            [['nombre', 'monto', 'dia_vencimiento'], 'required'],
            ['nombre', 'string', 'max' => 100],
            [
                'nombre', 'unique',
                'targetAttribute' => ['user_id', 'nombre'],
                'message' => 'Ya tenés un pago fijo con ese nombre.',
            ],
            ['nombre', 'validarMaximo', 'when' => function () {
                return $this->isNewRecord;
            }],
            ['monto', 'number', 'min' => 0.01, 'max' => 999999999999],
            ['dia_vencimiento', 'integer', 'min' => 1, 'max' => 31],
            [['categoria_id', 'medio_pago_id'], 'default', 'value' => null],
            [
                'categoria_id', 'exist',
                'targetClass' => Categoria::class,
                'targetAttribute' => ['categoria_id' => 'id', 'user_id' => 'user_id'],
            ],
            [
                'medio_pago_id', 'exist',
                'targetClass' => MedioPago::class,
                'targetAttribute' => ['medio_pago_id' => 'id', 'user_id' => 'user_id'],
            ],
            ['activo', 'boolean'],
            ['activo', 'default', 'value' => 1],
        ];
    }

    public function validarMaximo($attribute)
    {
        if (static::limiteAlcanzado($this->user_id)) {
            $this->addError(
                $attribute,
                'Llegaste al máximo de ' . self::MAXIMO_POR_USUARIO . ' pagos fijos. Eliminá o modificá uno existente.'
            );
        }
    }

    public function attributeLabels()
    {
        return [
            'nombre' => 'Nombre',
            'monto' => 'Monto estimado',
            'dia_vencimiento' => 'Día de vencimiento',
            'categoria_id' => 'Categoría',
            'medio_pago_id' => 'Medio de pago',
            'activo' => 'Activo',
        ];
    }

    public function getCategoria()
    {
        return $this->hasOne(Categoria::class, ['id' => 'categoria_id']);
    }

    public function getMedioPago()
    {
        return $this->hasOne(MedioPago::class, ['id' => 'medio_pago_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public static function delUsuario($userId = null)
    {
        return static::find()->where(['user_id' => $userId ?? Yii::$app->user->id]);
    }

    /** Pagos fijos activos del usuario. */
    public static function activos($userId = null)
    {
        return static::delUsuario($userId)->andWhere(['activo' => 1])->with('medioPago')->all();
    }

    public static function limiteAlcanzado($userId = null)
    {
        return static::delUsuario($userId)->count() >= self::MAXIMO_POR_USUARIO;
    }

    public function claveGrupo()
    {
        return 'f' . $this->id;
    }

    /** Primer mes ('Y-m') en que se recuerda: el mes en que se cargó. */
    public function mesDesde()
    {
        return date('Y-m', $this->created_at ?: time());
    }

    /** Categoría para el egreso: la propia, "Gasto fijo" o la de cuotas. */
    public function categoriaParaEgreso()
    {
        if ($this->categoria) {
            return $this->categoria;
        }
        return Categoria::delUsuario($this->user_id)->andWhere(['nombre' => self::CATEGORIA])->one()
            ?? Categoria::paraCuotas($this->user_id);
    }

    /**
     * Grupos del mes (mismo formato que Prestamo::gruposDelMes()), uno por pago fijo.
     *
     * @param PagoFijo[] $fijos
     * @param string $mes 'Y-m'
     */
    public static function gruposDelMes(array $fijos, $mes)
    {
        $grupos = [];
        foreach ($fijos as $f) {
            if (!$f->activo) {
                continue;
            }
            $grupos[$f->claveGrupo()] = [
                'grupo' => $f->claveGrupo(),
                'mes' => $mes,
                'medio_pago_id' => $f->medio_pago_id ? (int) $f->medio_pago_id : null,
                'prestamo_id' => null,
                'pago_fijo_id' => (int) $f->id,
                'nombre' => $f->nombre,
                'vence' => MedioPago::fechaEnMes($mes, $f->dia_vencimiento),
                'total' => (float) $f->monto,
                'items' => [$f->medioPago ? 'Pago fijo · ' . $f->medioPago->nombre : 'Pago fijo'],
                'fijo' => true,
                'desde' => $f->mesDesde(),
            ];
        }
        return $grupos;
    }
}
