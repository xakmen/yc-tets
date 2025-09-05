<?php

use yii\db\Migration;

class m250905_083936_create_user_activities extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->execute("
            CREATE TABLE user_activities (
              id INT AUTO_INCREMENT PRIMARY KEY,
              user_id INT NOT NULL,
              activity_type ENUM('banner_click','promo_view','message_read') NOT NULL,
              activity_data TEXT NULL,
              created_at DATETIME NOT NULL
            ) ENGINE=InnoDB
              DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $this->createIndex(
            'idx_user_activities_user_created',
            'user_activities',
            ['user_id', 'created_at']
        );
        $this->createIndex(
            'idx_user_activities_created',
            'user_activities',
            'created_at'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('user_activities');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m250905_083936_create_user_activities cannot be reverted.\n";

        return false;
    }
    */
}
