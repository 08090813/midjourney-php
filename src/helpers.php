<?php
# 打印函数
function _print($data)
{
    echo "<pre>";
    print_r($data);
    echo "</pre>";
}

# 生成6位字符串随机数
function createInvitecode(int $length = 6) {
    // 生成字母和数字组成的6位字符串
    $str = range('A', 'Z');
    // 去除大写的O，以防止与0混淆 
    unset($str[array_search('O', $str)]);
    $arr = array_merge(range(0, 9), $str);
    shuffle($arr);
    $invitecode = '';
    $arr_len = count($arr);
    for ($i = 0; $i < $length; $i++) {
        $rand = mt_rand(0, $arr_len - 1);
        $invitecode .= $arr[$rand];
    }
    return $invitecode;
}

# 控制台输出
function console(string $message)
{
    $msg = $message.PHP_EOL;
    print_r($msg);
    return $msg;
}