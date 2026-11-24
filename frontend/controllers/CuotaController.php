<?php

namespace frontend\controllers;

use common\models\MedioPago;
use common\models\PagoCuota;
use common\models\PagoFijo;
use common\models\Prestamo;
use Yii;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * Préstamos, créditos y compras con tarjeta en cuotas.
 */
class CuotaController extends Controller
{
    const MESES_ATRAS = 6;
    const MESES_ADELANTE = 12;

    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => ['delete' => ['POST'], 'pagar' => ['POST']],
            ],
        ];
    }

    public function actionIndex()
    {
        $hoy = Prestamo::hoy();
        $prestamos = Prestamo::delUsuario()
            ->with('medioPago')
            ->orderBy(['fecha_primera_cuota' => SORT_DESC, 'id' => SORT_DESC])
            ->all();

        $totalPagado = 0.0;
        $totalRestante = 0.0;
        foreach ($prestamos as $p) {
            $pagadas = $p->cuotasPagadas($hoy);
            $totalPagado += $pagadas * (float) $p->monto_cuota;
            $totalRestante += max(0, $p->cantidad_cuotas - $pagadas) * (float) $p->monto_cuota;
        }

        $pagados = PagoCuota::pagados();

        return $this->render('index', [
            'pagados' => $pagados,
            'pendientes' => PagoCuota::pendientes($prestamos, $pagados, $hoy, PagoFijo::activos()),
            'hoy' => $hoy,
            'prestamos' => $prestamos,
            'totalPagado' => $totalPagado,
            'totalRestante' => $totalRestante,
            'calendario' => Prestamo::calendario($prestamos, self::MESES_ATRAS, self::MESES_ADELANTE, $hoy),
            'mesesAtras' => self::MESES_ATRAS,
            'mesesAdelante' => self::MESES_ADELANTE,
        ]);
    }

    public function actionCreate()
    {
        $model = new Prestamo(['user_id' => Yii::$app->user->id]);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Cuota agregada.');
            return $this->redirect(['index']);
        }

        return $this->render('create', ['model' => $model, 'mediosPago' => MedioPago::lista()]);
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Cuota actualizada.');
            return $this->redirect(['index']);
        }

        return $this->render('update', ['model' => $model, 'mediosPago' => MedioPago::lista()]);
    }

    /**
     * Marca como pagado un medio de pago (o préstamo) en un mes.
     * Con egreso=1 además registra el egreso en la caja.
     */
    public function actionPagar()
    {
        $request = Yii::$app->request;
        [$ok, $mensaje] = PagoCuota::registrar(
            Yii::$app->user->id,
            (string) $request->post('mes'),
            (string) $request->post('grupo'),
            (bool) $request->post('egreso', 1),
            $request->post('monto')
        );
        Yii::$app->session->setFlash($ok ? 'success' : 'error', $mensaje);

        return $this->redirect($request->referrer ?: ['index']);
    }

    public function actionDelete($id)
    {
        $this->findModel($id)->delete();
        Yii::$app->session->setFlash('success', 'Cuota eliminada.');
        return $this->redirect(['index']);
    }

    protected function findModel($id)
    {
        $model = Prestamo::delUsuario()->andWhere(['id' => $id])->one();
        if ($model === null) {
            throw new NotFoundHttpException('La cuota no existe.');
        }
        return $model;
    }
}
