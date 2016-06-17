<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @link http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
namespace App\Cron\Models;

use Pulsar\Model;

class CronJob extends Model
{
    protected static $ids = ['module', 'command'];

    protected static $properties = [
        'module' => [
            'required' => true,
        ],
        'command' => [
            'required' => true,
        ],
        'last_ran' => [
            'type' => Model::TYPE_DATE,
            'null' => true,
        ],
        'last_run_result' => [
            'type' => Model::TYPE_BOOLEAN,
        ],
        'last_run_output' => [
            'null' => true,
        ],
    ];
}
