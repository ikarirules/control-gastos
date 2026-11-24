<?php

namespace frontend\controllers;

use common\models\Presupuesto;
use Yii;
use yii\web\Controller;

/**
 * Proyección del mes: gastos variables estimados por categoría + cuotas + pagos fijos.
 */
class ProyeccionController extends Controller
{
    public function actionIndex()
    {
        $errores = [];
        $montos = Presupuesto::delUsuario();

        if (Yii::$app->request->isPost) {
            $enviados = (array) Yii::$app->request->post('monto', []);
            $errores = Presupuesto::guardar($enviados);
            if (!$errores) {
                Yii::$app->session->setFlash('success', 'Estimados guardados.');
                return $this->redirect(['index']);
            }
            $montos = $enviados; // mantener lo que escribió
            Yii::$app->session->setFlash('error', 'Revisá los montos marcados.');
        }

        return $this->render('index', [
            'categorias' => Presupuesto::categorias(),
            'montos' => $montos,
            'errores' => $errores,
            'sugerencias' => Presupuesto::sugerencias(),
            'gastado' => Presupuesto::gastadoDelMes(),
            'proyeccion' => Presupuesto::proyeccion(),
        ]);
    }
}
