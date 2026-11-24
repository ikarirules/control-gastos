<?php

namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * Categoría de movimientos. Cada usuario tiene las suyas.
 *
 * @property int $id
 * @property int $user_id
 * @property string $nombre
 * @property int $created_at
 * @property int $updated_at
 *
 * @property Movimiento[] $movimientos
 * @property User $user
 */
class Categoria extends ActiveRecord
{
    /** Categorías que recibe cada usuario nuevo. */
    const POR_DEFECTO = ['Gasto fijo', 'Gasto particular', 'Ropa', 'Comida', 'Transporte', 'Sueldo', 'Otros'];

    /** Cantidad máxima de categorías por usuario. */
    const MAXIMO_POR_USUARIO = 20;

    public static function tableName()
    {
        return '{{%categoria}}';
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
                'message' => 'Ya tenés una categoría con ese nombre.',
            ],
            ['nombre', 'validarMaximo', 'when' => function () {
                return $this->isNewRecord;
            }],
        ];
    }

    public function validarMaximo($attribute)
    {
        if (static::limiteAlcanzado($this->user_id)) {
            $this->addError(
                $attribute,
                'Llegaste al máximo de ' . self::MAXIMO_POR_USUARIO . ' categorías. Eliminá o renombrá una existente.'
            );
        }
    }

    /**
     * ¿El usuario ya tiene el máximo de categorías permitido?
     */
    public static function limiteAlcanzado($userId = null)
    {
        return static::delUsuario($userId)->count() >= self::MAXIMO_POR_USUARIO;
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'nombre' => 'Nombre',
        ];
    }

    public function getMovimientos()
    {
        return $this->hasMany(Movimiento::class, ['categoria_id' => 'id']);
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    /**
     * Categorías de un usuario (por defecto, el logueado).
     * @return \yii\db\ActiveQuery
     */
    public static function delUsuario($userId = null)
    {
        return static::find()->where(['user_id' => $userId ?? Yii::$app->user->id]);
    }

    /**
     * Lista id => nombre de las categorías del usuario, para los desplegables.
     * @return array
     */
    public static function lista($userId = null)
    {
        return ArrayHelper::map(static::delUsuario($userId)->orderBy('nombre')->all(), 'id', 'nombre');
    }

    /**
     * Categoría para los egresos de pagos de cuotas: "Cuotas" (se crea si no existe).
     * Si el usuario llegó al límite de categorías, usa "Otros" o la primera que tenga.
     */
    public static function paraCuotas($userId)
    {
        $cat = static::delUsuario($userId)->andWhere(['nombre' => PagoCuota::CATEGORIA])->one();
        if ($cat) {
            return $cat;
        }
        $cat = new static(['user_id' => $userId, 'nombre' => PagoCuota::CATEGORIA]);
        if ($cat->save()) {
            return $cat;
        }
        return static::delUsuario($userId)->andWhere(['nombre' => 'Otros'])->one()
            ?? static::delUsuario($userId)->orderBy('id')->one();
    }

    /**
     * Crea las categorías por defecto para un usuario.
     */
    public static function crearPorDefecto($userId)
    {
        $now = time();
        Yii::$app->db->createCommand()->batchInsert(
            static::tableName(),
            ['user_id', 'nombre', 'created_at', 'updated_at'],
            array_map(function ($nombre) use ($userId, $now) {
                return [$userId, $nombre, $now, $now];
            }, self::POR_DEFECTO)
        )->execute();
    }
}
