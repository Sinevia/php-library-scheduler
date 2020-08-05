<?php

namespace Sinevia;

class Scheduler {

    /**
     * @var \Sinevia\SqlDb
     */
    protected static $db = null;
    protected static $scheduleStatusList = [
        'Active',
        'Paused',
        'Deleted',
    ];

    /**
     * @var string
     */
    protected static $tableRun = null;

    /**
     * @var string
     */
    protected static $tableSchedule = null;

    public static function configure($options) {
        $pdo = isset($options['pdo']) ? $options['pdo'] : null;
        self::$tableSchedule = isset($options['table_schedule']) ? $options['table_schedule'] : 'snv_scheduler_schedule';
        self::$tableRun = isset($options['table_run']) ? $options['table_run'] : 'snv_scheduler_run';
        if ($pdo == null) {
            throw new \RuntimeException('Required option "pdo" is missing');
        }
        self::$db = new \Sinevia\SqlDb();
        self::$db->setPdo($pdo);
        if (self::$db->table(self::$tableSchedule)->exists() == false) {
            self::install();
        }
    }

    public static function install() {
        if (self::$db->table(self::$tableSchedule)->exists() == false) {
            self::$db->table(self::$tableSchedule)
                    ->column('Id', 'INTEGER')
                    ->column('Status', 'STRING')
                    ->column('Title', 'STRING')
                    //->column('Schedule', 'STRING')
                    ->column('Command', 'STRING')
                    ->column('Arguments', 'TEXT')
                    ->column('Options', 'TEXT')
                    ->column('ScheduledOn', 'STRING')
                    ->column('IsRecurring', 'TEXT') // enum('No','Yes') [No]
                    ->column('Description', 'TEXT')
                    ->column('RunAt', 'DATETIME')
                    ->column('CreatedAt', 'DATETIME')
                    ->column('UpdatedAt', 'DATETIME')
                    ->column('DeletedAt', 'DATETIME')
                    ->create();
        }
        if (self::$db->table(self::$tableRun)->exists() == false) {
            self::$db->table(self::$tableRun)
                    ->column('Id', 'INTEGER')
                    ->column('ScheduleId', 'INTEGER')
                    ->column('Command', 'STRING')
                    ->column('Output', 'TEXT')
                    ->column('StartedAt', 'DATETIME')
                    ->column('EndedAt', 'DATETIME')
                    ->column('CreatedAt', 'DATETIME')
                    ->column('UpdatedAt', 'DATETIME')
                    ->column('DeletedAt', 'DATETIME')
                    ->create();
        }
    }

    function run() {
        $schedules = self::$db->table(self::$tableSchedule)
                ->where('Status', '=', 'Active')
                ->where('Command', '<>', '')
                ->where('ScheduledOn', '<>', '')
                ->select();

        foreach ($schedules as $schedule) {
            $cron = \Cron\CronExpression::factory($schedule['ScheduledOn']);

            if ($cron->isDue()) {
                self::comment(" - Executing schedule (id:" . $schedule['Id'] . "): " . $schedule['Title']);
                $baseDir = dirname(dirname(dirname(dirname(__DIR__))));
                
                $command = str_replace('{DIR}', $baseDir, $schedule['Command']);

                $runId = self::$db->table(self::$tableRun)->nextId('Id');
                self::comment(" -- Run ID: " . $runId);
                self::$db->table(self::$tableRun)->insert([
                    'Id' => $runId,
                    'ScheduleId' => $schedule['Id'],
                    'Command' => $command,
                    'StartedAt' => date('Y-m-d H:i:s'),
                    'CreatedAt' => date('Y-m-d H:i:s'),
                    'UpdatedAt' => date('Y-m-d H:i:s'),
                ]);

                self::comment(" -- Command: " . $command);

                exec($command, $output);
                $output = implode("\n", $output);
                
                self::comment($output);

                self::$db->table(self::$tableRun)
                        ->where('Id', '=', $runId)
                        ->update([
                            'Output' => $output,
                            'EndedAt' => date('Y-m-d H:i:s'),
                            'UpdatedAt' => date('Y-m-d H:i:s'),
                ]);
            } else {
                self::comment(" - Shedule (id:" . $schedule->Id . "): " . $schedule->Title . " NOT DUE until " . $cron->getNextRunDate()->format("Y-m-d H:i:s"));
            }
        }
    }

    protected static function comment($message) {
        echo date('Y-m-d H:i:s') . ' : ' . $message . "\n";
    }

}
