<?php

namespace common\traits;

use Yii;

trait LogPrint
{
    /**
     * @var float время начала выполнения скрипта
     */
    private static $time_start = .0;

    /**
     * Начало выполнения
     */
    static function start()
    {
        self::$time_start = microtime(true);
    }

    /**
     * Разница между текущей меткой времени и меткой self::$time_start
     * @return float
     */
    static function finish()
    {
//        $tm = microtime(true) - self::$time_start;
        $tm = microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"];
//        $tm = 13560;
        $hour = floor($tm/3600);
        $sec = $tm - ($hour*3600);
        $min = floor($sec/60);
        $sec = $sec - ($min*60);

        return sprintf("%02d:%02d:%02d", $hour, $min, $sec);

    }

    public function getLogName()
    {
        $a = pathinfo(self::class);
        return Yii::getAlias('@runtime') . '/logs/' . $a['basename'] . '.log';
    }

    public function print(string $message)
    {
        $dt = date("d.m.Y H:i:s");
        $fd = fopen($this->getLogName(), 'at');
        fprintf($fd, "%s %s\r\n", $dt, $message);
        fclose($fd);
    }

    public function endprint()
    {
        $dt = date("d.m.Y H:i:s");
        $fd = fopen($this->getLogName(), 'at');
        fprintf($fd, "%s Время работы скрипта: %s\r\n", $dt, $this->finish());
        fclose($fd);
    }

    public function reprint()
    {
        $dt = date("d.m.Y H:i:s");
        $fd = fopen($this->getLogName(), 'wb');
        fprintf($fd, "%s START %s\r\n", $dt, self::class);
        fclose($fd);
    }
}