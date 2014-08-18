framework-cron
==============

[![Build Status](https://travis-ci.org/idealistsoft/framework-cron.png?branch=master)](https://travis-ci.org/idealistsoft/framework-cron) [![Coverage Status](https://coveralls.io/repos/idealistsoft/framework-cron/badge.png)](https://coveralls.io/r/idealistsoft/framework-cron)

[![Latest Stable Version](https://poser.pugx.org/idealistsoft/framework-cron/v/stable.png)](https://packagist.org/packages/idealistsoft/framework-cron)
[![Total Downloads](https://poser.pugx.org/idealistsoft/framework-cron/downloads.png)](https://packagist.org/packages/idealistsoft/framework-cron)

Schedule tasks module for Idealist Framework

## Installation

1. Add the composer package in the require section of your app's `composer.json` and run `composer update`

2. Setup a cron job for Idealist Framework in your crontab:

```bash
*	*	*	*	*	php /var/www/example.com/public/index.php /cron/scheduleCheck
```