<?php

namespace common\models;

use DateTimeImmutable;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * Préstamo, crédito o compra con tarjeta en cuotas.
 *
 * @property int $id
 * @property int $user_id
 * @property string $nombre
 * @property string $fecha_primera_cuota
 * @property string $monto_cuota
 * @property int $cantidad_cuotas
 * @property int|null $medio_pago_id
 * @property int $created_at
 * @property int $updated_at
 *
 * @property User $user
 * @property MedioPago|null $medioPago
 */
class Prestamo extends ActiveRecord
{
    const MESES = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
    ];

    public static function tableName()
    {
        return '{{%prestamo}}';
    }

    public function behaviors()
    {
        return [TimestampBehavior::class];
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function getMedioPago()
    {
        return $this->hasOne(MedioPago::class, ['id' => 'medio_pago_id']);
    }

    public function rules()
    {
        return [
            [['nombre', 'fecha_primera_cuota', 'monto_cuota', 'cantidad_cuotas'], 'required'],
            ['nombre', 'trim'],
            ['nombre', 'string', 'max' => 150],
            ['fecha_primera_cuota', 'date', 'format' => 'php:Y-m-d'],
            ['monto_cuota', 'number', 'min' => 0.01],
            ['cantidad_cuotas', 'integer', 'min' => 1, 'max' => 600],
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
            'nombre' => 'Descripción',
            'fecha_primera_cuota' => 'Fecha de la primera cuota',
            'monto_cuota' => 'Monto de cada cuota',
            'cantidad_cuotas' => 'Cantidad de cuotas',
            'medio_pago_id' => 'Medio de pago',
        ];
    }

    /**
     * Día del mes en que vence cada cuota: el del medio de pago si tiene,
     * si no el día de la primera cuota.
     */
    public function diaVencimiento()
    {
        if ($this->medioPago && $this->medioPago->dia_vencimiento) {
            return (int) $this->medioPago->dia_vencimiento;
        }
        return (int) $this->fechaInicio()->format('j');
    }

    /**
     * Préstamos de un usuario (por defecto, el logueado).
     * @return \yii\db\ActiveQuery
     */
    public static function delUsuario($userId = null)
    {
        return static::find()->where(['user_id' => $userId ?? Yii::$app->user->id]);
    }

    public static function hoy()
    {
        return new DateTimeImmutable('today');
    }

    public static function nombreMes(DateTimeImmutable $fecha)
    {
        return self::MESES[(int) $fecha->format('n')] . ' ' . $fecha->format('Y');
    }

    /**
     * Mes (primer día) de la cuota $i (0 = primera). Se calcula desde el
     * primer día del mes para que una primera cuota el 31 no se saltee meses.
     */
    private function mesDeCuota($i)
    {
        $inicio = (new DateTimeImmutable($this->fecha_primera_cuota))->modify('first day of this month');
        return $inicio->modify("+{$i} months");
    }

    /**
     * Detalle de cuotas: una fila por cuota con su mes y estado respecto a hoy.
     * Igual que en cuenta-cuotas: una cuota de un mes ya pasado se considera pagada.
     */
    public function detalleCuotas(DateTimeImmutable $hoy = null)
    {
        $mesActual = ($hoy ?? static::hoy())->format('Y-m');
        $dia = $this->diaVencimiento();
        $detalle = [];
        for ($i = 0; $i < (int) $this->cantidad_cuotas; $i++) {
            $mes = $this->mesDeCuota($i)->format('Y-m');
            $detalle[] = [
                'numero' => $i + 1,
                'mes' => $mes,
                'vence' => MedioPago::fechaEnMes($mes, $dia),
                'monto' => (float) $this->monto_cuota,
                'pagada' => $mes < $mesActual,
                'es_mes_actual' => $mes === $mesActual,
            ];
        }
        return $detalle;
    }

    /** Cuotas pagadas hasta hoy, contando la del mes actual (como en cuenta-cuotas). */
    public function cuotasPagadas(DateTimeImmutable $hoy = null)
    {
        $pagadas = 0;
        foreach ($this->detalleCuotas($hoy) as $c) {
            if ($c['pagada'] || $c['es_mes_actual']) {
                $pagadas++;
            }
        }
        return $pagadas;
    }

    /** Mes 'Y-m' de la última cuota. */
    public function mesFinal()
    {
        return $this->mesDeCuota(max(0, (int) $this->cantidad_cuotas - 1))->format('Y-m');
    }

    /** Ya pagó la última cuota (terminó en un mes anterior al actual). */
    public function terminado(DateTimeImmutable $hoy = null)
    {
        return $this->mesFinal() < ($hoy ?? static::hoy())->format('Y-m');
    }

    /** La última cuota es la de este mes. */
    public function terminaEsteMes(DateTimeImmutable $hoy = null)
    {
        return $this->mesFinal() === ($hoy ?? static::hoy())->format('Y-m');
    }

    public function montoTotal()
    {
        return (int) $this->cantidad_cuotas * (float) $this->monto_cuota;
    }

    public function fechaInicio()
    {
        return new DateTimeImmutable($this->fecha_primera_cuota);
    }

    /**
     * Calendario mensual: total a pagar por mes entre todos los préstamos.
     *
     * @param Prestamo[] $prestamos
     * @return array clave 'Y-m' => ['fecha', 'total', 'items', 'es_pasado', 'es_actual']
     */
    public static function calendario(array $prestamos, $mesesAtras, $mesesAdelante, DateTimeImmutable $hoy = null)
    {
        $hoy = $hoy ?? static::hoy();
        $base = $hoy->modify('first day of this month');
        $mesActual = $hoy->format('Y-m');
        $meses = [];

        for ($i = -$mesesAtras; $i <= $mesesAdelante; $i++) {
            $fecha = $base->modify("{$i} months");
            $clave = $fecha->format('Y-m');
            $meses[$clave] = [
                'clave' => $clave,
                'fecha' => $fecha,
                'total' => 0.0,
                'items' => [],
                'es_pasado' => $clave < $mesActual,
                'es_actual' => $clave === $mesActual,
            ];
        }

        foreach ($prestamos as $p) {
            foreach ($p->detalleCuotas($hoy) as $c) {
                if (isset($meses[$c['mes']])) {
                    $meses[$c['mes']]['total'] += $c['monto'];
                    $meses[$c['mes']]['items'][] = [
                        'nombre' => $p->nombre,
                        'numero' => $c['numero'],
                        'cantidad_cuotas' => (int) $p->cantidad_cuotas,
                        'monto' => $c['monto'],
                        'vence' => $c['vence'],
                        'medio' => $p->medioPago ? $p->medioPago->nombre : null,
                        'grupo' => $p->claveGrupo(),
                    ];
                }
            }
        }

        // Dentro de cada mes, ordenar por fecha de vencimiento
        foreach ($meses as &$mes) {
            usort($mes['items'], function ($a, $b) {
                return $a['vence'] <=> $b['vence'];
            });
        }
        unset($mes);

        return $meses;
    }

    /**
     * Clave con la que se agrupa el pago de esta cuota: por medio de pago
     * ('m<id>') o, si no tiene, el préstamo solo ('p<id>').
     */
    public function claveGrupo()
    {
        return $this->medio_pago_id ? 'm' . $this->medio_pago_id : 'p' . $this->id;
    }

    /**
     * Lo que hay que pagar en un mes, agrupado por medio de pago
     * (los préstamos sin medio de pago van cada uno por separado).
     *
     * @param Prestamo[] $prestamos
     * @param string $mes 'Y-m'
     * @return array clave grupo => ['grupo', 'mes', 'medio_pago_id', 'prestamo_id', 'nombre', 'vence', 'total', 'items']
     */
    public static function gruposDelMes(array $prestamos, $mes)
    {
        $grupos = [];
        foreach ($prestamos as $p) {
            foreach ($p->detalleCuotas() as $c) {
                if ($c['mes'] !== $mes) {
                    continue;
                }
                $clave = $p->claveGrupo();
                if (!isset($grupos[$clave])) {
                    $grupos[$clave] = [
                        'grupo' => $clave,
                        'mes' => $mes,
                        'medio_pago_id' => $p->medio_pago_id ? (int) $p->medio_pago_id : null,
                        'prestamo_id' => $p->medio_pago_id ? null : (int) $p->id,
                        'nombre' => $p->medioPago ? $p->medioPago->nombre : $p->nombre,
                        'vence' => $c['vence'],
                        'total' => 0.0,
                        'items' => [],
                    ];
                }
                $grupos[$clave]['total'] += $c['monto'];
                $grupos[$clave]['items'][] = $p->nombre . ' (' . $c['numero'] . '/' . $p->cantidad_cuotas . ')';
            }
        }

        uasort($grupos, function ($a, $b) {
            return [$a['vence'], $a['nombre']] <=> [$b['vence'], $b['nombre']];
        });
        return $grupos;
    }

    /**
     * Próximos vencimientos (desde hoy, dentro de $dias días) que todavía no se pagaron.
     *
     * @param Prestamo[] $prestamos
     * @param array $pagados mes => [grupo => true] (ver PagoCuota::pagados())
     * @return array lista de grupos (ver gruposDelMes()) ordenada por fecha
     */
    public static function proximosVencimientos(array $prestamos, $dias = 45, DateTimeImmutable $hoy = null, array $pagados = [], array $fijos = [])
    {
        $hoy = $hoy ?? static::hoy();
        $hasta = $hoy->modify("+{$dias} days");
        $lista = [];

        for ($m = $hoy->modify('first day of this month'); $m <= $hasta; $m = $m->modify('+1 month')) {
            $mes = $m->format('Y-m');
            foreach (PagoCuota::grupos($prestamos, $fijos, $mes) as $g) {
                if ($g['vence'] >= $hoy && $g['vence'] <= $hasta && empty($pagados[$mes][$g['grupo']])) {
                    $lista[] = $g;
                }
            }
        }

        usort($lista, function ($a, $b) {
            return [$a['vence'], $a['nombre']] <=> [$b['vence'], $b['nombre']];
        });
        return $lista;
    }
}
