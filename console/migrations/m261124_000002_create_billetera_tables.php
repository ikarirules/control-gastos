<?php

use yii\db\Migration;

/**
 * Billeteras (Mercado Pago, Ualá, banco...) y el saldo que tenían cada día que se cargó.
 */
class m261124_000002_create_billetera_tables extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%billetera}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'nombre' => $this->string(100)->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);
        $this->createIndex('ux-billetera-user_id-nombre', '{{%billetera}}', ['user_id', 'nombre'], true);
        $this->addForeignKey('fk-billetera-user_id', '{{%billetera}}', 'user_id', '{{%user}}', 'id', 'CASCADE', 'CASCADE');

        // Un saldo por billetera y por día: si se carga dos veces el mismo día, se reemplaza.
        $this->createTable('{{%billetera_saldo}}', [
            'id' => $this->primaryKey(),
            'billetera_id' => $this->integer()->notNull(),
            'fecha' => $this->date()->notNull(),
            'monto' => $this->decimal(14, 2)->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);
        $this->createIndex('ux-billetera_saldo-billetera_id-fecha', '{{%billetera_saldo}}', ['billetera_id', 'fecha'], true);
        $this->addForeignKey('fk-billetera_saldo-billetera_id', '{{%billetera_saldo}}', 'billetera_id', '{{%billetera}}', 'id', 'CASCADE', 'CASCADE');
    }

    public function safeDown()
    {
        $this->dropTable('{{%billetera_saldo}}');
        $this->dropTable('{{%billetera}}');
    }
}
