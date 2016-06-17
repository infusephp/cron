<?php

use Phinx\Migration\AbstractMigration;

class ChangePrimaryKey extends AbstractMigration
{
    public function up()
    {
        $this->execute('ALTER TABLE CronJobs DROP PRIMARY KEY, ADD PRIMARY KEY (id)');

        $this->table('CronJobs')
             ->changeColumn('module', 'string', ['default' => null, 'null' => true])
             ->changeColumn('command', 'string', ['default' => null, 'null' => true])
             ->update();
    }
}
