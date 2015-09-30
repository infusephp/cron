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

class CronJobNullFields extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $table = $this->table('CronJobs');
        $table->changeColumn('last_ran', 'integer', ['null' => true, 'default' => null])
              ->changeColumn('last_run_result', 'boolean', ['null' => true, 'default' => null])
              ->changeColumn('last_run_output', 'text', ['null' => true, 'default' => null])
              ->save();
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
    }
}
