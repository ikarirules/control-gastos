<?php

use yii\db\Migration;

/**
 * Se saca el saldo inicial: la plata que había antes se carga como un ingreso común.
 */
class m261123_000009_drop_saldo_inicial_from_user extends Migration
{
    public function safeUp()
    {
        $this->dropColumn('{{%user}}', 'saldo_inicial');
    }

    public function safeDown()
    {
        $this->addColumn('{{%user}}', 'saldo_inicial', $this->decimal(14, 2)->notNull()->defaultValue(0)->after('status'));
    }
}
