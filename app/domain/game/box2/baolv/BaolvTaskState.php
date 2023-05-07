<?php


namespace app\domain\game\box2\baolv;


class BaolvTaskState
{
    public static $INIT = 0;
    public static $RUNNING = 1;
    public static $FINISH = 2;
    public static $FAILED = 3;
}