<?php

namespace app\domain\exceptions;

class AssetNotEnoughException2 extends FQException
{
    public $assetId = null;
    public function __construct($assetId, $message = "", $code = 0, Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
        $this->assetId = $assetId;
    }
}