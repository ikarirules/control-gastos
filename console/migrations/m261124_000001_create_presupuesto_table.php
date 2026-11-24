<?php

use yii\db\Migration;

/**
 * Presupuesto mensual estimado por categoría (gastos variables: súper, combustible...).
 */
class m261124_000001_create_presupuesto_table extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%presupuesto}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'categoria_id' => $this->integer()->notNull(),
            'monto' => $this->decimal(14, 2)->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->createIndex('ux-presupuesto-user_id-categoria_id', '{{%presupuesto}}', ['user_id', 'categoria_id'], true);
        $this->addForeignKey('fk-presupuesto-user_id', '{{%presupuesto}}', 'user_id', '{{%user}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk-presupuesto-categoria_id', '{{%presupuesto}}', 'categoria_id', '{{%categoria}}', 'id', 'CASCADE', 'CASCADE');
    }

    public function safeDown()
    {
        $this->dropTable('{{%presupuesto}}');
    }
}
