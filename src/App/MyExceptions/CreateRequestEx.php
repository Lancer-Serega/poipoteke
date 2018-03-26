<?php

namespace App\MyExceptions;


class CreateRequestEx
{
    public function __construct() {
        exit('reterg');
    }

    public static function f1() {
        echo 'Все плохо ';
    }
    public static function f2() {
        echo 'Все печально ';
    }
}