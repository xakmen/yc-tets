<?php

namespace app\commands;

use yii\console\Controller;
use yii\helpers\Console;
use MongoDB\BSON\UTCDateTime;

class SeedController extends Controller
{
    public function actionMysql($users = 1000, $activities = 2000)
    {
        $db = \Yii::$app->db;
        $this->stdout("Seeding MySQL: users={$users}, activities={$activities}\n", Console::FG_GREEN);

        $db->createCommand("TRUNCATE TABLE users")->execute();
        $db->createCommand("TRUNCATE TABLE user_activities")->execute();

        $now = gmdate('Y-m-d H:i:s');

        // Users
        $rows = [];
        for ($i = 1; $i <= $users; $i++) {
            $rows[] = [$i, "user{$i}@example.com", 1];
            if (count($rows) === 500 || $i === $users) {
                $db->createCommand()->batchInsert('users', ['id', 'email', 'is_active'], $rows)->execute();
                $rows = [];
            }
        }
        $this->stdout("Users inserted\n", Console::FG_GREEN);

        // Activities
        $types = ['banner_click', 'promo_view', 'message_read'];
        $rows = [];
        $nowTs = time();
        for ($i = 0; $i < $activities; $i++) {
            $uid = mt_rand(1, $users);
            $type = $types[array_rand($types)];
            $created = gmdate('Y-m-d H:i:s', $nowTs - mt_rand(0, 48 * 3600));
            $rows[] = [$uid, $type, null, $created];
            if (count($rows) === 500 || $i + 1 === $activities) {
                $db->createCommand()->batchInsert('user_activities', ['user_id', 'activity_type', 'activity_data', 'created_at'], $rows)->execute();
                $rows = [];
            }
        }
        $this->stdout("Activities inserted\n", Console::FG_GREEN);
    }

    public function actionMongo($logs = 3000)
    {
        $col = \Yii::$app->mongodb->getCollection('user_activity_logs');
        $col->createIndex(
            ['action' => 1, 'created_at' => 1, 'user_id' => 1],
            ['partialFilterExpression' => ['action' => 'view_product']]
        );

        $this->stdout("Seeding Mongo: {$logs} logs\n", Console::FG_GREEN);

        $col->remove([]);

        $now = time();
        $batch = [];
        for ($i = 0; $i < $logs; $i++) {
            $userId = mt_rand(1, 1000);
            $action = (mt_rand(1, 100) <= 40) ? 'view_product' : 'search';
            $createdTs = $now - mt_rand(0, 48 * 3600);
            $batch[] = [
                'user_id' => $userId,
                'action' => $action,
                'created_at' => new UTCDateTime($createdTs * 1000)
            ];
            if (count($batch) === 500 || $i + 1 === $logs) {
                $col->batchInsert($batch);
                $batch = [];
            }
        }

        $this->stdout("Mongo seeding done. Total docs: " . $col->count() . "\n", Console::FG_GREEN);
    }
}
