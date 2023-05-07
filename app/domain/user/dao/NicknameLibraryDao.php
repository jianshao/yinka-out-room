<?php


namespace app\domain\user\dao;


use app\core\mysql\ModelDao;
use app\domain\user\model\NicknameLibraryModel;


class NicknameLibraryDao extends ModelDao
{
    protected $serviceName = 'commonMaster';
    protected $table = 'zb_nickname_library';
    protected $shardingColumn = 0;

    protected static $instance;

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new NicknameLibraryDao();
        }
        return self::$instance;
    }

    public function store(NicknameLibraryModel $model)
    {
        $data = [
            "nickname" => $model->nickname,
            "hashkey" => $model->hashkey,
            "create_time" => $model->createTime,
        ];
        return $this->getModel($this->shardingColumn)->insertGetId($data);
    }

    public function getNumRows(){
        return $this->getModel($this->shardingColumn)->getNumRows();
    }

    /**
     * @info 获取没有使用的昵称的数量
     * @return int
     */
    public function countNotUsedNumber()
    {
        return $this->getModel($this->shardingColumn)->where('use', '=', 0)->count('id');
    }


    /**
     * @info 获取没有使用的昵称list
     * @return array
     */
    public function getNotUsedNicknameList()
    {
        $idsList = $this->getModel($this->shardingColumn)->where('use', "=", 0)->limit(7000)->column('id');
        $idsList=array_values($idsList);
        shuffle($idsList);
        $idsListResult=array_slice($idsList,0,500);
        return $this->loadNacknameForIds($idsListResult);
    }

    /**
     * @param $idsListResult
     * @return array
     * @throws \app\domain\exceptions\FQException
     */
    private function loadNacknameForIds($idsListResult){
        $where[]=['id','in',$idsListResult];
        $temp=$this->getModel($this->shardingColumn)->where($where)->column('nickname');
        if (empty($temp)){
            return [];
        }
        return array_values($temp);
    }

    /**
     * @param $nickname
     * @return bool
     */
    public function updateUseNickName($nickname)
    {
        if (empty($nickname)) {
            return false;
        }
        $hash = md5($nickname);
        $id = $this->getModel($this->shardingColumn)->where('hashkey', '=', $hash)->where('use',0)->value('id');
        if ($id === null) {
            return false;
        }
        return $this->getModel($this->shardingColumn)->where('id', $id)->save(['use' => 1, 'update_time' => time()]);
    }


    /**
     * @param $nickname
     * @return bool  true是默认昵称，false不是
     */
    public function issetNickName($nickname)
    {
        if (empty($nickname)) {
            return false;
        }
        $id = $this->getModel($this->shardingColumn)->where('hashkey', '=', md5($nickname))->where('use',0)->value('id');
        if ($id === null) return false;
        return true;
    }
}