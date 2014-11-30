<?php

namespace app\test;

class TestController
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
