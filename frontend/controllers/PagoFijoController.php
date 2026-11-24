<?php

namespace frontend\controllers;

use common\models\Categoria;
use common\models\MedioPago;
use common\models\PagoFijo;
use Yii;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * Pagos fijos mensuales (alquiler, luz, internet...) con vencimiento estimado.
 */
class PagoFijoController extends Controller
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
        $fijos = PagoFijo::delUsuario()
            ->with(['categoria', 'medioPago'])
            ->orderBy(['activo' => SORT_DESC, 'dia_vencimiento' => SORT_ASC, 'nombre' => SORT_ASC])
            ->all();

        return $this->render('index', ['fijos' => $fijos]);
    }

    public function actionCreate()
    {
        if (PagoFijo::limiteAlcanzado()) {
            Yii::$app->session->setFlash(
                'error',
                'Llegaste al máximo de ' . PagoFijo::MAXIMO_POR_USUARIO . ' pagos fijos. Eliminá o modificá uno existente.'
            );
            return $this->redirect(['index']);
        }

        $model = new PagoFijo(['user_id' => Yii::$app->user->id, 'activo' => 1]);
        $gastoFijo = Categoria::delUsuario()->andWhere(['nombre' => PagoFijo::CATEGORIA])->one();
        if ($gastoFijo) {
            $model->categoria_id = $gastoFijo->id;
        }

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Pago fijo creado.');
            return $this->redirect(['index']);
        }

        return $this->render('create', $this->datosForm($model));
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Pago fijo actualizado.');
            return $this->redirect(['index']);
        }

        return $this->render('update', $this->datosForm($model));
    }

    /**
     * Borra el pago fijo y su registro de meses pagados.
     * Los egresos que ya se cargaron en la caja quedan.
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();
        Yii::$app->session->setFlash('success', 'Pago fijo eliminado. Los egresos ya registrados siguen en la caja.');
        return $this->redirect(['index']);
    }

    private function datosForm(PagoFijo $model)
    {
        return [
            'model' => $model,
            'categorias' => Categoria::lista(),
            'mediosPago' => MedioPago::lista(),
        ];
    }

    protected function findModel($id)
    {
        $model = PagoFijo::findOne(['id' => $id, 'user_id' => Yii::$app->user->id]);
        if ($model === null) {
            throw new NotFoundHttpException('El pago fijo no existe.');
        }
        return $model;
    }
}
