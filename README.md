# NCLOUD SENS notifications channel for Laravel

[![tests](https://github.com/Daworks/laravel-sens/actions/workflows/tests.yml/badge.svg?branch=master)](https://github.com/Daworks/laravel-sens/actions/workflows/tests.yml)
[![latest version](https://img.shields.io/packagist/v/daworks/laravel-sens.svg)](https://packagist.org/packages/daworks/laravel-sens)
[![license](https://img.shields.io/packagist/l/daworks/laravel-sens.svg)](LICENSE.md)

이 패키지는 https://github.com/seungmun/laravel-sens에서 fork하여 라라벨 9.x이상 버전에서 동작하도록 컨버전되었습니다.

This package makes it easy to send notification using [ncloud sens](//ncloud.com/product/applicationService/sens) with Laravel.

And We are working on an unofficial sdk development public project so that ncloud sens can be used in php more flexibly.

## Official Community

- [라라벨코리아](https://laravel.kr/)
- [라라벨코리아 오픈채팅](https://open.kakao.com/o/g3dWlf0)

## Prerequisites

Before you get started, you need the following:

- PHP >= 8.2 (라라벨 13.x 를 사용하는 경우 PHP >= 8.3)
- Laravel (9.x / 10.x / 11.x / 12.x / 13.x)

## Installation

You can install the package via composer:

``` bash
composer require daworks/laravel-sens
```

The package will automatically register itself.

> **라라벨 9.x ~ 11.x 를 사용하는 경우**
> 이 버전들은 지원이 종료되어 `CVE-2026-48019`(Laravel CRLF injection in default email rule)
> 패치가 제공되지 않습니다. 그래서 Composer가 기본 정책으로 `illuminate/mail` 설치를 차단하며,
> 이 패키지도 `illuminate/notifications`를 거쳐 같은 의존성에 걸립니다.
>
> 패키지 자체는 9.x부터 동작하며 CI에서도 검증하고 있습니다. 설치가 차단된다면
> 애플리케이션의 Composer 정책을 조정하거나, 가능하면 라라벨을 12.60 이상으로 올리십시오.
>
> ```bash
> # 취약점을 인지한 상태에서 설치를 진행하려면
> composer config policy.advisories.ignore-id '["PKSA-zwc5-qtrz-zm1n"]'
> ```

You can publish the config with:
```bash
php artisan vendor:publish --provider="Daworks\Sens\SensServiceProvider" --tag="config"
```

Also, you can use it without publish the config file can be used simply by adding environment variables with:

```bash
SENS_ACCESS_KEY=your-sens-access-key
SENS_SECRET_KEY=your-sens-secret-key
SENS_SERVICE_ID=your-sens-service-id
SENS_ALIMTALK_SERVICE_ID=your-alimtalk-service-id
SENS_PlUS_FRIEND_ID=your-plus-friend-id
```

If you want to put the `sms_from` value in your .env,

config/services.php

```php
/*
|--------------------------------------------------------------------------
| SMS "From" Number
|--------------------------------------------------------------------------
|
| This configuration option defines the phone number that will be used as
| the "from" number for all outgoing text messages. You should provide
| the number you have already reserved within your Naver Cloud Platform
| /sens/sms-calling-number of dashboard.
|
*/
'sens' => [
    'services' => [
        'sms' => [
            'sender' => env('SENS_SMS_FROM'),
        ],
    ],
],
```

.env:

```env
SENS_SMS_FROM=1234567890
```

## Usage

This package can be used using with the Laravel default notification feature.

##### 1) Request to send a SMS

```bash
php artisan make:notification SendPurchaseReceipt
```

```php
<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Daworks\Sens\Sms\SmsChannel;
use Daworks\Sens\Sms\SmsMessage;
use Illuminate\Notifications\Notification;

class SendPurchaseReceipt extends Notification
{
    use Queueable;

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return [SmsChannel::class];
    }

    /**
     * Get the sens sms representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return SmsMessage
     */
    public function toSms($notifiable)
    {
        return (new SmsMessage)
            ->to($notifiable->phone)
            ->from('055-000-0000')
            ->content('Welcome: https://open.kakao.com/o/g3dWlf0')
            ->contentType('AD')// You can ignore it (default: COMM)
            ->type('SMS');  // You can ignore it (default: SMS)
    }
}
```

```php
use App\User;
use App\Notifications\SendPurchaseReceipt;

User::find(1)->notify(new SendPurchaseReceipt);
```

##### 2) Request to send MMS

```bash
php artisan make:notification SendPurchaseInvoice
```

```php
<?php

namespace App\Notifications;
use Illuminate\Bus\Queueable;
use Daworks\Sens\Sms\SmsChannel;
use Daworks\Sens\Sms\SmsMessage;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

class SendPurchaseInvoice extends Notification
{
    use Queueable;
    
    /** @var UploadedFile */
    private $image;
    
    /**
     * Create a new notification instance.
     *
     * @param  UploadedFile  $image
     */
    public function __construct(UploadedFile $image)
    {
        $this->image = $image;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return [SmsChannel::class];
    }

    /**
     * Get the sens sms representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return SmsMessage
     * @throws FileNotFoundException
     */
    public function toSms($notifiable)
    {
        return (new SmsMessage)
            ->type('MMS')
            ->to($notifiable->phone)
            ->from('055-000-0000')
            ->content('This is your invoice.\nCheck out the attached image.')
            /* file's path string or UploadedFile object of Illuminate are allowed */
            ->file('filename.jpg', $this->image);
    }
}
```

```php
<?php

use App\User;
use App\Notifications\SendPurchaseReceipt;

// In this case, you should only pass UploadedFile object as a parameter.
// If when you need to pass a file path string as a parameter, change your notification class up.
User::find(1)->notify(new SendPurchaseReceipt(request()->file('image')));
```

> **첨부 파일에 대하여**
> SENS는 MMS 첨부 파일을 먼저 업로드해 파일 아이디를 받은 뒤 발송하는 방식을 사용합니다.
> 위처럼 `file()`로 파일을 넘기면 패키지가 발송 직전에 업로드를 대신 처리하므로
> 코드를 바꿀 필요는 없습니다. 다만 발송 한 번에 HTTP 요청이 두 번 나갑니다.
>
> 같은 이미지를 반복해서 보낸다면 미리 한 번만 업로드해 파일 아이디를 재사용하는 편이 낫습니다.
> 업로드된 파일은 6일간 보관됩니다.
>
> ```php
> use Daworks\Sens\Facades\Sens;
>
> $attachment = Sens::sms()->uploadAttachment('invoice.jpg', base64_encode($contents));
>
> $attachment->fileId;     // 'a136...74f7'
> $attachment->expireTime; // '2025-11-27T10:12:47.520'
>
> // 이후 발송에서는 파일 아이디만 넘깁니다.
> (new SmsMessage)->type('MMS')->to($phone)->content('...')->fileId($attachment->fileId);
> ```
>
> jpg, jpeg 이미지만 사용할 수 있으며 최대 300KB, 해상도 1500x1440까지 허용됩니다.


Now `User id: 1` which has own phone attribute would receive a sms or mms message soon.

##### 3) Request send AlimTalk

```php
<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Daworks\Sens\AlimTalk\AlimTalkChannel;
use Daworks\Sens\AlimTalk\AlimTalkMessage;

class SendPurchaseInvoice extends Notification
{
    use Queueable;

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return [AlimTalkChannel::class];
    }

    /**
     * Get the sens sms representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Daworks\Sens\AlimTalk\AlimTalkMessage
     */
    public function toAlimTalk($notifiable)
    {
        return (new AlimTalkMessage())
            ->templateCode('TEMPLATE001') // required
            ->to($notifiable->phone) // required
            ->content('Evans, Your order is shipped.') //required
            ->countryCode('82') // optional
            ->addButton(['type' => 'DS', 'name' => 'Tracking of Shipment']) // optional
            ->setReserved('2020-05-31 14:20', 'Asia/Seoul'); // optional
    }
}
```

## 발송 결과 추적 (Message tracking)

발송 API는 비동기입니다. `202` 응답은 **요청이 접수되었다**는 뜻이며, 수신자에게 전달되었다는 보장이 아닙니다.
실제 수신 여부는 발송 시 받은 `requestId`로 조회해야 합니다.

```
send() → requestId → findByRequestId() → messageId[] → findMessage() → 수신 상태
```

### 1) 발송 응답에서 requestId 받기

`Sens` 파사드로 직접 발송하면 응답 객체를 그대로 돌려받습니다.

```php
use Daworks\Sens\Facades\Sens;

$response = Sens::sms()->send($message->toArray());

$response->requestId;      // 'RSLA-...'  ← 이 값을 저장해 두면 추적할 수 있습니다.
$response->requestTime;    // '2025-11-25T09:39:40.535'
$response->statusCode;     // '202'
$response->isAccepted();   // true
$response->isSuccessful(); // true
```

알림톡은 즉시 발송인 경우 수신자별 접수 결과가 함께 옵니다. HTTP 응답이 `202`여도
개별 수신자의 접수가 실패할 수 있으므로 `isSuccessful()`로 함께 확인하십시오.

```php
$response = Sens::alimTalk()->send($message->toArray());

$response->isAccepted();   // true  (요청은 접수됨)
$response->isSuccessful(); // false (수신자 중 일부가 실패)

foreach ($response->failedMessages() as $message) {
    logger()->warning('알림톡 접수 실패', [
        'requestId' => $response->requestId,
        'to' => $message->getTo(),
        'reason' => $message->getStatusMessage(),
    ]);
}
```

### 2) Notification으로 발송한 경우

채널의 반환값은 라라벨이 `NotificationSent` 이벤트의 `$response`로 전달합니다.
큐로 발송하면 호출부에서는 반환값을 받을 수 없으므로, **이벤트 리스너로 기록하는 방식을 권장합니다.**

```php
use Illuminate\Notifications\Events\NotificationSent;
use Daworks\Sens\Responses\SendResponse;

class RecordSensRequestId
{
    public function handle(NotificationSent $event): void
    {
        if (! $event->response instanceof SendResponse) {
            return;
        }

        SentMessage::create([
            'notifiable_type' => $event->notifiable::class,
            'notifiable_id' => $event->notifiable->getKey(),
            'request_id' => $event->response->requestId,
            'status_code' => $event->response->statusCode,
        ]);
    }
}
```

큐를 쓰지 않는다면 `notifyNow()`의 반환값에서도 확인할 수 있습니다.

### 3) 수신 여부 조회

```php
use Daworks\Sens\Facades\Sens;

// 요청 아이디로 해당 발송 요청의 메시지 목록을 조회합니다.
$list = Sens::sms()->findByRequestId($requestId);

$list->messageIds();   // ['f574d3f0-...', ...]
$list->isSuccessful(); // 모든 메시지가 수신 성공했는지
$list->isPending();    // 아직 결과를 확정할 수 없는지
$list->isEmpty();      // 조회 결과가 없는지
$list->pending();      // 아직 처리 중인 메시지
$list->failed();       // 수신 실패한 메시지

foreach ($list->failed() as $message) {
    echo $message->getTo() . ': ' . $message->getStatusMessage();
}

// 개별 메시지의 상세 결과를 조회합니다.
$result = Sens::sms()->findMessage($list->messageIds()[0]);

$result->isSuccessful();    // 수신 성공 여부
$result->isPending();       // 아직 처리 중인지 (실패로 단정하면 안 됩니다)
$result->getStatusCode();   // '3018'
$result->getStatusMessage();// '발신번호 변작 방지 서비스에 가입된 번호'
$result->toArray();         // SENS 원본 응답
```

알림톡도 동일한 방법으로 조회합니다. 알림톡은 SMS 대체 발송(failover)이 있으므로
"알림톡 자체의 성공"과 "최종 전달 성공"을 구분할 수 있습니다.

```php
$result = Sens::alimTalk()->findMessage($messageId);

$result->isSuccessful();     // 알림톡으로 전달되었는지
$result->failoverSucceeded();// SMS 대체 발송이 성공했는지
$result->isDelivered();      // 둘 중 하나로 전달되었는지
```

> **폴링할 때 주의**
> 예약 발송이거나 발송 직후에는 SENS가 메시지 목록을 비워서 응답합니다.
> 이때 `isSuccessful()`은 `false`지만 실패한 것이 아니라 **아직 결과가 없는 상태**입니다.
> 실패로 처리하기 전에 `isPending()`을 먼저 확인하십시오.
>
> ```php
> $list = Sens::sms()->findByRequestId($requestId);
>
> if ($list->isPending()) {
>     return; // 아직 처리 중 — 잠시 후 다시 조회합니다.
> }
>
> if ($list->failed()) {
>     // 실패 처리
> }
> ```

기간으로 조회할 수도 있습니다. SMS는 최근 90일, 알림톡은 최근 30일까지 조회할 수 있습니다.

```php
$list = Sens::sms()->findMessages([
    'requestStartTime' => '2025-11-25 09:00:00',
    'requestEndTime' => '2025-11-25 18:00:00',
    'statusName' => 'fail',
    'pageSize' => 100,
]);

if ($list->hasMore) {
    $next = Sens::sms()->findMessages([/* ... */, 'nextToken' => $list->nextToken]);
}
```

### 4) 예약 발송 관리

예약 아이디는 발송 시 받은 `requestId`와 같습니다.

```php
$status = Sens::sms()->getReservationStatus($requestId);

$status->reserveStatus; // 'READY'
$status->isPending();   // 아직 발송되지 않음 (이 상태에서만 취소할 수 있습니다)

if ($status->isPending()) {
    Sens::sms()->cancelReservation($requestId);
}
```

### 5) 오류 처리

인증 실패나 잘못된 요청처럼 SENS가 오류를 응답하면 `SensException`이 발생하며,
예외 객체에서 상태 코드와 응답 본문을 확인할 수 있습니다.

```php
use Daworks\Sens\Exceptions\SensException;

try {
    $response = Sens::sms()->send($message->toArray());
} catch (SensException $e) {
    $e->getStatusCode();   // 401
    $e->getErrorCode();    // '210'
    $e->getMessage();      // '[210] Authentication Failed'
    $e->getResponseBody(); // SENS 원본 오류 응답
}
```

접수 자체는 성공했지만 일부 수신자만 실패한 경우에는 예외가 발생하지 않습니다.
이때는 응답 객체의 `isSuccessful()` / `failedMessages()`로 확인하십시오.

MMS를 `file()`로 발송할 때는 첨부 파일 업로드가 먼저 이루어지므로,
`send()`에서 발생한 예외가 **업로드 단계의 실패**일 수 있습니다.
이 경우 발송 요청이 나가지 않았으므로 `requestId`가 없습니다.
두 단계를 구분해서 처리하려면 `uploadAttachment()`를 직접 호출하고 `fileId()`로 넘기십시오.

### 변경 사항 안내

발송 결과 추적 기능이 추가되면서 다음이 바뀌었습니다. 기존 발송 코드는 그대로 동작합니다.

- `Sms::send()` / `AlimTalk::send()`가 `void` 대신 `SendResponse`를 반환합니다.
- 두 Notification 채널의 `send()`에서 `: void` 반환 타입 선언이 제거되었습니다.
  채널을 상속해 재정의한 경우 시그니처를 맞춰 주십시오.
- `Contracts\Sens` 인터페이스에 조회 메서드가 추가되었습니다.
  이 인터페이스를 직접 구현한 코드가 있다면 새 메서드를 구현해야 합니다.
- SMS/알림톡 클라이언트가 컨테이너에 싱글톤으로 등록되어,
  채널 밖에서도 `app(Sms::class)` 또는 `Sens` 파사드로 사용할 수 있습니다.
- MMS 첨부 파일이 현재 API 명세에 맞춰 **업로드 후 파일 아이디로 발송**하는 방식으로 바뀌었습니다.
  `SmsMessage::file()`을 쓰던 코드는 그대로 동작하며, 발송 시 업로드가 함께 이루어집니다.
- `AlimTalkMessage::setSchedule()`이 값을 설정하지 못하던 문제를 고쳤습니다.
  더불어 스케줄 코드를 지정하지 않은 경우 요청에 `scheduleCode: null`을 보내지 않습니다.
- 발신 번호를 `config/laravel-sens.php`의 `sms_from`으로도 설정할 수 있습니다.
  기존 `config/services.php` 설정이 있으면 그 값이 우선합니다.

## Features

- SMS(LMS) and MMS
- Kakao Alimtalk
- 발송 결과 추적 (requestId / messageId 기반 조회)
- 예약 발송 상태 조회 및 취소

## License

이 패키지는 [Apache License 2.0](LICENSE.md)으로 배포됩니다.

원본인 [seungmun/laravel-sens](https://github.com/seungmun/laravel-sens)는 MIT 라이선스로 공개되었으며,
MIT 라이선스가 요구하는 저작권 고지는 [NOTICE](NOTICE) 파일에 그대로 보존되어 있습니다.
원본에서 유래한 부분에는 해당 고지가 계속 적용됩니다.
