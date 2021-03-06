<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @see http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
use Phinx\Migration\AbstractMigration;

class CronJob extends AbstractMigration
{
    public function change()
    {
        if (!$this->hasTable('CronJobs')) {
            $table = $this->table('CronJobs', ['id' => false, 'primary_key' => ['module', 'command']]);
            $table->addColumn('module', 'string', ['length' => 100])
                  ->addColumn('command', 'string', ['length' => 100])
                  ->addColumn('last_ran', 'integer')
                  ->addColumn('last_run_result', 'boolean')
                  ->addColumn('last_run_output', 'text')
                  ->addColumn('locked', 'string', ['length' => 32])
                  ->create();
        }
    }
}
