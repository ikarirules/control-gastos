<?php

use yii\db\Migration;

/**
 * Crea la tabla `movimiento` (ingresos y egresos de dinero).
 */
class m261123_000002_create_movimiento_table extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%movimiento}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'tipo' => "ENUM('ingreso','egreso') NOT NULL",
            'monto' => $this->decimal(14, 2)->notNull(),
            'fecha' => $this->dateTime()->notNull(),
            'motivo' => $this->string(255)->notNull(),
            'categoria_id' => $this->integer()->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->createIndex('idx-movimiento-user_fecha', '{{%movimiento}}', ['user_id', 'fecha']);
        $this->createIndex('idx-movimiento-categoria_id', '{{%movimiento}}', 'categoria_id');

        $this->addForeignKey(
            'fk-movimiento-user_id', '{{%movimiento}}', 'user_id',
            '{{%user}}', 'id', 'CASCADE', 'CASCADE'
        );
        // No se puede borrar una categoría que tenga movimientos
        $this->addForeignKey(
            'fk-movimiento-categoria_id', '{{%movimiento}}', 'categoria_id',
            '{{%categoria}}', 'id', 'RESTRICT', 'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropTable('{{%movimiento}}');
    }
}
