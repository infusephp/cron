<?php

namespace Infuse\Cron\Libs;

function file_get_contents($cmd)
{
    return FileGetContentsMock::$functions->file_get_contents($cmd);
}

class FileGetContentsMock
{
    public static $functions;
}
