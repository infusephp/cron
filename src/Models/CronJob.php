<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @link http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */

namespace Infuse\Cron\Models;

use Pulsar\Model;

class CronJob extends Model
{
    protected static $properties = [
        'id' => [
            'required' => true,
        ],
        'module' => [],
        'command' => [],
        'last_ran' => [
            'type' => Model::TYPE_DATE,
            'null' => true,
        ],
        'last_run_succeeded' => [
            'type' => Model::TYPE_BOOLEAN,
        ],
        'last_run_output' => [
            'null' => true,
        ],
    ];
}
