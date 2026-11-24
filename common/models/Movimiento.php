<?php

namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * Ingreso o egreso de dinero.
 *
 * @property int $id
 * @property int $user_id
 * @property string $tipo
 * @property string $monto
 * @property string $fecha
 * @property string $motivo
 * @property int $categoria_id
 * @property int|null $medio_pago_id
 * @property int $created_at
 * @property int $updated_at
 *
 * @property Categoria $categoria
 * @property User $user
 */
class Movimiento extends ActiveRecord
{
    const TIPO_INGRESO = 'ingreso';
    const TIPO_EGRESO = 'egreso';

    public static function tableName()
    {
        return '{{%movimiento}}';
    }

    public function behaviors()
    {
        return [TimestampBehavior::class];
    }

    public function rules()
    {
        return [
            [['tipo', 'monto', 'motivo', 'categoria_id'], 'required'],
            ['tipo', 'in', 'range' => array_keys(static::tipos())],
            ['monto', 'number', 'min' => 0.01],
            ['motivo', 'trim'],
            ['motivo', 'string', 'max' => 255],
            ['categoria_id', 'integer'],
            // La categoría tiene que ser del mismo usuario que el movimiento
            [
                'categoria_id', 'exist',
                'targetClass' => Categoria::class,
                'targetAttribute' => ['categoria_id' => 'id', 'user_id' => 'user_id'],
            ],
            ['medio_pago_id', 'default', 'value' => null],
            ['medio_pago_id', 'integer'],
            [
                'medio_pago_id', 'exist',
                'targetClass' => MedioPago::class,
                'targetAttribute' => ['medio_pago_id' => 'id', 'user_id' => 'user_id'],
            ],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'tipo' => 'Tipo',
            'monto' => 'Monto',
            'fecha' => 'Fecha',
            'motivo' => 'Motivo',
            'categoria_id' => 'Categoría',
            'medio_pago_id' => 'Medio de pago',
        ];
    }

    public static function tipos()
    {
        return [
            self::TIPO_INGRESO => 'Ingreso (entra plata)',
            self::TIPO_EGRESO => 'Egreso (sale plata)',
        ];
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }
        if ($insert) {
            // La fecha y el dueño se asignan automáticamente al crear
            $this->fecha = date('Y-m-d H:i:s');
            if ($this->user_id === null) {
                $this->user_id = Yii::$app->user->id;
            }
        }
        return true;
    }

    public function getCategoria()
    {
        return $this->hasOne(Categoria::class, ['id' => 'categoria_id']);
    }

    public function getMedioPago()
    {
        return $this->hasOne(MedioPago::class, ['id' => 'medio_pago_id']);
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function esIngreso()
    {
        return $this->tipo === self::TIPO_INGRESO;
    }

    /**
     * Formatea un monto como "$ 12.345,50".
     */
    public static function formatoMonto($monto)
    {
        $decimales = fmod((float) $monto, 1) == 0 ? 0 : 2;
        return '$ ' . number_format((float) $monto, $decimales, ',', '.');
    }

    /**
     * Totales de ingresos, egresos y saldo de un usuario
     * (o de la consulta recibida, si se pasa una).
     *
     * @param \yii\db\ActiveQuery|null $query
     * @return array ['ingresos' => float, 'egresos' => float, 'saldo' => float]
     */
    /** Mes actual en formato 'Y-m' (zona horaria de Mendoza). */
    public static function mesActual()
    {
        return date('Y-m');
    }

    /** Movimientos del usuario en un mes 'Y-m'. */
    public static function delMes($mes = null, $userId = null)
    {
        $inicio = ($mes ?: static::mesActual()) . '-01 00:00:00';
        $fin = date('Y-m-d H:i:s', strtotime($inicio . ' +1 month'));
        return static::find()
            ->where(['user_id' => $userId ?? Yii::$app->user->id])
            ->andWhere(['>=', 'fecha', $inicio])
            ->andWhere(['<', 'fecha', $fin]);
    }

    /**
     * Saldo con el que arranca un mes: todo lo de los meses anteriores.
     * Puede ser negativo si se gastó más de lo que había.
     */
    public static function saldoAnterior($mes = null, $userId = null)
    {
        $userId = $userId ?? Yii::$app->user->id;
        $inicio = ($mes ?: static::mesActual()) . '-01 00:00:00';
        return static::totales(static::find()->where(['user_id' => $userId])->andWhere(['<', 'fecha', $inicio]))['saldo'];
    }

    /**
     * Caja de un mes: ['anterior', 'ingresos', 'egresos', 'saldo_mes', 'saldo'].
     * 'saldo' = anterior + ingresos − egresos (lo que hay al día de hoy / al cierre del mes).
     *
     * @param \yii\db\ActiveQuery|null $query movimientos del mes (por defecto, todos los del mes)
     */
    public static function caja($mes = null, $query = null, $userId = null)
    {
        $mes = $mes ?: static::mesActual();
        $delMes = static::totales($query ?? static::delMes($mes, $userId));
        $anterior = static::saldoAnterior($mes, $userId);
        return [
            'anterior' => $anterior,
            'ingresos' => $delMes['ingresos'],
            'egresos' => $delMes['egresos'],
            'saldo_mes' => $delMes['saldo'],
            'saldo' => $anterior + $delMes['saldo'],
        ];
    }

    /** Mes 'Y-m' al que pertenece el movimiento. */
    public function mes()
    {
        return $this->fecha ? substr($this->fecha, 0, 7) : static::mesActual();
    }

    /**
     * Resumen por mes: [['mes' => 'Y-m', 'ingresos', 'egresos', 'saldo', 'cantidad'], ...] del más nuevo al más viejo.
     */
    public static function historial($userId = null)
    {
        $filas = static::find()
            ->select([
                'mes' => "DATE_FORMAT(fecha, '%Y-%m')",
                'ingresos' => "COALESCE(SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE 0 END), 0)",
                'egresos' => "COALESCE(SUM(CASE WHEN tipo = 'egreso' THEN monto ELSE 0 END), 0)",
                'cantidad' => 'COUNT(*)',
            ])
            ->where(['user_id' => $userId ?? Yii::$app->user->id])
            ->groupBy('mes')
            ->orderBy(['mes' => SORT_DESC])
            ->asArray()
            ->all();

        // Se acumula del más viejo al más nuevo para arrastrar el saldo anterior
        $acumulado = 0.0;
        $meses = [];
        foreach (array_reverse($filas) as $f) {
            $ingresos = (float) $f['ingresos'];
            $egresos = (float) $f['egresos'];
            $meses[] = [
                'mes' => $f['mes'],
                'anterior' => $acumulado,
                'ingresos' => $ingresos,
                'egresos' => $egresos,
                'saldo_mes' => $ingresos - $egresos,
                'saldo' => $acumulado += $ingresos - $egresos,
                'cantidad' => (int) $f['cantidad'],
            ];
        }
        return array_reverse($meses);
    }

    public static function totales($query = null)
    {
        if ($query === null) {
            $query = static::find()->where(['user_id' => Yii::$app->user->id]);
        }
        $fila = (clone $query)
            ->select([
                'ingresos' => "COALESCE(SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE 0 END), 0)",
                'egresos' => "COALESCE(SUM(CASE WHEN tipo = 'egreso' THEN monto ELSE 0 END), 0)",
            ])
            ->orderBy(null)
            ->asArray()
            ->one();

        $ingresos = (float) $fila['ingresos'];
        $egresos = (float) $fila['egresos'];
        return ['ingresos' => $ingresos, 'egresos' => $egresos, 'saldo' => $ingresos - $egresos];
    }
}
