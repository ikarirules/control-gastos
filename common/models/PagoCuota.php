<?php

namespace common\models;

use DateTimeImmutable;
use Yii;
use yii\db\ActiveRecord;
use yii\helpers\StringHelper;

/**
 * Pago mensual de un medio de pago (o de un préstamo sin medio de pago).
 * Si existe la fila para (usuario, mes, grupo), ese mes está pagado.
 *
 * @property int $id
 * @property int $user_id
 * @property string $mes 'Y-m'
 * @property string $grupo 'm<medio_pago_id>', 'p<prestamo_id>' o 'f<pago_fijo_id>'
 * @property int|null $medio_pago_id
 * @property int|null $prestamo_id
 * @property int|null $pago_fijo_id
 * @property string $monto
 * @property int|null $movimiento_id
 * @property int $created_at
 *
 * @property Movimiento|null $movimiento
 */
class PagoCuota extends ActiveRecord
{
    const CATEGORIA = 'Cuotas';

    public static function tableName()
    {
        return '{{%pago_cuota}}';
    }

    public function beforeSave($insert)
    {
        if ($insert && !$this->created_at) {
            $this->created_at = time();
        }
        return parent::beforeSave($insert);
    }

    public function getMovimiento()
    {
        return $this->hasOne(Movimiento::class, ['id' => 'movimiento_id']);
    }

    /** Primer mes en que se recuerdan pagos ('Y-m'). */
    public static function recordarDesde()
    {
        return Yii::$app->params['cuotas.recordarDesde'] ?? date('Y-m');
    }

    /**
     * ¿Está pagado ese grupo en ese mes? Los meses anteriores a recordarDesde() se dan por pagados.
     */
    public static function estaPagado(array $pagados, $mes, $grupo)
    {
        return $mes < static::recordarDesde() || !empty($pagados[$mes][$grupo]);
    }

    /**
     * Pagos registrados del usuario: mes => [grupo => PagoCuota].
     */
    public static function pagados($userId = null)
    {
        $mapa = [];
        $filas = static::find()
            ->where(['user_id' => $userId ?? Yii::$app->user->id])
            ->andWhere(['>=', 'mes', static::recordarDesde()])
            ->all();
        foreach ($filas as $p) {
            $mapa[$p->mes][$p->grupo] = $p;
        }
        return $mapa;
    }

    /**
     * Todo lo que hay que pagar en un mes: cuotas (agrupadas por medio de pago) + pagos fijos.
     *
     * @param Prestamo[] $prestamos
     * @param PagoFijo[] $fijos
     */
    public static function grupos(array $prestamos, array $fijos, $mes)
    {
        $grupos = Prestamo::gruposDelMes($prestamos, $mes) + PagoFijo::gruposDelMes($fijos, $mes);
        uasort($grupos, function ($a, $b) {
            return [$a['vence'], $a['nombre']] <=> [$b['vence'], $b['nombre']];
        });
        return $grupos;
    }

    /**
     * Pagos vencidos sin confirmar: grupos cuya fecha de vencimiento ya pasó
     * (desde recordarDesde() hasta hoy) y que todavía no se marcaron como pagados.
     *
     * @param Prestamo[] $prestamos
     * @param PagoFijo[] $fijos
     */
    public static function pendientes(array $prestamos, array $pagados, DateTimeImmutable $hoy = null, array $fijos = [])
    {
        $hoy = $hoy ?? Prestamo::hoy();
        $lista = [];
        $mes = new DateTimeImmutable(static::recordarDesde() . '-01');
        for (; $mes <= $hoy; $mes = $mes->modify('+1 month')) {
            $clave = $mes->format('Y-m');
            foreach (static::grupos($prestamos, $fijos, $clave) as $g) {
                if (isset($g['desde']) && $clave < $g['desde']) {
                    continue; // pago fijo cargado después de ese mes
                }
                if ($g['vence'] <= $hoy && !static::estaPagado($pagados, $clave, $g['grupo'])) {
                    $lista[] = $g;
                }
            }
        }
        return $lista;
    }

