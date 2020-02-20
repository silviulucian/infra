<?php
namespace silviulucian\infra;

class Terraform
{
    public static function __callStatic($name, $arguments)
    {
        self::terraform($name);
    }

    protected static function terraform($cmd)
    {
        $sanitized_cmd = preg_replace('/[^0-9A-Za-z\-]+/', '', $cmd);

        return system("set -x; cd terraform; terraform {$sanitized_cmd}");
    }
}
