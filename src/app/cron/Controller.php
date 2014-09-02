<?php

/**
 * @package infuse\framework
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.16
 * @copyright 2013 Jared King
 * @license MIT
 */

namespace app\cron;

use App;
use app\cron\libs\Cron;

class Controller
{
	public static $properties = [
		'models' => [ 'CronJob' ],
		'routes' => [
			'get /cron/scheduleCheck' => 'checkSchedule'
		]
	];

	public static $scaffoldAdmin;

	private $app;

	public function __construct(App $app)
	{
		$this->app = $app;
	}

	public function checkSchedule($req, $res)
	{
		if( !$req->isCli() )
			return $res->setCode( 404 );

		Cron::scheduleCheck( $this->app, true );
	}

	public function cron($command)
	{
		if ($command = 'test') {
			$t = date( 'h:i a' );
			$subject = $this->app[ 'config' ]->get( 'site.title' ) . ' Cron test: ' . $t;
			$body = "This is a cron job test\n$t";

			// Create the Transport
            $transport = \Swift_SmtpTransport::newInstance( $this->app[ 'config' ]->get( 'smtp.host' ), $this->app[ 'config' ]->get( 'smtp.port' ) )
			  ->setUsername( $this->app[ 'config' ]->get( 'smtp.username' ) )
			  ->setPassword( $this->app[ 'config' ]->get( 'smtp.password' ) );

			// Create the Mailer using your created Transport
            $mailer = \Swift_Mailer::newInstance( $transport );

			// Create a message
            $message = \Swift_Message::newInstance( $subject )
			  ->setFrom( [ $this->app[ 'config' ]->get( 'site.email' ) => $this->app[ 'config' ]->get( 'site.title' ) ] )
			  ->setTo( [ $this->app[ 'config' ]->get( 'site.email' ) => $this->app[ 'config' ]->get( 'site.title' ) ] )
			  ->setBody( nl2br( $body ), 'text/html' )
			  ->addPart( strip_tags( $body ), 'text/plain' );

			// sleep for 30 seconds to simulate a long-running cron job
            sleep( 30 );

			// send the e-mail

            return $mailer->send( $message );
		}
	}
}
