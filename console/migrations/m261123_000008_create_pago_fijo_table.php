<?php

use yii\db\Migration;

/**
 * Pagos fijos mensuales (alquiler, luz, internet...) con día de vencimiento estimado.
 * Se recuerdan como las cuotas: `pago_cuota.grupo = 'f<id>'`.
 */
class m261123_000008_create_pago_fijo_table extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%pago_fijo}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'nombre' => $this->string(100)->notNull(),
            'monto' => $this->decimal(14, 2)->notNull()->comment('Monto estimado'),
            'dia_vencimiento' => $this->tinyInteger()->notNull(),
            'categoria_id' => $this->integer()->null(),
            'medio_pago_id' => $this->integer()->null(),
            'activo' => $this->boolean()->notNull()->defaultValue(1),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->createIndex('ux-pago_fijo-user_id-nombre', '{{%pago_fijo}}', ['user_id', 'nombre'], true);
        $this->addForeignKey('fk-pago_fijo-user_id', '{{%pago_fijo}}', 'user_id', '{{%user}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk-pago_fijo-categoria_id', '{{%pago_fijo}}', 'categoria_id', '{{%categoria}}', 'id', 'SET NULL', 'CASCADE');
        $this->addForeignKey('fk-pago_fijo-medio_pago_id', '{{%pago_fijo}}', 'medio_pago_id', '{{%medio_pago}}', 'id', 'SET NULL', 'CASCADE');

        $this->addColumn('{{%pago_cuota}}', 'pago_fijo_id', $this->integer()->null()->after('prestamo_id'));
        $this->addForeignKey('fk-pago_cuota-pago_fijo_id', '{{%pago_cuota}}', 'pago_fijo_id', '{{%pago_fijo}}', 'id', 'CASCADE', 'CASCADE');
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-pago_cuota-pago_fijo_id', '{{%pago_cuota}}');
        $this->dropColumn('{{%pago_cuota}}', 'pago_fijo_id');
        $this->dropTable('{{%pago_fijo}}');
    }
}
