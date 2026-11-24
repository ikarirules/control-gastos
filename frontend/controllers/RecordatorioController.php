<?php

namespace frontend\controllers;

use common\models\PagoCuota;
use common\models\PagoFijo;
use common\models\Prestamo;
use yii\web\Controller;

/**
 * Recordatorios: pagos vencidos sin confirmar y lo que vence pronto.
 */
class RecordatorioController extends Controller
{
    const DIAS_PROXIMOS = 45;

    public function actionIndex()
    {
        $hoy = Prestamo::hoy();
        $prestamos = Prestamo::delUsuario()->with('medioPago')->all();
        $fijos = PagoFijo::activos();
        $pagados = PagoCuota::pagados();

        return $this->render('index', [
            'hoy' => $hoy,
            'pendientes' => PagoCuota::pendientesDelUsuario(),
            'proximos' => Prestamo::proximosVencimientos($prestamos, self::DIAS_PROXIMOS, $hoy, $pagados, $fijos),
            'dias' => self::DIAS_PROXIMOS,
        ]);
    }
}
