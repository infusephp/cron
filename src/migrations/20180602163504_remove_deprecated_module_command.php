<?php


use Phinx\Migration\AbstractMigration;

class RemoveDeprecatedModuleCommand extends AbstractMigration
{
    public function change()
    {
        $this->table('CronJobs')
             ->removeColumn('module')
             ->removeColumn('command')
             ->update();
    }
}
