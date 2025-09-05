<?php

use yii\db\Migration;

class m250905_083739_create_users extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->execute("
            CREATE TABLE users (
            id INT PRIMARY KEY,
            email VARCHAR(255) NOT NULL,
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME NULL,
            updated_at DATETIME NULL
            ) ENGINE=InnoDB
            DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $this->createIndex('idx_users_is_active', 'users', ['is_active', 'id']);
        $this->createIndex('idx_users_email', 'users', ['email']);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('users');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m250905_083739_create_users cannot be reverted.\n";

        return false;
    }
    */
}
