<?php

namespace app\commands;

use yii\console\Controller;
use yii\console\ExitCode;
use MongoDB\BSON\UTCDateTime;

class CampaignController extends Controller
{
    public $hours = 24;
    public $batch = 5000;
    public $dryRun = 1;

    public function options($actionID)
    {
        return ['hours', 'batch', 'dryRun'];
    }

    public function actionSendEngagementEmails(): int
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $from = $now->sub(new \DateInterval('PT' . (int)$this->hours . 'H'));

        $ws = $from->format('Y-m-d H:i:s');
        $we = $now->format('Y-m-d H:i:s');

        $this->stdout("Window: $ws .. $we (UTC)\n");

        $mongo = \Yii::$app->mongodb->getCollection('user_activity_logs');
        $pipeline = [
            ['$match' => [
                'action' => 'view_product',
                'created_at' => [
                    '$gte' => new UTCDateTime($from->getTimestamp() * 1000),
                    '$lt'  => new UTCDateTime($now->getTimestamp() * 1000),
                ],
            ]],
            ['$group' => ['_id' => '$user_id', 'cnt' => ['$sum' => 1]]],
            ['$match' => ['cnt' => ['$gte' => 3]]],
            ['$project' => ['user_id' => '$_id', '_id' => 0]]
        ];
        $mongoUsers = array_map(fn($d) => (int)$d['user_id'], iterator_to_array($mongo->aggregate($pipeline)));
        $this->stdout("Mongo candidates: " . count($mongoUsers) . "\n");

        $sqlUsers = \Yii::$app->db->createCommand("
            SELECT DISTINCT user_id
            FROM user_activities
            WHERE created_at >= :ws AND created_at < :we
        ", [':ws' => $ws, ':we' => $we])->queryColumn();
        $this->stdout("MySQL candidates: " . count($sqlUsers) . "\n");

        $userIds = array_values(array_unique(array_merge($mongoUsers, $sqlUsers)));
        $this->stdout("Total unique candidates: " . count($userIds) . "\n");

        $chunks = array_chunk($userIds, (int)$this->batch);
        $sent = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($chunks as $i => $chunk) {
            $rows = \Yii::$app->db->createCommand("
                SELECT id, email FROM users
                WHERE id IN (" . implode(',', $chunk) . ") AND is_active=1
            ")->queryAll();

            if ($this->dryRun) {
                $this->stdout("DRY-RUN batch " . ($i + 1) . ": would send " . count($rows) . " emails\n");
                $skipped += count($rows);
                continue;
            }

            foreach ($rows as $row) {
                $ok = $this->sendEmail($row['email']);
                $ok ? $sent++ : $failed++;
            }

            usleep(200000);
        }

        $this->stdout("Done. sent=$sent, dry-skipped=$skipped, failed=$failed\n");
        return ExitCode::OK;
    }

    private function sendEmail(string $email): bool
    {
        try {
            return \Yii::$app->mailer->compose()
                ->setTo($email)
                ->setFrom(['noreply@example.com' => 'Promo'])
                ->setSubject('Test Campaign')
                ->setTextBody('Hello, this is a test email.')
                ->send();
        } catch (\Throwable $e) {
            \Yii::error("Send fail to $email: " . $e->getMessage(), __METHOD__);
            return false;
        }
    }
}
