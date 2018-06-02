<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @see http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */

namespace Infuse\Cron\Models;

use Infuse\HasApp;
use Pulsar\Model;

/**
 * @property string      $id
 * @property int|null    $last_ran
 * @property bool        $last_run_succeeded
 * @property string|null $last_run_output
 */
class CronJob extends Model
{
    use HasApp;

    protected static $properties = [
        'id' => [
            'required' => true,
        ],
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

    protected static $casts = [];
    protected static $validations = [];
    protected static $protected = [];
    protected static $defaults = [];
}
