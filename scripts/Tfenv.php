<?php
namespace silviulucian\infra;

class Tfenv
{
    public static function __callStatic($name, $arguments)
    {
        self::tfenv($name);
    }

    protected static function tfenv($cmd)
    {
        $sanitized_cmd = preg_replace('/[^0-9A-Za-z\-]+/', '', $cmd);

        return system("set -x; tfenv {$sanitized_cmd}");
    }
}
