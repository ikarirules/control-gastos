<?php

use yii\db\Migration;
use yii\db\Query;

/**
 * Crea la tabla `medio_pago` (por usuario, con día de vencimiento aproximado)
 * y agrega `medio_pago_id` opcional a `movimiento` y `prestamo`.
 */
class m261123_000005_create_medio_pago_table extends Migration
{
    /** Medios iniciales: nombre => día de vencimiento (null = no vence) */
    const POR_DEFECTO = [
        'Efectivo' => null,
        'Tarjeta de débito' => null,
        'Transferencia' => null,
        'Tarjeta de crédito' => 10,
    ];

    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%medio_pago}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'nombre' => $this->string(100)->notNull(),
            'dia_vencimiento' => $this->tinyInteger()->unsigned()->null(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->createIndex('ux-medio_pago-user_nombre', '{{%medio_pago}}', ['user_id', 'nombre'], true);
        $this->addForeignKey(
            'fk-medio_pago-user_id', '{{%medio_pago}}', 'user_id',
            '{{%user}}', 'id', 'CASCADE', 'CASCADE'
        );

        $now = time();
        foreach ((new Query())->select('id')->from('{{%user}}')->column($this->db) as $userId) {
            foreach (self::POR_DEFECTO as $nombre => $dia) {
                $this->insert('{{%medio_pago}}', [
                    'user_id' => $userId,
                    'nombre' => $nombre,
                    'dia_vencimiento' => $dia,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        foreach (['movimiento' => 'categoria_id', 'prestamo' => 'cantidad_cuotas'] as $tabla => $despuesDe) {
            $this->addColumn("{{%$tabla}}", 'medio_pago_id', $this->integer()->null()->after($despuesDe));
            $this->createIndex("idx-$tabla-medio_pago_id", "{{%$tabla}}", 'medio_pago_id');
            // No se puede borrar un medio de pago que esté en uso
            $this->addForeignKey(
                "fk-$tabla-medio_pago_id", "{{%$tabla}}", 'medio_pago_id',
                '{{%medio_pago}}', 'id', 'RESTRICT', 'CASCADE'
            );
        }
    }

    public function safeDown()
    {
        foreach (['movimiento', 'prestamo'] as $tabla) {
            $this->dropForeignKey("fk-$tabla-medio_pago_id", "{{%$tabla}}");
            $this->dropIndex("idx-$tabla-medio_pago_id", "{{%$tabla}}");
            $this->dropColumn("{{%$tabla}}", 'medio_pago_id');
        }
        $this->dropTable('{{%medio_pago}}');
    }
}
