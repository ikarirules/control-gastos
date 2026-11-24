<?php

use yii\db\Migration;

/**
 * Saldo inicial del usuario: la plata que ya tenía antes de empezar a
 * registrar movimientos. Se suma al "saldo anterior" de cada mes.
 */
class m261123_000007_add_saldo_inicial_to_user extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%user}}', 'saldo_inicial', $this->decimal(14, 2)->notNull()->defaultValue(0)->after('status'));
    }

    public function safeDown()
    {
        $this->dropColumn('{{%user}}', 'saldo_inicial');
    }
}
