<?php
namespace YcOpen\Midjourney\log;

class Log
{
    # 写入追加日志
    public static function add(string $message,string $file = '')
    {
        if (!$file) {
            $date = date('Ymd');
            $file = __DIR__."/../../logs/{$date}.log";
        }
        $time = date('Y-m-d H:i:s');
        $content = "-----------{$time}-----------".PHP_EOL."{$message}".PHP_EOL;
        file_put_contents($file,$content,FILE_APPEND);
    }
}