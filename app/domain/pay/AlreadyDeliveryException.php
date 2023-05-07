<?php


namespace app\domain\pay;


use app\domain\exceptions\FQException;

class AlreadyDeliveryException extends FQException
{
    public function __construct($message = "", $code = 0, Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}