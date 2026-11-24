<?php

namespace common\models;

use DateTimeImmutable;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * Billetera o cuenta donde hay plata (Mercado Pago, Ualá, banco...). Cada usuario tiene las suyas.
 *
 * @property int $id
 * @property int $user_id
 * @property string $nombre
 * @property int $created_at
 * @property int $updated_at
 *
 * @property BilleteraSaldo[] $saldos
 */
class Billetera extends ActiveRecord
{
    const MAXIMO_POR_USUARIO = 20;

    public static function tableName()
    {
        return '{{%billetera}}';
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
                'message' => 'Ya tenés una billetera con ese nombre.',
            ],
            ['nombre', 'validarMaximo', 'when' => function () {
                return $this->isNewRecord;
            }],
        ];
    }

    public function validarMaximo($attribute)
    {
        if (static::limiteAlcanzado($this->user_id)) {
            $this->addError($attribute, 'Llegaste al máximo de ' . self::MAXIMO_POR_USUARIO . ' billeteras.');
        }
    }

    public function attributeLabels()
    {
        return ['nombre' => 'Nombre'];
    }

    public function getSaldos()
    {
        return $this->hasMany(BilleteraSaldo::class, ['billetera_id' => 'id'])->orderBy(['fecha' => SORT_ASC]);
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

    /**
     * Días entre dos fechas 'Y-m-d'.
     */
    private static function dias($desde, $hasta)
    {
        return (int) (new DateTimeImmutable($desde))->diff(new DateTimeImmutable($hasta))->days;
    }

    /**
     * Estado de cada billetera: último saldo, comparación con el registro anterior e historial.
     *
     * @return array [
     *   'billeteras' => [['billetera' => Billetera, 'ultimo' => BilleteraSaldo|null, 'diferencia' => float|null,
     *                     'dias' => int|null, 'registros' => [['saldo' => BilleteraSaldo, 'diferencia' => float|null, 'dias' => int|null]] (más nuevo primero)]],
     *   'total' => float suma del último saldo de cada billetera,
     *   'diferencia' => float suma de las diferencias contra el registro anterior,
     * ]
     */
    public static function resumen($userId = null)
    {
        $billeteras = static::delUsuario($userId)->with('saldos')->orderBy('nombre')->all();
        $filas = [];
        $total = 0.0;
        $diferenciaTotal = 0.0;
        foreach ($billeteras as $b) {
            $registros = [];
            $previo = null;
            foreach ($b->saldos as $s) {
                $registros[] = [
                    'saldo' => $s,
                    'diferencia' => $previo ? (float) $s->monto - (float) $previo->monto : null,
                    'dias' => $previo ? static::dias($previo->fecha, $s->fecha) : null,
                ];
                $previo = $s;
            }
            $registros = array_reverse($registros);
            $ultimo = $registros[0] ?? null;
            if ($ultimo) {
                $total += (float) $ultimo['saldo']->monto;
                $diferenciaTotal += (float) $ultimo['diferencia'];
            }
            $filas[] = [
                'billetera' => $b,
                'ultimo' => $ultimo ? $ultimo['saldo'] : null,
                'diferencia' => $ultimo ? $ultimo['diferencia'] : null,
                'dias' => $ultimo ? $ultimo['dias'] : null,
                'registros' => $registros,
            ];
        }

        return ['billeteras' => $filas, 'total' => $total, 'diferencia' => $diferenciaTotal];
    }

    /**
     * Comienzo y cierre de cada billetera por mes (más nuevo primero).
     *
     * - inicio: primer saldo cargado en el mes; cierre: último saldo cargado en el mes.
     * - variacion: cierre contra el cierre del último mes con datos (o contra el inicio si es el primero).
     * - total: suma del último saldo conocido de todas las billeteras a fin de ese mes.
     *
     * @return array [['mes' => 'Y-m', 'filas' => [...], 'total' => float, 'variacion' => float|null]]
     */
    public static function porMes($userId = null)
    {
        $billeteras = static::delUsuario($userId)->with('saldos')->orderBy('nombre')->all();
        $meses = [];
        foreach ($billeteras as $b) {
            foreach ($b->saldos as $s) {
                $meses[substr($s->fecha, 0, 7)] = true;
            }
        }
        ksort($meses);

        $resultado = [];
        $ultimoCierre = [];   // billetera_id => monto al cierre del último mes con datos
        $totalAnterior = null;
        foreach (array_keys($meses) as $mes) {
            $filas = [];
            foreach ($billeteras as $b) {
                $delMes = array_values(array_filter($b->saldos, function ($s) use ($mes) {
                    return strncmp($s->fecha, $mes, 7) === 0;
                }));
                if (!$delMes) {
                    continue;
                }
                $inicio = $delMes[0];
                $cierre = $delMes[count($delMes) - 1];
                $base = $ultimoCierre[$b->id] ?? (float) $inicio->monto;
                $filas[] = [
                    'billetera' => $b,
                    'inicio' => $inicio,
                    'cierre' => $cierre,
                    'variacion' => (float) $cierre->monto - $base,
                    'contra' => isset($ultimoCierre[$b->id]) ? 'mes anterior' : 'inicio del mes',
                ];
                $ultimoCierre[$b->id] = (float) $cierre->monto;
            }
            $total = array_sum($ultimoCierre);
            $resultado[] = [
                'mes' => $mes,
                'filas' => $filas,
                'total' => $total,
                'variacion' => $totalAnterior === null ? null : $total - $totalAnterior,
            ];
            $totalAnterior = $total;
        }

        return array_reverse($resultado);
    }
}
