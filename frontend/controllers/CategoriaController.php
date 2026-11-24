<?php

namespace frontend\controllers;

use common\models\Categoria;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * Administración de categorías (Gasto fijo, Ropa, etc.).
 */
class CategoriaController extends Controller
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
            'query' => Categoria::delUsuario(),
            'pagination' => ['pageSize' => 50],
            'sort' => ['defaultOrder' => ['nombre' => SORT_ASC], 'attributes' => ['nombre']],
        ]);

        return $this->render('index', ['dataProvider' => $dataProvider]);
    }

    public function actionCreate()
    {
        if (Categoria::limiteAlcanzado()) {
            Yii::$app->session->setFlash(
                'error',
                'Llegaste al máximo de ' . Categoria::MAXIMO_POR_USUARIO . ' categorías. Eliminá o renombrá una existente.'
            );
            return $this->redirect(['index']);
        }

        $model = new Categoria(['user_id' => Yii::$app->user->id]);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Categoría creada.');
            return $this->redirect(['index']);
        }

        return $this->render('create', ['model' => $model]);
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Categoría actualizada.');
            return $this->redirect(['index']);
        }

        return $this->render('update', ['model' => $model]);
    }

    public function actionDelete($id)
    {
        $model = $this->findModel($id);

        if ($model->getMovimientos()->exists()) {
            Yii::$app->session->setFlash(
                'error',
                'No se puede eliminar "' . $model->nombre . '" porque tiene movimientos asociados.'
            );
        } else {
            $model->delete();
            Yii::$app->session->setFlash('success', 'Categoría eliminada.');
        }

        return $this->redirect(['index']);
    }

    protected function findModel($id)
    {
        // Solo categorías del usuario logueado
        $model = Categoria::findOne(['id' => $id, 'user_id' => Yii::$app->user->id]);
        if ($model === null) {
            throw new NotFoundHttpException('La categoría no existe.');
        }
        return $model;
    }
}
