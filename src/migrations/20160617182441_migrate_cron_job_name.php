<?php

use Phinx\Migration\AbstractMigration;

class MigrateCronJobName extends AbstractMigration
{
    public function up()
    {
        $this->execute('UPDATE CronJobs SET id=CONCAT_WS(".", module, command)');
    }
}
