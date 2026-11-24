<?php

namespace frontend\models;

use common\models\Movimiento;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * Filtros del listado de movimientos (solo los del usuario logueado).
 */
class MovimientoSearch extends Model
{
    public $tipo;
    public $categoria_id;
    public $medio_pago_id;
    public $motivo;
    public $mes; // formato YYYY-MM

    public function rules()
    {
        return [
            ['tipo', 'in', 'range' => array_keys(Movimiento::tipos())],
            [['categoria_id', 'medio_pago_id'], 'integer'],
            ['motivo', 'string'],
            ['mes', 'match', 'pattern' => '/^\d{4}-\d{2}$/'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'tipo' => 'Tipo',
            'categoria_id' => 'Categoría',
            'motivo' => 'Motivo',
            'mes' => 'Mes',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function query(array $params)
    {
        $this->load($params, '');
        if (!$this->validate()) {
            // filtros inválidos: se descartan y se muestra el mes actual
            $this->setAttributes(['tipo' => null, 'categoria_id' => null, 'medio_pago_id' => null, 'motivo' => null, 'mes' => null], false);
        }
        if (!$this->mes) {
            $this->mes = Movimiento::mesActual();
        }

        // Siempre se ve un solo mes (por defecto el actual)
        $query = Movimiento::delMes($this->mes)->with(['categoria', 'medioPago']);

        $query->andFilterWhere([
                'tipo' => $this->tipo,
                'categoria_id' => $this->categoria_id,
                'medio_pago_id' => $this->medio_pago_id,
            ])
            ->andFilterWhere(['like', 'motivo', $this->motivo]);

        return $query;
    }

    public function dataProvider($query)
    {
        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 20],
            'sort' => [
                'defaultOrder' => ['fecha' => SORT_DESC, 'id' => SORT_DESC],
                'attributes' => ['fecha', 'monto', 'tipo', 'motivo', 'categoria_id', 'medio_pago_id', 'id'],
            ],
        ]);
    }
}
