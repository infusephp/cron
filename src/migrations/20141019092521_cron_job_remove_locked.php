<?php

use Phinx\Migration\AbstractMigration;

class CronJobRemoveLocked extends AbstractMigration
{   
    /**
     * Migrate Up.
     */
    public function up()
    {
        $table = $this->table('CronJobs');
        $table->removeColumn('locked')
              ->save();
    }

    /**
     * Migrate Down.
     */
    public function down()
    {

    }
}