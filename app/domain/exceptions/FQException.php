<?php

namespace app\domain\exceptions;
use Exception;

class FQException extends Exception
{
    public function __construct($message = "", $code = 0, Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}


