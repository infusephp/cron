<?php

/**
 * @package infuse\framework
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.16
 * @copyright 2013 Jared King
 * @license MIT
 */
 
namespace app\cron\models;

use infuse\Database;
use infuse\Model;
use infuse\Util;

use app\cron\libs\Cron;
use app\cron\libs\CronDate;

class CronJob extends Model
{
	public static $scaffoldApi = true;

	public static $properties = [
		'module' => [
			'type' => 'text',
			'length' => 100,
			'mutable' => true,
			'required' => true
		],
		'command' => [
			'type' => 'text',
			'length' => 100,
			'mutable' => true,
			'required' => true
		],
		'last_ran' => [
			'type' => 'date',
			'admin_type' => 'datepicker'
		],
		'last_run_result' => [
			'type' => 'boolean',
			'default' => false,
			'admin_type' => 'checkbox'
		],
		'last_run_output' => [
			'type' => 'longtext',
			'admin_type' => 'textarea',
			'admin_hidden_property' => true,
			'admin_html' => '<pre>{last_run_output}</pre>',
			'admin_truncate' => false,
		],
		'locked' => [
			'type' => 'text',
			'length' => '32',
			'db_type' => 'char',
			'default' => '00000000000000000000000000000000',
			'admin_hidden_property' => true,
		]
	];

	private static $redis;
	private $hasLock;

	static function idProperty()
	{
		return [ 'module', 'command' ];
	}

	protected function hasPermission( $permission, Model $requester )
	{
		return $requester->isAdmin();
	}

	function __construct( $id = false )
	{
		parent::__construct( $id );

		$this->lockName = $this->app[ 'config' ]->get( 'site.host-name' ) . ':' .
			'cron.' . $this->module . '.' . $this->command;
	}

	/**
	 * Attempts to get the global lock for this job
	 *
	 * @param int $expires time in which the lock expires
	 *
	 * @return boolean
	 */
	function getLock( $expires = 0 )
	{
		// do not lock if expiry time is 0
		if( $expires <= 0 )
			return true;

		$r = $this->app[ 'redis' ];

		if( $r->setnx( $this->lockName, $expires ) )
		{
			$r->expire( $this->lockName, $expires );

			$this->hasLock = true;

			return true;
		}

		return false;
	}

	function releaseLock()
	{
		if( !$this->hasLock )
			return;

		$this->app[ 'redis' ]->del( $this->lockName );

		$this->hasLock = false;
	}

	/**
	 * Gets all jobs that are due to be ran and current lock values
	 *
	 * @return array(model => CronJob, lock => lock value)
	 */
	static function overdueJobs()
	{
		$jobsFromConfig = (array)self::$injectedApp[ 'config' ]->get( 'cron' );

		$jobs = [];

		// round current time down to nearest minute
		$start = floor( time() / 60 ) * 60;

		foreach( $jobsFromConfig as $job )
		{
			// check if overdue
			$nextRun = self::calcNextRun( $job );

			if( $nextRun > $start )
				continue;

			// check if model has already been created for the job
			$model = new CronJob( [ $job[ 'module' ], $job[ 'command' ] ] );

			// TODO why is this not working
			if( !$model->exists() )
			{
				$model = new CronJob;
				$model->create( [
					'module' => $job[ 'module' ],
					'command' => $job[ 'command' ] ] );
			}

			$jobs[] = [
				'model' => $model,
				'expires' => (int)Util::array_value( $job, 'expires' ) ];
		}

		return $jobs;
	}
	
	/**
	 * Generates a timestamp from cron date parameters
	 *
	 * @param array $params cron date parameters (minute, hour, day, month, week)
	 *
	 * @return int timestamp
	 */
	private static function calcNextRun( array $params )
	{
		$cron_date = new CronDate( time() );
		$cron_date->second = 0;
		
		if( $params[ 'minute' ] == '*' ) $params[ 'minute' ] = null;
		if( $params[ 'hour' ] == '*' ) $params[ 'hour' ] = null;
		if( $params[ 'day' ] == '*' ) $params[ 'day' ] = null;
		if( $params[ 'month' ] == '*' ) $params[ 'month' ] = null;
		if( $params[ 'week' ] == '*' ) $params[ 'week' ] = null;
		
		$Job = [
			'Minute' => $params[ 'minute' ],
			'Hour' => $params[ 'hour' ],
			'Day' => $params[ 'day' ],
			'Month' => $params[ 'month' ],
			'DOW' => $params[ 'week' ] ];
		
		$done = 0;
		while ($done < 100) {
			if (!is_null($Job['Minute']) && ($cron_date->minute != $Job['Minute'])) {
				if ($cron_date->minute > $Job['Minute']) {
					$cron_date->modify('+1 hour');
				}
				$cron_date->minute = $Job['Minute'];
			}
			if (!is_null($Job['Hour']) && ($cron_date->hour != $Job['Hour'])) {
				if ($cron_date->hour > $Job['Hour']) {
					$cron_date->modify('+1 day');
				}
				$cron_date->hour = $Job['Hour'];
				$cron_date->minute = 0;
			}
			if (!is_null($Job['DOW']) && ($cron_date->dow != $Job['DOW'])) {
				$cron_date->dow = $Job['DOW'];
				$cron_date->hour = 0;
				$cron_date->minute = 0;
			}
			if (!is_null($Job['Day']) && ($cron_date->day != $Job['Day'])) {
				if ($cron_date->day > $Job['Day']) {
					$cron_date->modify('+1 month');
				}
				$cron_date->day = $Job['Day'];
				$cron_date->hour = 0;
				$cron_date->minute = 0;
			}
			if (!is_null($Job['Month']) && ($cron_date->month != $Job['Month'])) {
				if ($cron_date->month > $Job['Month']) {
					$cron_date->modify('+1 year');
				}
				$cron_date->month = $Job['Month'];
				$cron_date->day = 1;
				$cron_date->hour = 0;
				$cron_date->minute = 0;
			}
	
			$done = (is_null($Job['Minute']) || $Job['Minute'] == $cron_date->minute) &&
					(is_null($Job['Hour']) || $Job['Hour'] == $cron_date->hour) &&
					(is_null($Job['Day']) || $Job['Day'] == $cron_date->day) &&
					(is_null($Job['Month']) || $Job['Month'] == $cron_date->month) &&
					(is_null($Job['DOW']) || $Job['DOW'] == $cron_date->dow)?100:($done+1);
		}
	
		return $cron_date->timestamp;
	}	
}