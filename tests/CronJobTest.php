<?php

use infuse\Database;

use app\cront\models\CronJob;

class GroupMemberTest extends \PHPUnit_Framework_TestCase
{
	static $job;

	function testHasPermission()
	{
		$job = new CronJob;

		$this->assertFalse( $job->can( 'create', TestBootstrap::app( 'user' ) ) );
	}

	function testCreate()
	{
		self::$job = new CronJob;
		self::$job->grantAllPermissions();
		$this->assertTrue( self::$job->create( [
			'module' => 'test',
			'command' => 'test' ] ) );
	}

	/**
	 * @depends testCreate
	 */
	function testDelete()
	{
		$this->assertTrue( self::$job->delete() );
	}
}