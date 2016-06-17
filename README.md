cron
====

[![Build Status](https://travis-ci.org/infusephp/cron.svg?branch=master&style=flat)](https://travis-ci.org/infusephp/cron)
[![Coverage Status](https://coveralls.io/repos/infusephp/cron/badge.svg?style=flat)](https://coveralls.io/r/infusephp/cron)
[![Latest Stable Version](https://poser.pugx.org/infuse/cron/v/stable.svg?style=flat)](https://packagist.org/packages/infuse/cron)
[![Total Downloads](https://poser.pugx.org/infuse/cron/downloads.svg?style=flat)](https://packagist.org/packages/infuse/cron)
[![HHVM Status](http://hhvm.h4cc.de/badge/infuse/cron.svg?style=flat)](http://hhvm.h4cc.de/package/infuse/cron)

Scheduled jobs module for Infuse Framework

## Installation

1. Install the package with [composer](http://getcomposer.org):

   ```
   composer require infuse/cron
   ```

2. Add scheduled jobs to the `cron` section of your app's configuration:

   ```php
   'cron' => [
      	[
	       'id' => 'users:cleanup',
		   'class' => 'App\Users\ScheduledJobs\Cleanup',
		   'minute' => 0,
		   'hour' => 0,
		   'expires' => 60,
		   'successUrl' => 'https://webhook.example.com'
	   ],
	   [
   		   'id' => 'orgs:bill',
		   'class' => 'App\Billing\ScheduledJobs\Bill'
	   ]
   ]
   ```

   And add the console command to run jobs to `console.commands` in your app's configuration:
   
   ```php
   'console' => [
	   // ...
	   'commands' => [
		   // ...
		   'App\Cron\Console\RunScheduledCommand'
	   ]
   ]
   ```

3. Code up your jobs. Each job class must be [invokeable](http://php.net/manual/en/language.oop5.magic.php#object.invoke).

4. Add this to your crontab to begin running app cron jobs in the background:

   ```bash
   *	*	*	*	*	php /var/www/example.com/infuse run-scheduled
   ```

### Webhooks

You can optionally specify a URL that will be called upon a successful run. The output from the run will be available using the `m` query parameter. This was designed to be compatible with [Dead Man's Snitch](https://deadmanssnitch.com/).