<?php

include 'vendor/autoload.php';

use YcOpen\Midjourney\Service;

$discord_channel_id = '1108410963478196297';
$discord_user_token = 'ODQ2NDU5NjU4Mjg5NTQ1MjE3.GSFlS3.FTUdDnFJ1Jh2roLIcnnFoVb3qo9PqGs53UH8us';
$config = [
    'channel_id' => $discord_channel_id,
    'oauth_token' => $discord_user_token,
    'timeout'=>30,
];

$midjourney = new Service($config);
$response = $midjourney->imagine('Pink Panda');
print_r($response);