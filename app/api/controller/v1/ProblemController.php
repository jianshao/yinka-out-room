<?php


namespace app\api\controller\v1;


use app\BaseController;
use app\domain\problem\ProblemService;
use \app\facade\RequestAes as Request;


class ProblemController extends BaseController
{
    public function getList() {
        $type = intval(Request::param('type'));

        if ($type == 1) {
            $getType = 1;
        } else {
            $getType = null;
        }
        $problems = ProblemService::getInstance()->listProblem($getType);

        $problemList = [];
        foreach ($problems as $problem) {
            $problemList[] = [
                'problem_id' => $problem->id,
                'btypeid' => $problem->typeId,
                'title' => $problem->title,
                'createtime' => strval($problem->createTime)
            ];
        }

        return rjson([
            'problem_list' => $problemList
        ]);
    }
}