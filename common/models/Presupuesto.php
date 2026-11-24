<?php

namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Query;

/**
 * Cuánto se estima gastar por mes en una categoría (súper, combustible, verdulería...).
 *
 * Los egresos generados al confirmar cuotas o pagos fijos ("Sí, lo pagué") no cuentan
 * como gasto variable: esos ya están en la proyección como cuotas / pagos fijos.
 *
 * @property int $id
 * @property int $user_id
 * @property int $categoria_id
 * @property string $monto
 * @property int $created_at
 * @property int $updated_at
 *
 * @property Categoria $categoria
 */
class Presupuesto extends ActiveRecord
{
    /** Meses completos que se promedian para la sugerencia. */
    const MESES_SUGERENCIA = 3;

    public static function tableName()
    {
        return '{{%presupuesto}}';
    }

    public function behaviors()
    {
        return [TimestampBehavior::class];
    }

    public function rules()
    {
        return [
            [['categoria_id', 'monto'], 'required'],
            ['monto', 'number', 'min' => 0.01, 'max' => 999999999999],
            [
                'categoria_id', 'exist',
                'targetClass' => Categoria::class,
                'targetAttribute' => ['categoria_id' => 'id', 'user_id' => 'user_id'],
            ],
        ];
    }

    public function getCategoria()
    {
        return $this->hasOne(Categoria::class, ['id' => 'categoria_id']);
    }

    /** @return array categoria_id => monto */
    public static function delUsuario($userId = null)
    {
        $mapa = [];
        foreach (static::find()->where(['user_id' => $userId ?? Yii::$app->user->id])->all() as $p) {
            $mapa[(int) $p->categoria_id] = (float) $p->monto;
        }
        return $mapa;
    }

    /**
     * Categorías que se pueden presupuestar (todas menos la de cuotas, que ya se proyecta sola).
     *
     * @return Categoria[]
     */
    public static function categorias($userId = null)
    {
        return Categoria::delUsuario($userId)
            ->andWhere(['<>', 'nombre', PagoCuota::CATEGORIA])
            ->orderBy('nombre')
            ->all();
    }

    /**
     * Egresos variables por categoría entre dos fechas (sin los pagos de cuotas / pagos fijos).
     *
     * @return array categoria_id => total
     */
    private static function egresosPorCategoria($userId, $desde, $hasta)
    {
        $filas = (new Query())
            ->select(['m.categoria_id', 'total' => 'SUM(m.monto)'])
            ->from(['m' => Movimiento::tableName()])
            ->leftJoin(['pc' => PagoCuota::tableName()], 'pc.movimiento_id = m.id')
            ->where(['m.user_id' => $userId, 'm.tipo' => Movimiento::TIPO_EGRESO, 'pc.id' => null])
            ->andWhere(['>=', 'm.fecha', $desde])
            ->andWhere(['<', 'm.fecha', $hasta])
            ->groupBy('m.categoria_id')
            ->all();

        $mapa = [];
        foreach ($filas as $f) {
            $mapa[(int) $f['categoria_id']] = (float) $f['total'];
        }
        return $mapa;
    }

    /** Gasto variable del mes por categoría. */
    public static function gastadoDelMes($mes = null, $userId = null)
    {
        $mes = $mes ?: Movimiento::mesActual();
        $desde = $mes . '-01 00:00:00';
        return static::egresosPorCategoria(
            $userId ?? Yii::$app->user->id,
            $desde,
            date('Y-m-d H:i:s', strtotime($desde . ' +1 month'))
        );
    }

    /**
     * Sugerencia por categoría: promedio mensual de los últimos meses completos con movimientos
     * (hasta MESES_SUGERENCIA). Sin historial devuelve [].
     *
     * @return array ['meses' => int, 'promedios' => categoria_id => promedio]
     */
    public static function sugerencias($userId = null)
    {
        $userId = $userId ?? Yii::$app->user->id;
        $inicioMes = Movimiento::mesActual() . '-01 00:00:00';

        $meses = (new Query())
            ->select(['mes' => "DATE_FORMAT(fecha, '%Y-%m')"])
            ->distinct()
            ->from(Movimiento::tableName())
            ->where(['user_id' => $userId])
            ->andWhere(['<', 'fecha', $inicioMes])
            ->orderBy(['mes' => SORT_DESC])
            ->limit(self::MESES_SUGERENCIA)
            ->column();

        if (!$meses) {
            return ['meses' => 0, 'promedios' => []];
        }

        $desde = min($meses) . '-01 00:00:00';
        $promedios = [];
        foreach (static::egresosPorCategoria($userId, $desde, $inicioMes) as $cat => $total) {
            $promedios[$cat] = round($total / count($meses), 2);
        }
        return ['meses' => count($meses), 'promedios' => $promedios];
    }

    /** Qué parte del mes ya pasó (0..1), contando el día de hoy. */
    public static function avanceDelMes()
    {
        return (int) date('j') / (int) date('t');
    }

