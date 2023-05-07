<?php

namespace app\web\model;


//{"bindType":"promote","promoteCode":"800001","qrCode":10001}
class RefereeInfoModel
{
    public $bindType = "";

    public $promoteCode = "";

    public $qrCode = 0;


    /**
     * @return array
     */
    public function modelToData()
    {
        return [
            'bindType' => $this->bindType,
            'promoteCode' => $this->promoteCode,
            'qrCode' => $this->qrCode,
        ];
    }

    /**
     * @param $data
     * @return $this
     */
    public function dateToModel($data)
    {
        $this->bindType = $data['bindType'] ?? "";
        $this->promoteCode = $data['promoteCode'] ?? "";
        $this->qrCode = $data['qrCode'] ?? 0;
        return $this;
    }
}