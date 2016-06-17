<?php

use Phinx\Migration\AbstractMigration;

class AddCronJobName extends AbstractMigration
{
    public function change()
    {
        $this->table('CronJobs')
             ->addColumn('id', 'string')
             ->update();
    }
}