    /**
     * Seguimiento del mes actual para las categorías con presupuesto.
     *
     * @return array ['filas' => [...], 'estimado', 'gastado', 'falta', 'avance']
     *   cada fila: categoria, estimado, gastado, porcentaje, ritmo (proyección al cierre), estado (bien|rapido|pasado)
     */
    public static function seguimiento($userId = null)
    {
        $userId = $userId ?? Yii::$app->user->id;
        $presupuestos = static::delUsuario($userId);
        $gastado = static::gastadoDelMes(null, $userId);
        $avance = static::avanceDelMes();
        $nombres = Categoria::lista($userId);

        $filas = [];
        $totEstimado = $totGastado = $totFalta = 0.0;
        foreach ($presupuestos as $catId => $estimado) {
            if (!isset($nombres[$catId])) {
                continue;
            }
            $g = $gastado[$catId] ?? 0.0;
            $porcentaje = $estimado > 0 ? $g / $estimado : 0;
            if ($g > $estimado) {
                $estado = 'pasado';
            } elseif ($porcentaje > $avance + 0.1) {
                $estado = 'rapido';
            } else {
                $estado = 'bien';
            }
            $filas[] = [
                'categoria_id' => $catId,
                'categoria' => $nombres[$catId],
                'estimado' => $estimado,
                'gastado' => $g,
                'porcentaje' => $porcentaje,
                'ritmo' => $avance > 0 ? $g / $avance : $g,
                'estado' => $estado,
            ];
            $totEstimado += $estimado;
            $totGastado += $g;
            $totFalta += max(0, $estimado - $g);
        }

        usort($filas, function ($a, $b) {
            return $b['porcentaje'] <=> $a['porcentaje'];
        });

        return [
            'filas' => $filas,
            'estimado' => $totEstimado,
            'gastado' => $totGastado,
            'falta' => $totFalta,
            'avance' => $avance,
        ];
    }

    /**
     * Proyección del mes actual: cuotas + pagos fijos + gastos variables estimados.
     *
     * @return array [
     *   'cuotas', 'fijos', 'variables' => total del mes de cada cosa,
     *   'total' => suma de las tres,
     *   'falta_cuotas_fijos' => cuotas y pagos fijos sin marcar como pagados,
     *   'falta_variables' => lo que queda del presupuesto variable,
     *   'falta' => lo que todavía falta gastar/pagar en el mes,
     *   'saldo' => saldo actual, 'saldo_cierre' => saldo estimado al cierre,
     *   'seguimiento' => ver seguimiento(),
     * ]
     */
    public static function proyeccion($userId = null)
    {
        $userId = $userId ?? Yii::$app->user->id;
        $mes = Movimiento::mesActual();
        $prestamos = Prestamo::delUsuario($userId)->with('medioPago')->all();
        $fijos = PagoFijo::activos($userId);
        $pagados = PagoCuota::pagados($userId);

        $cuotas = $montoFijos = $falta = 0.0;
        foreach (PagoCuota::grupos($prestamos, $fijos, $mes) as $g) {
            if (!empty($g['fijo'])) {
                $montoFijos += $g['total'];
            } else {
                $cuotas += $g['total'];
            }
            if (!PagoCuota::estaPagado($pagados, $mes, $g['grupo'])) {
                $falta += $g['total'];
            }
        }

        $seguimiento = static::seguimiento($userId);
        $saldo = Movimiento::caja($mes, null, $userId)['saldo'];
        $faltaTotal = $falta + $seguimiento['falta'];

        return [
            'cuotas' => $cuotas,
            'fijos' => $montoFijos,
            'variables' => $seguimiento['estimado'],
            'total' => $cuotas + $montoFijos + $seguimiento['estimado'],
            'falta_cuotas_fijos' => $falta,
            'falta_variables' => $seguimiento['falta'],
            'falta' => $faltaTotal,
            'saldo' => $saldo,
            'saldo_cierre' => $saldo - $faltaTotal,
            'seguimiento' => $seguimiento,
        ];
    }

    /**
     * Guarda los estimados del formulario: categoria_id => monto (vacío o 0 = sin presupuesto).
     *
     * @return array errores categoria_id => mensaje
     */
    public static function guardar(array $montos, $userId = null)
    {
        $userId = $userId ?? Yii::$app->user->id;
        $validas = array_map('intval', array_keys(Categoria::lista($userId)));
        $existentes = static::find()->where(['user_id' => $userId])->indexBy('categoria_id')->all();
        $errores = [];

        $tx = Yii::$app->db->beginTransaction();
        try {
            foreach ($montos as $catId => $monto) {
                $catId = (int) $catId;
                if (!in_array($catId, $validas, true)) {
                    continue;
                }
                $monto = trim(str_replace(',', '.', (string) $monto));
                $actual = $existentes[$catId] ?? null;

                if ($monto === '' || (is_numeric($monto) && (float) $monto == 0)) {
                    if ($actual) {
                        $actual->delete();
                    }
                    continue;
                }
                $p = $actual ?? new static(['user_id' => $userId, 'categoria_id' => $catId]);
                $p->monto = $monto;
                if (!$p->save()) {
                    $errores[$catId] = $p->getFirstError('monto') ?: 'Monto inválido.';
                }
            }
            if ($errores) {
                $tx->rollBack();
            } else {
                $tx->commit();
            }
        } catch (\Throwable $e) {
            $tx->rollBack();
            throw $e;
        }
        return $errores;
    }
}
