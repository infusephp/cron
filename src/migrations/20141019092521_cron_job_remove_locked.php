<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @link http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
use Phinx\Migration\AbstractMigration;

class CronJobRemoveLocked extends AbstractMigration
{
    public function up()
    {
        $table = $this->table('CronJobs');
        $table->removeColumn('locked')
              ->save();
    }
}
