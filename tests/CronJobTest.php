<?php


use app\cron\models\CronJob;

class CronJobTest extends \PHPUnit_Framework_TestCase
{
    public static $job;

    public function testHasPermission()
    {
        $job = new CronJob();

        $this->assertFalse( $job->can( 'create', TestBootstrap::app( 'user' ) ) );
    }

    public function testCreate()
    {
        self::$job = new CronJob();
        self::$job->grantAllPermissions();
        $this->assertTrue( self::$job->create( [
            'module' => 'test',
            'command' => 'test' ] ) );
    }

    /**
	 * @depends testCreate
	 */
    public function testDelete()
    {
        $this->assertTrue( self::$job->delete() );
    }
}
