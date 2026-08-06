<?php

return [

    /*
    |--------------------------------------------------------------------------
    | NCLOUD SENS Service ID for SMS or LMS
    |--------------------------------------------------------------------------
    |
    | Service ID used to authenticate the SENS api request.
    |
    */
    'service_id' => env('SENS_SERVICE_ID', ''),

    /*
    |--------------------------------------------------------------------------
    | NCLOUD SENS Service ID for AlimTalk
    |--------------------------------------------------------------------------
    |
    | Service ID used to authenticate the SENS AlimTalk api request.
    | SMS service ID is not same with this AlimTalk service ID.
    */
    'alimtalk_service_id' => env('SENS_ALIMTALK_SERVICE_ID', ''),
    'plus_friend_id' => env('SENS_PlUS_FRIEND_ID', '@id'),

    /*
    |--------------------------------------------------------------------------
    | NCLOUD SENS Access Key
    |--------------------------------------------------------------------------
    |
    | Access key used to authenticate the SENS api request.
    |
    */
    'access_key' => env('SENS_ACCESS_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | NCLOUD SENS Secret Key
    |--------------------------------------------------------------------------
    |
    | Secret key used to authenticate the SENS api request.
    |
    */
    'secret_key' => env('SENS_SECRET_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | SMS "From" Number
    |--------------------------------------------------------------------------
    |
    | 발신 번호. 네이버 클라우드 콘솔에 등록된 번호만 사용할 수 있습니다.
    | 기존과 같이 config/services.php 의 sens.services.sms.sender 에 설정해도
    | 되며, 그 값이 우선합니다.
    |
    */
    'sms_from' => env('SENS_SMS_FROM'),

    /*
    |--------------------------------------------------------------------------
    | NCLOUD SENS API Base URL
    |--------------------------------------------------------------------------
    |
    | SENS API host. 공공기관용(gov) 또는 금융용(fin) 환경을 사용하는 경우에만
    | 변경하십시오.
    |
    */
    'base_url' => env('SENS_BASE_URL', 'https://sens.apigw.ntruss.com'),

    /*
    |--------------------------------------------------------------------------
    | 초당 발송 건수
    |--------------------------------------------------------------------------
    |
    | 한도를 넘긴 요청을 SENS 는 기다리게 하지 않고 그 자리에서 429 로 거절합니다.
    | 대량 발송에서 이 값을 넘겨 부르면 넘친 만큼이 그대로 실패로 남습니다.
    |
    | 이 패키지는 값을 두기만 하고 스스로 조이지는 않습니다 — 발송을 어떤 간격으로
    | 부를지는 큐·워커 구성에 따라 달라서 호출하는 쪽이 정해야 합니다. 값을 읽어
    | 실제로 제한하는 것은 쓰는 쪽의 몫입니다.
    |
    | 0 이나 음수는 '제한하지 않음' 으로 씁니다.
    |
    */
    'rate_limit' => (int) env('SENS_RATE_LIMIT', 30),

];
