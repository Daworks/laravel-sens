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
    | SENS 는 한도를 넘은 요청을 기다렸다가 처리해 주지 않고 곧바로 429 로 거절합니다.
    | 그래서 대량 발송에서 한도보다 빠르게 호출하면 넘친 요청은 그대로 실패로 남습니다.
    |
    | 이 설정은 값을 보관할 뿐, 패키지가 발송 속도를 대신 조절하지는 않습니다. 어느 정도
    | 간격으로 발송할지는 큐와 워커를 어떻게 구성했는지에 따라 달라지므로, 실제 속도
    | 제한은 이 값을 읽어 애플리케이션에서 직접 구현해야 합니다.
    |
    | 0 또는 음수를 넣으면 제한을 두지 않겠다는 뜻입니다.
    |
    */
    'rate_limit' => (int) env('SENS_RATE_LIMIT', 30),

];
