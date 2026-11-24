<?php

namespace frontend\controllers;

use common\models\MedioPago;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * Administración de medios de pago (efectivo, tarjetas, transferencia...).
 */
class MedioPagoController extends Controller
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
        $dataProvider = new ActiveDataProvider([
            'query' => MedioPago::delUsuario(),
            'pagination' => false,
            'sort' => [
                'defaultOrder' => ['nombre' => SORT_ASC],
                'attributes' => ['nombre', 'dia_vencimiento'],
            ],
        ]);

        return $this->render('index', ['dataProvider' => $dataProvider]);
    }

    public function actionCreate()
    {
        if (MedioPago::limiteAlcanzado()) {
            Yii::$app->session->setFlash(
                'error',
                'Llegaste al máximo de ' . MedioPago::MAXIMO_POR_USUARIO . ' medios de pago. Eliminá o renombrá uno existente.'
            );
            return $this->redirect(['index']);
        }

        $model = new MedioPago(['user_id' => Yii::$app->user->id]);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Medio de pago creado.');
            return $this->redirect(['index']);
        }

        return $this->render('create', ['model' => $model]);
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Medio de pago actualizado.');
            return $this->redirect(['index']);
        }

        return $this->render('update', ['model' => $model]);
    }

    public function actionDelete($id)
    {
        $model = $this->findModel($id);

        if ($model->enUso()) {
            Yii::$app->session->setFlash(
                'error',
                'No se puede eliminar "' . $model->nombre . '" porque tiene movimientos o cuotas asociados.'
            );
        } else {
            $model->delete();
            Yii::$app->session->setFlash('success', 'Medio de pago eliminado.');
        }

        return $this->redirect(['index']);
    }

    protected function findModel($id)
    {
        $model = MedioPago::findOne(['id' => $id, 'user_id' => Yii::$app->user->id]);
        if ($model === null) {
            throw new NotFoundHttpException('El medio de pago no existe.');
        }
        return $model;
    }
}
