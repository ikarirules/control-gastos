<?php

use yii\db\Migration;
use yii\db\Query;

/**
 * Hace que las categorías sean de cada usuario.
 *
 * Las categorías existentes pasan al primer usuario. Si hay más usuarios,
 * cada uno recibe una copia y sus movimientos se reasignan a esa copia.
 */
class m261123_000003_add_user_id_to_categoria extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%categoria}}', 'user_id', $this->integer()->null()->after('id'));

        $userIds = (new Query())->select('id')->from('{{%user}}')->orderBy('id')->column($this->db);
        $categorias = (new Query())->from('{{%categoria}}')->all($this->db);

        if ($userIds) {
            $primero = array_shift($userIds);
            $this->update('{{%categoria}}', ['user_id' => $primero]);

            foreach ($userIds as $userId) {
                foreach ($categorias as $cat) {
                    $this->insert('{{%categoria}}', [
                        'user_id' => $userId,
                        'nombre' => $cat['nombre'],
                        'created_at' => $cat['created_at'],
                        'updated_at' => $cat['updated_at'],
                    ]);
                    $nuevoId = $this->db->getLastInsertID();
                    $this->update(
                        '{{%movimiento}}',
                        ['categoria_id' => $nuevoId],
                        ['user_id' => $userId, 'categoria_id' => $cat['id']]
                    );
                }
            }
        } else {
            // Sin usuarios no hay a quién asignarlas
            $this->delete('{{%categoria}}');
        }

        $this->alterColumn('{{%categoria}}', 'user_id', $this->integer()->notNull());
        $this->dropIndex('nombre', '{{%categoria}}');
        $this->createIndex('ux-categoria-user_nombre', '{{%categoria}}', ['user_id', 'nombre'], true);
        $this->addForeignKey(
            'fk-categoria-user_id', '{{%categoria}}', 'user_id',
            '{{%user}}', 'id', 'CASCADE', 'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-categoria-user_id', '{{%categoria}}');
        $this->dropIndex('ux-categoria-user_nombre', '{{%categoria}}');
        $this->dropColumn('{{%categoria}}', 'user_id');
        // Puede fallar si al volver atrás hay nombres repetidos entre usuarios
        $this->createIndex('nombre', '{{%categoria}}', 'nombre', true);
    }
}
