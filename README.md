cron
==============

[![Build Status](https://travis-ci.org/infusephp/cron.svg?branch=master&style=flat)](https://travis-ci.org/infusephp/cron)
[![Coverage Status](https://coveralls.io/repos/infusephp/cron/badge.svg?style=flat)](https://coveralls.io/r/infusephp/cron)
[![Latest Stable Version](https://poser.pugx.org/infuse/cron/v/stable.svg?style=flat)](https://packagist.org/packages/infuse/cron)
[![Total Downloads](https://poser.pugx.org/infuse/cron/downloads.svg?style=flat)](https://packagist.org/packages/infuse/cron)
[![HHVM Status](http://hhvm.h4cc.de/badge/infuse/cron.svg?style=flat)](http://hhvm.h4cc.de/package/infuse/cron)

Schedule tasks module for Infuse Framework

## Installation

1. Install the package with [composer](http://getcomposer.org):

```
composer require infuse/cron
```

2. Add cron jobs to the `cron` section of your app's configuration:
```php
'cron' => [
	[
		'module' => 'test',
		'command' => 'test',
		'expires' => 60,
		'successUrl' => 'http://webhook.example.com',
		'minute' => 0,
		'hour' => 0,
		'day' => '*',
		'month' => '*',
		'week' => '*'
	],
	[
		'module' => 'test',
		'command' => 'test2'
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

3. Create your jobs. Each job is a method on a module controller.

4. Add this to your crontab to begin running app cron jobs in the background:
```bash
*	*	*	*	*	php /var/www/example.com/infuse run-scheduled
```

### Webhooks

You can optionally specify a URL that will be called upon a successful run. The output from the run will be available using the `m` query parameter. This was designed to be compatible with [Dead Man's Snitch](https://deadmanssnitch.com/).