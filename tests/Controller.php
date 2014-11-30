<?php

namespace app\test;

class Controller
{
    public function exception()
    {
        throw new \Exception('test');
    }

    public function success()
    {
        echo 'test';

        return true;
    }
}