    /**
     * Pendientes del usuario (cuotas + pagos fijos activos), cargando todo lo necesario.
     * Se calcula una sola vez por request (lo usa el menú y las páginas).
     */
    public static function pendientesDelUsuario($userId = null)
    {
        static $cache = [];
        $userId = $userId ?? Yii::$app->user->id;
        if (!isset($cache[$userId])) {
            $cache[$userId] = static::pendientes(
                Prestamo::delUsuario($userId)->with('medioPago')->all(),
                static::pagados($userId),
                Prestamo::hoy(),
                PagoFijo::activos($userId)
            );
        }
        return $cache[$userId];
    }

    /**
     * Marca como pagado un grupo de un mes y, si $conEgreso, registra el egreso en la caja.
     *
     * @param float|null $monto monto real pagado (si difiere del estimado)
     * @return array [bool ok, string mensaje]
     */
    public static function registrar($userId, $mes, $grupo, $conEgreso = true, $monto = null)
    {
        if (!preg_match('/^\d{4}-\d{2}$/', $mes)) {
            return [false, 'Mes inválido.'];
        }
        $prestamos = Prestamo::delUsuario($userId)->with('medioPago')->all();
        $fijos = PagoFijo::delUsuario($userId)->with(['medioPago', 'categoria'])->all();
        $grupos = static::grupos($prestamos, $fijos, $mes);
        if (!isset($grupos[$grupo])) {
            return [false, 'No hay nada para pagar con ese nombre en ese mes.'];
        }
        $g = $grupos[$grupo];
        if ($monto !== null && $monto !== '') {
            if (!is_numeric($monto) || (float) $monto <= 0) {
                return [false, 'El monto tiene que ser mayor a cero.'];
            }
            $g['total'] = round((float) $monto, 2);
        }
        $categoriaId = Categoria::paraCuotas($userId)->id;
        if (!empty($g['fijo'])) {
            foreach ($fijos as $f) {
                if ((int) $f->id === $g['pago_fijo_id']) {
                    $categoriaId = $f->categoriaParaEgreso()->id;
                }
            }
        }
        $nombreMes = Prestamo::nombreMes(new DateTimeImmutable($mes . '-01'));

        $tx = Yii::$app->db->beginTransaction();
        try {
            if (static::find()->where(['user_id' => $userId, 'mes' => $mes, 'grupo' => $grupo])->exists()) {
                $tx->rollBack();
                return [false, "{$g['nombre']} de {$nombreMes} ya estaba marcado como pagado."];
            }

            $movimientoId = null;
            if ($conEgreso) {
                $mov = new Movimiento([
                    'user_id' => $userId,
                    'tipo' => Movimiento::TIPO_EGRESO,
                    'monto' => $g['total'],
                    'motivo' => StringHelper::truncate(
                        "Pago {$g['nombre']} - {$nombreMes}" . (empty($g['fijo']) ? ': ' . implode(', ', $g['items']) : ''),
                        255
                    ),
                    'categoria_id' => $categoriaId,
                    'medio_pago_id' => $g['medio_pago_id'],
                ]);
                if (!$mov->save()) {
                    throw new \RuntimeException('No se pudo registrar el egreso: ' . json_encode($mov->getFirstErrors(), JSON_UNESCAPED_UNICODE));
                }
                $movimientoId = $mov->id;
            }

            $pago = new static([
                'user_id' => $userId,
                'mes' => $mes,
                'grupo' => $grupo,
                'medio_pago_id' => $g['medio_pago_id'],
                'prestamo_id' => $g['prestamo_id'],
                'pago_fijo_id' => $g['pago_fijo_id'] ?? null,
                'monto' => $g['total'],
                'movimiento_id' => $movimientoId,
            ]);
            if (!$pago->save(false)) {
                throw new \RuntimeException('No se pudo guardar el pago.');
            }

            $tx->commit();
        } catch (\Throwable $e) {
            $tx->rollBack();
            Yii::error($e->getMessage(), __METHOD__);
            return [false, $e->getMessage()];
        }

        $monto = Movimiento::formatoMonto($g['total']);
        return [true, $conEgreso
            ? "Listo: {$g['nombre']} de {$nombreMes} pagado, se registró un egreso de {$monto}."
            : "Listo: {$g['nombre']} de {$nombreMes} marcado como pagado (sin egreso)."];
    }
}
