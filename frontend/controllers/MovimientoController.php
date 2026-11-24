<?php

namespace frontend\controllers;

use common\models\Categoria;
use common\models\MedioPago;
use common\models\Movimiento;
use frontend\models\MovimientoSearch;
use Yii;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * Ingresos y egresos de dinero del usuario logueado.
 */
class MovimientoController extends Controller
{
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => ['delete' => ['POST']],
            ],
        ];
    }

    public function actionIndex()
    {
        $searchModel = new MovimientoSearch();
        $query = $searchModel->query(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $searchModel->dataProvider($query),
            'totales' => Movimiento::caja($searchModel->mes, $query),
            'categorias' => Categoria::lista(),
            'mediosPago' => MedioPago::lista(),
        ]);
    }

    /** Resumen de ingresos, egresos y saldo de cada mes. */
    public function actionHistorial()
    {
        return $this->render('historial', ['meses' => Movimiento::historial()]);
    }

    /**
     * @param string|null $tipo para abrir el formulario con "ingreso" o "egreso" ya elegido
     */
    public function actionCreate($tipo = null)
    {
        $model = new Movimiento(['user_id' => Yii::$app->user->id]);
        if (isset(Movimiento::tipos()[$tipo])) {
            $model->tipo = $tipo;
        }

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Movimiento registrado.');
            return $this->redirect(['index']);
        }

        return $this->render('create', [
            'model' => $model,
            'categorias' => Categoria::lista(),
            'mediosPago' => MedioPago::lista(),
        ]);
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Movimiento actualizado.');
            return $this->redirect(['index', 'mes' => $model->mes()]);
        }

        return $this->render('update', [
            'model' => $model,
            'categorias' => Categoria::lista(),
            'mediosPago' => MedioPago::lista(),
        ]);
    }

    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        $model->delete();
        Yii::$app->session->setFlash('success', 'Movimiento eliminado.');
        return $this->redirect(['index', 'mes' => $model->mes()]);
    }

    /**
     * Solo devuelve movimientos del usuario logueado.
     */
    protected function findModel($id)
    {
        $model = Movimiento::findOne(['id' => $id, 'user_id' => Yii::$app->user->id]);
        if ($model === null) {
            throw new NotFoundHttpException('El movimiento no existe.');
        }
        return $model;
    }
}
