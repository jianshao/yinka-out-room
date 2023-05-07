<?php


namespace app\api\shardingScript;

use app\core\mysql\Sharding;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;

ini_set('set_time_limit', 0);

class UserDatabaseCreateTableCommand extends Command
{
    protected function configure()
    {
        $this->setName('UserDatabaseCreateTableCommand')->setDescription('UserDatabaseCreateTable');
    }

    protected $serviceName = 'userMaster';

    /**
     *执行
     */
    protected function execute(Input $input, Output $output)
    {
        $dbMap = Sharding::getInstance()->getDbMap($this->serviceName);
        foreach ($dbMap as $dbName) {
            $sqlArray = $this->getCreateTableSql();
            foreach ($sqlArray as $sql) {
                if (!empty($sql)) {
                    Db::connect($dbName)->query(trim($sql, '/n'));
                }
            }
        }
    }

    public function getCreateTableSql() {
        return $this->createFromFile(app()->getBasePath() . 'core/database/user/createTable.sql', null);
    }

    public function createFromFile($sqlPath,$delimiter = '(;/n)|((;/r/n))|(;/r)',$prefix = '',$commenter = array('#','--'))
    {
        $sqlPath = '/' . trim($sqlPath, '/app');
        //判断文件是否存在
        if(!file_exists($sqlPath))
            return false;

        $handle = fopen($sqlPath,'rb');

        $sqlStr = fread($handle,filesize($sqlPath));

        //通过sql语法的语句分割符进行分割
        $segment = explode(";",trim($sqlStr));

        //var_dump($segment);

        //去掉注释和多余的空行
        foreach($segment as & $statement)
        {
            $sentence = explode("/n",$statement);

            $newStatement = array();

            foreach($sentence as $subSentence)
            {
                if('' != trim($subSentence))
                {
                    //判断是会否是注释
                    $isComment = false;
                    foreach($commenter as $comer)
                    {
                        if (preg_match("/^".$comer."/",trim($subSentence)))
                        {
                            $isComment = true;
                            break;
                        }
                    }
                    //如果不是注释，则认为是sql语句
                    if(!$isComment)
                        $newStatement[] = $subSentence;
                }
            }

            $statement = $newStatement;
        }
        //组合sql语句
        foreach($segment as & $statement)
        {
            $newStmt = '';
            foreach($statement as $sentence)
            {
                $newStmt = $newStmt.trim($sentence)."/n";
            }

            $statement = $newStmt;
        }

        //用于测试------------------------      
        //var_dump($segment);die;
        //writeArrayToFile('data.txt',$segment);
        //-------------------------------
        return $segment;
    }


}