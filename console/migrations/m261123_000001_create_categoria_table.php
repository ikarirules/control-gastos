<?php

use yii\db\Migration;

/**
 * Crea la tabla `categoria` con categorías iniciales.
 */
class m261123_000001_create_categoria_table extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%categoria}}', [
            'id' => $this->primaryKey(),
            'nombre' => $this->string(100)->notNull()->unique(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $now = time();
        $categorias = ['Gasto fijo', 'Gasto particular', 'Ropa', 'Comida', 'Transporte', 'Sueldo', 'Otros'];
        $this->batchInsert(
            '{{%categoria}}',
            ['nombre', 'created_at', 'updated_at'],
            array_map(function ($nombre) use ($now) {
                return [$nombre, $now, $now];
            }, $categorias)
        );
    }

    public function safeDown()
    {
        $this->dropTable('{{%categoria}}');
    }
}
