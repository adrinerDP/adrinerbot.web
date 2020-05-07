<?php

return array(
    'endpoints' => array(
        'account' => 'https://accounts.kakao.com/',
        'sharer' => 'https://sharer.kakao.com/',
        'tiara' => 'https://track.tiara.kakao.com/',
    ),

    'client' => array(
        'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0',
        'kakao-agent' => 'sdk/1.36.6 os/javascript lang/en-US device/Win64 origin/' . urlencode(config('app.url')),
    ),

    'auth' => array(
        'api_key' => env('KAKAO_API_KEY'),
        'email' => env('KAKAO_AUTH_EMAIL'),
        'password' => env('KAKAO_AUTH_PASSWORD'),
    ),
);
