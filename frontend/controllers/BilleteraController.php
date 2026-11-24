<?php

namespace frontend\controllers;

use common\models\Billetera;
use common\models\BilleteraSaldo;
use common\models\Movimiento;
use frontend\models\BilleteraForm;
use Yii;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * Billeteras: cargar cuánto hay en cada una y compararlo con la carga anterior.
 */
class BilleteraController extends Controller
{
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => ['delete' => ['POST'], 'delete-saldo' => ['POST']],
            ],
        ];
    }

    public function actionIndex($billetera = null)
    {
        $form = new BilleteraForm(['billetera_id' => $billetera]);

        if ($form->load(Yii::$app->request->post())) {
            $saldo = $form->guardar();
            if ($saldo) {
                Yii::$app->session->setFlash('success', 'Saldo de "' . $saldo->billetera->nombre . '" '
                    . ($form->reemplazado ? 'actualizado (ya habías cargado uno hoy).' : 'guardado.'));
                return $this->redirect(['index']);
            }
        }

        return $this->render('index', [
            'form' => $form,
            'lista' => Billetera::lista(),
            'limite' => Billetera::limiteAlcanzado(),
            'resumen' => Billetera::resumen(),
            'meses' => Billetera::porMes(),
            'caja' => Movimiento::caja(),
        ]);
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Billetera renombrada.');
            return $this->redirect(['index']);
        }

        return $this->render('update', ['model' => $model]);
    }

    /** Borra la billetera con todos sus saldos cargados. */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        $model->delete();
        Yii::$app->session->setFlash('success', 'Billetera "' . $model->nombre . '" eliminada.');
        return $this->redirect(['index']);
    }

    /** Borra un saldo cargado por error. */
    public function actionDeleteSaldo($id)
    {
        $saldo = BilleteraSaldo::find()
            ->joinWith('billetera b', false)
            ->where(['billetera_saldo.id' => $id, 'b.user_id' => Yii::$app->user->id])
            ->one();
        if ($saldo === null) {
            throw new NotFoundHttpException('El registro no existe.');
        }
        $saldo->delete();
        Yii::$app->session->setFlash('success', 'Registro eliminado.');
        return $this->redirect(['index']);
    }

    protected function findModel($id)
    {
        $model = Billetera::findOne(['id' => $id, 'user_id' => Yii::$app->user->id]);
        if ($model === null) {
            throw new NotFoundHttpException('La billetera no existe.');
        }
        return $model;
    }
}
