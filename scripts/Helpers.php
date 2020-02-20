<?php
namespace silviulucian\infra;


class Helpers
{
    public static function tfmspecialchars($str) {
        return preg_replace('/[^A-Za-z0-9\-_]+/', '', $str);
    }
}
