<?php

use Phinx\Migration\AbstractMigration;

class RenameLastRunResult extends AbstractMigration
{
    public function change()
    {
        $this->table('CronJobs')
             ->renameColumn('last_run_result', 'last_run_succeeded')
             ->update();
    }
}
