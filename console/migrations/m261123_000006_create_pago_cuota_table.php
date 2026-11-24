<?php

use yii\db\Migration;

/**
 * Crea la tabla `pago_cuota`: recuerda si ya se pagó un medio de pago
 * (o un préstamo sin medio de pago) en un mes. Si existe la fila, está pagado.
 */
class m261123_000006_create_pago_cuota_table extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%pago_cuota}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'mes' => $this->char(7)->notNull()->comment('YYYY-MM'),
            // 'm<id>' = medio de pago, 'p<id>' = préstamo sin medio de pago
            'grupo' => $this->string(20)->notNull(),
            'medio_pago_id' => $this->integer()->null(),
            'prestamo_id' => $this->integer()->null(),
            'monto' => $this->decimal(14, 2)->notNull(),
            // Egreso generado al pagar (null si se marcó como pagado sin egreso)
            'movimiento_id' => $this->integer()->null(),
            'created_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->createIndex('ux-pago_cuota-user_mes_grupo', '{{%pago_cuota}}', ['user_id', 'mes', 'grupo'], true);

        $fks = [
            'user_id' => 'user',
            'medio_pago_id' => 'medio_pago',
            'prestamo_id' => 'prestamo',
            // Si se borra el egreso, el pago vuelve a quedar pendiente
            'movimiento_id' => 'movimiento',
        ];
        foreach ($fks as $columna => $tabla) {
            $this->addForeignKey(
                "fk-pago_cuota-$columna", '{{%pago_cuota}}', $columna,
                "{{%$tabla}}", 'id', 'CASCADE', 'CASCADE'
            );
        }
    }

    public function safeDown()
    {
        $this->dropTable('{{%pago_cuota}}');
    }
}
