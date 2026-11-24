<?php

use yii\db\Migration;

/**
 * Crea la tabla `prestamo` (préstamos y compras en cuotas) y copia los datos
 * desde la base gestor_cuotas, relacionando los usuarios por nombre de usuario.
 * La base gestor_cuotas no se modifica.
 */
class m261123_000004_create_prestamo_table extends Migration
{
    const BASE_ORIGEN = 'gestor_cuotas';

    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%prestamo}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'nombre' => $this->string(150)->notNull(),
            'fecha_primera_cuota' => $this->date()->notNull(),
            'monto_cuota' => $this->decimal(12, 2)->notNull(),
            'cantidad_cuotas' => $this->integer()->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->createIndex('idx-prestamo-user_id', '{{%prestamo}}', 'user_id');
        $this->addForeignKey(
            'fk-prestamo-user_id', '{{%prestamo}}', 'user_id',
            '{{%user}}', 'id', 'CASCADE', 'CASCADE'
        );

        $existe = $this->db->createCommand(
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = :db AND table_name = :t',
            [':db' => self::BASE_ORIGEN, ':t' => 'prestamo']
        )->queryScalar();

        if (!$existe) {
            echo '    > ' . self::BASE_ORIGEN . ".prestamo no existe, no se copian datos\n";
            return;
        }

        $origen = self::BASE_ORIGEN;
        $copiados = $this->db->createCommand("
            INSERT INTO {{%prestamo}} (user_id, nombre, fecha_primera_cuota, monto_cuota, cantidad_cuotas, created_at, updated_at)
            SELECT u.id, p.nombre, p.fecha_primera_cuota, p.monto_cuota, p.cantidad_cuotas, p.created_at, p.updated_at
            FROM `{$origen}`.prestamo p
            JOIN `{$origen}`.user gu ON gu.id = p.user_id
            JOIN {{%user}} u ON u.username = gu.username
            ORDER BY p.id
        ")->execute();

        echo "    > copiados {$copiados} préstamos desde {$origen}.prestamo\n";
    }

    public function safeDown()
    {
        $this->dropTable('{{%prestamo}}');
    }
}
