<?php

namespace app\test;

class Controller
{
	function exception()
	{
		throw new \Exception('test');
	}

	function success()
	{
		echo 'test';
		return true;
	}
}