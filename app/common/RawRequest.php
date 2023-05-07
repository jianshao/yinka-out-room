<?php

namespace app\common;

use \think\Request;


class RawRequest extends Request{


    /**
     * @return array
     */
    public function getMiddleware(){
        return $this->middleware;
    }
}