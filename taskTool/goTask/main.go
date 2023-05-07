package main

import (
	"errors"
	"github.com/astaxie/beego/config"
	"github.com/astaxie/beego/logs"
	"github.com/gomodule/redigo/redis"
	"log"
	"os"
	"os/exec"
	"path/filepath"
	"strings"
	"time"
)

var (
	//起始进程数
	proc_num int = 1
	//脚本文件路径
	file_name string
	//当前任务数
	cur_task_num = 0
	//总任务数
	total_task_num = 0
	//最大任务数
	max_task_num = 30

	//redis连接池
	RedisClient *redis.Pool
	cmd         *exec.Cmd

	iniconf config.Configer

	//日志
	errorLog = logs.NewLogger(10)
	infoLog  = logs.NewLogger(10)

	//redis配置信息
	redis_prefix = ""
	redis_host   = ""
	redis_auth   = ""
)

func main() {
	initLogger()
	err := loadConfig()
	if err != nil {
		return
	}
	initRedisPool()
	total_task_num = proc_num
	go checkTaskNum()
	for {
		if cur_task_num < total_task_num {
			cur_task_num++
			go startTask()
			//infoLog.Info("start task,cur_task_num=%d,total_task_num=%d", cur_task_num, total_task_num)
			continue
		}
		time.Sleep(time.Second * 5)
	}
}

func GetCurrentDirectory() string {
	dir, err := filepath.Abs(filepath.Dir(os.Args[0])) //返回绝对路径  filepath.Dir(os.Args[0])去除最后一个元素的路径
	if err != nil {
		log.Fatal(err)
	}
	return strings.Replace(dir, "\\", "/", -1) //将\替换成/
}

//初始化日志
func initLogger() {
	errorLog.SetLogger(logs.AdapterFile, `{"filename":"`+GetCurrentDirectory()+`/Log/error.log","rotateperm":"777","perm":"777","maxdays":5}`)
	infoLog.SetLogger(logs.AdapterFile, `{"filename":"`+GetCurrentDirectory()+`/Log/info.log","rotateperm":"777","perm":"777","maxdays":5}`)
	errorLog.EnableFuncCallDepth(true)
	infoLog.EnableFuncCallDepth(true)
}

//初始化redis连接池
func initRedisPool() {
	RedisClient = &redis.Pool{
		MaxIdle:     1,
		MaxActive:   10,
		IdleTimeout: 180 * time.Second,
		Dial: func() (redis.Conn, error) {
			c, err := redis.Dial("tcp", redis_host)
			if err != nil {
				return nil, err
			}
			if redis_auth != "" {
				c.Do("AUTH", "tbkredis4006010136")
			}
			return c, nil
		},
	}
}

//读取配置文件设置的任务数
func loadConfig() error {
	var err error
	iniconf, err = config.NewConfig("ini", GetCurrentDirectory()+"/config.conf")
	if err != nil {
		errorLog.Error(err.Error())
		return err
	}
	proc_num = iniconf.DefaultInt("Task::proc_num", 1)
	max_task_num = iniconf.DefaultInt("Task::max_proc", 10)
	file_name = iniconf.DefaultString("Task::file_name", "")
	if file_name == "" {
		errorLog.Error("file_name is empty")
		return errors.New("file_name is empty")
	}
	redis_prefix = iniconf.DefaultString("Redis::redis_prefix", "")
	redis_host = iniconf.DefaultString("Redis::redis_host", "127.0.0.1:6379")
	redis_auth = iniconf.DefaultString("Redis::redis_auth", "")
	return nil
}

//执行程序
func startTask() {
	cmd = exec.Command("/bin/bash", "-c", file_name)
	ret, err := cmd.Output()
	if err != nil {
		errorLog.Error(err.Error())
	}
	infoLog.Info(string(ret))
	cur_task_num--
}

//定期检查总任务数1
func checkTaskNum() {
	for {
		c := RedisClient.Get()
		num, err := redis.Int(c.Do("LLEN", redis_prefix+"{queues:default"))
		if err != nil {
			c.Close()
			errorLog.Error(err.Error())
			continue
		}

		count_num := "0"
		if num > 0 {
			total_task_num = num/50 + proc_num
			if total_task_num > cur_task_num {
				max_task_num = iniconf.DefaultInt("Task::max_proc", 10)
				if total_task_num > max_task_num {
					total_task_num = max_task_num
				}
				infoLog.Info("checkTaskNum,num=%d,total_task_num=%d,all_task_num=%s", num, total_task_num, count_num)
			}
		}
		c.Close()
		time.Sleep(time.Second * 10)
	}
}

