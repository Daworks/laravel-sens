# laravel-sens

네이버 클라우드 플랫폼 [SENS](https://www.ncloud.com/product/applicationService/sens)로
SMS · LMS · MMS와 카카오 알림톡을 보내는 라라벨 Notification 채널입니다.

[![tests](https://github.com/Daworks/laravel-sens/actions/workflows/tests.yml/badge.svg?branch=master)](https://github.com/Daworks/laravel-sens/actions/workflows/tests.yml)
[![latest version](https://img.shields.io/packagist/v/daworks/laravel-sens.svg)](https://packagist.org/packages/daworks/laravel-sens)
[![license](https://img.shields.io/packagist/l/daworks/laravel-sens.svg)](LICENSE.md)

이 패키지는 [seungmun/laravel-sens](https://github.com/seungmun/laravel-sens)를 포크해
라라벨 9.x 이상에서 동작하도록 고친 것입니다.

## 목차

- [요구 사항](#요구-사항)
- [설치](#설치)
- [사전 준비](#사전-준비)
- [설정](#설정)
- [발송](#발송)
- [메시지 빌더 레퍼런스](#메시지-빌더-레퍼런스)
- [발송 결과 추적](#발송-결과-추적)
- [예약 발송 관리](#예약-발송-관리)
- [오류 처리](#오류-처리)
- [테스트](#테스트)
- [라이선스](#라이선스)

## 요구 사항

CI에서 실제로 검증하는 조합은 다음과 같습니다.

| 라라벨 | PHP |
| --- | --- |
| 9.x | 8.2 |
| 10.x | 8.2, 8.3 |
| 11.x | 8.2, 8.3, 8.4 |
| 12.x | 8.2, 8.3, 8.4 |
| 13.x | 8.3, 8.4 |

`ext-json`과 `guzzlehttp/guzzle` ^7.2가 필요하며, 둘 다 Composer가 함께 설치합니다.

## 설치

```bash
composer require daworks/laravel-sens
```

서비스 프로바이더는 패키지 자동 등록(package discovery)이 알아서 잡아 주므로 `config/app.php`에
직접 추가할 필요가 없습니다.

> **파사드는 별칭 없이 씁니다.**
> 이 패키지는 서비스 프로바이더만 등록하고 클래스 별칭(alias)은 제공하지 않습니다.
> 파사드를 쓸 때는 반드시 네임스페이스를 임포트하십시오.
>
> ```php
> use Daworks\Sens\Facades\Sens;   // 필요
> // \Sens::sms() 처럼 전역 별칭으로는 동작하지 않습니다.
> ```

### 라라벨 9.x ~ 11.x 를 사용하는 경우

이 버전들은 지원이 종료되어 `CVE-2026-48019`(Laravel CRLF injection in default email rule)
패치가 제공되지 않습니다. 그래서 Composer가 기본 정책으로 `illuminate/mail` 설치를 차단하는데,
이 패키지도 `illuminate/notifications`를 통해 같은 패키지를 의존하므로 함께 막힙니다.

패키지 자체는 9.x부터 동작하며 CI에서도 검증하고 있습니다. 설치가 차단된다면
애플리케이션의 Composer 정책을 조정하거나, 가능하면 라라벨을 12.60 이상으로 올리십시오.

```bash
# 취약점을 인지한 상태에서 설치를 진행하려면
composer config policy.advisories.ignore-id '["PKSA-zwc5-qtrz-zm1n"]'
```

### 설정 파일 퍼블리시

```bash
php artisan vendor:publish --provider="Daworks\Sens\SensServiceProvider" --tag="config"
```

퍼블리시하면 `config/laravel-sens.php`가 생성됩니다. 퍼블리시하지 않아도 패키지 기본 설정이
병합되므로, `.env`에 환경 변수만 넣어도 그대로 동작합니다.

## 사전 준비

발송을 시작하려면 네이버 클라우드 플랫폼 콘솔에서 먼저 준비해야 할 값이 있습니다.
콘솔 메뉴는 수시로 바뀌므로 여기서는 **무엇이 필요한지**만 정리했습니다. 실제 발급 절차는
공식 문서를 확인하십시오.

| 준비할 것 | 설명 |
| --- | --- |
| 인증키 (Access Key / Secret Key) | 네이버 클라우드 플랫폼 계정 단위로 발급하는 API 인증키입니다. SENS 전용 값이 아니라 계정 공통 값입니다. |
| SMS 서비스 ID | SENS에서 SMS 프로젝트를 만들면 발급되는 아이디입니다. 콘솔에 표시된 값을 그대로 복사하십시오. |
| 발신 번호 | SENS에 **사전 등록·승인된 번호**만 발신 번호로 쓸 수 있습니다. 등록되지 않은 번호로 보내면 발송이 거절됩니다. |
| 알림톡 서비스 ID | 알림톡 프로젝트의 아이디로, **SMS 서비스 ID와 다른 값**입니다. |
| 카카오톡 채널 ID | `@`로 시작하는 채널 검색용 아이디입니다. SENS에 연동된 채널이어야 합니다. |
| 알림톡 템플릿 코드 | 카카오 심사를 통과해 등록된 템플릿의 코드입니다. 발송 본문은 승인된 템플릿과 일치해야 합니다. |

관련 공식 API 명세:

- [Project API](https://api.ncloud-docs.com/docs/ai-application-service-sens-projectv2)
- [SMS API](https://api.ncloud-docs.com/docs/ai-application-service-sens-smsv2)
- [알림톡 API](https://api.ncloud-docs.com/docs/ai-application-service-sens-alimtalkv2)

## 설정

### 한눈에 보기

| 설정 키 (`config/laravel-sens.php`) | 환경 변수 | 기본값 | 필요한 경우 |
| --- | --- | --- | --- |
| `service_id` | `SENS_SERVICE_ID` | `''` | SMS · LMS · MMS |
| `alimtalk_service_id` | `SENS_ALIMTALK_SERVICE_ID` | `''` | 알림톡 |
| `plus_friend_id` | `SENS_PlUS_FRIEND_ID` | `'@id'` | 알림톡 |
| `access_key` | `SENS_ACCESS_KEY` | `''` | 공통 (필수) |
| `secret_key` | `SENS_SECRET_KEY` | `''` | 공통 (필수) |
| `sms_from` | `SENS_SMS_FROM` | `null` | SMS · LMS · MMS |
| `base_url` | `SENS_BASE_URL` | `https://sens.apigw.ntruss.com` | 공공/금융 환경만 |
| `rate_limit` | `SENS_RATE_LIMIT` | `30` | 선택 |

`.env` 예시:

```dotenv
SENS_ACCESS_KEY=your-access-key
SENS_SECRET_KEY=your-secret-key
SENS_SERVICE_ID=ncp:sms:kr:000000000000:your-project
SENS_SMS_FROM=0550000000

# 알림톡을 사용할 때만
SENS_ALIMTALK_SERVICE_ID=ncp:kkobizmsg:kr:000000000000:your-project
SENS_PlUS_FRIEND_ID=@your-channel

# 필요할 때만
SENS_RATE_LIMIT=30
```

### 항목별 지침

#### `access_key` / `secret_key`

모든 요청의 서명(`x-ncp-apigw-signature-v2`)을 만드는 데 쓰입니다. SMS와 알림톡이 같은 값을
공유합니다.

#### `service_id`

SMS·LMS·MMS 요청 경로(`/sms/v2/services/{serviceId}`)에 들어갑니다. **알림톡에는 쓰이지
않습니다.**

#### `alimtalk_service_id`

알림톡 요청 경로(`/alimtalk/v2/services/{serviceId}`)에 들어갑니다. SMS 서비스 아이디와
혼동하면 인증은 통과하지만 서비스를 찾지 못해 실패합니다.

> **퍼블리시한 설정에서 기본값 `''`를 지우지 마십시오.**
> 다른 항목은 값이 `null`이어도 빈 문자열로 바꿔 처리하지만, 알림톡 클라이언트는 이 값을
> 그대로 읽습니다. 설정 파일을 퍼블리시한 뒤 다음처럼 기본값을 없애고 환경 변수도 설정하지
> 않으면, 컨테이너가 클라이언트를 만드는 시점에 `TypeError`가 발생합니다.
>
> ```php
> 'alimtalk_service_id' => env('SENS_ALIMTALK_SERVICE_ID'),        // 위험 — null 이 전달됩니다
> 'alimtalk_service_id' => env('SENS_ALIMTALK_SERVICE_ID', ''),    // 패키지 기본값
> ```
>
> 알림톡을 쓰지 않는다면 값을 비워 두어도 되지만, 기본값 `''`는 남겨 두십시오.
> (반대로 퍼블리시한 파일에서 이 **줄 전체를 삭제**하면 패키지 기본값이 병합되므로 문제가 되지 않습니다.)

#### `plus_friend_id`

카카오톡 채널 아이디(`@`로 시작)입니다. 두 곳에서 쓰입니다.

- `AlimTalkMessage`를 만들 때 채널 아이디를 지정하지 않으면 이 값이 기본값이 됩니다.
- `requestId` 없이 알림톡 발송 목록을 조회할 때 조회 대상 채널로 쓰입니다.

> **환경 변수 이름의 철자에 주의하십시오.**
> 이 패키지가 읽는 이름은 `SENS_PlUS_FRIEND_ID`로, `PLUS`가 아니라 **`PlUS`(세 번째 글자가
> 소문자 `l`)** 입니다. 환경 변수 이름은 대소문자를 구분하므로 `SENS_PLUS_FRIEND_ID`로 적으면
> 값이 반영되지 않고, 자리만 채워 둔 기본값 `'@id'`가 그대로 전송되어 알림톡 발송이 실패합니다.
>
> 이름을 바로잡으면 이미 쓰고 있는 설정이 모두 깨지기 때문에 지금 철자를 그대로 두었습니다.
> 철자를 신경 쓰고 싶지 않다면 설정 파일을 퍼블리시해 직접 값을 적어도 됩니다.
>
> ```php
> 'plus_friend_id' => env('SENS_PLUS_FRIEND_ID', '@id'),
> ```

#### `sms_from`

SMS·LMS·MMS의 발신 번호입니다. 다음 순서로 결정되며, 앞쪽 값이 우선합니다.

1. `SmsMessage::from()`으로 직접 지정한 값
2. `config('services.sens.services.sms.sender')` — 예전 버전과의 호환을 위해 남겨 둔 자리
3. `config('laravel-sens.sms_from')`

> **설정으로 넣는 번호는 하이픈 없이 숫자만 적으십시오.**
> `SmsMessage::from()`으로 넘긴 값은 패키지가 하이픈을 지워 주지만, 설정에서 읽은 값은 손대지 않고
> 그대로 보냅니다. `.env`에 `055-000-0000`을 넣으면 하이픈이 붙은 채로 API에 전달됩니다.

`config/services.php`에 두고 싶다면:

```php
'sens' => [
    'services' => [
        'sms' => [
            'sender' => env('SENS_SMS_FROM'),
        ],
    ],
],
```

#### `base_url`

SENS API 호스트입니다. 기본값은 공용 환경인 `https://sens.apigw.ntruss.com`이며,
**공공기관용(gov)이나 금융용(fin) 환경을 쓰는 경우에만** 변경하십시오. 끝에 슬래시를 붙여도
알아서 떼어 내며, 값이 비어 있으면 기본값을 씁니다.

#### `rate_limit`

초당 발송 건수입니다. SENS는 한도를 넘은 요청을 기다렸다가 처리해 주지 않고 곧바로 `429`로
거절합니다. 그래서 대량 발송에서 한도보다 빠르게 호출하면 넘친 요청은 그대로 실패로 남습니다.

**이 설정은 값을 보관할 뿐, 패키지가 발송 속도를 대신 조절하지는 않습니다.** 어느 정도
간격으로 발송할지는 큐와 워커를 어떻게 구성했는지에 따라 달라지므로, 실제 속도 제한은
`config('laravel-sens.rate_limit')` 값을 읽어 애플리케이션에서 직접 구현해야 합니다.
`0` 또는 음수를 넣으면 제한을 두지 않겠다는 뜻입니다.

허용되는 실제 한도는 상품과 계약에 따라 다르므로, 콘솔에서 확인한 값을 넣으십시오.

### 자격 증명이 비어 있을 때

값이 비어 있어도 클라이언트는 정상적으로 만들어지고, **발송하거나 조회할 때** `SensException`이
발생합니다. 설정이 비었다는 이유만으로 애플리케이션 전체가 뜨지 못하는 일을 막기 위한 것입니다.

검사는 서비스마다 따로 합니다. SMS는 `service_id`를, 알림톡은
`alimtalk_service_id`를 각각 확인하므로, **SMS만 사용한다면 알림톡 관련 값은 비워 두어도**
SMS 발송에는 영향이 없습니다.

### 설정 캐시

`php artisan config:cache`를 사용하는 환경이라면, `.env`를 고친 뒤 반드시 캐시를 다시
만드십시오. 캐시된 설정에서는 `env()` 호출이 `null`을 돌려줍니다.

```bash
php artisan config:clear && php artisan config:cache
```

## 발송

라라벨 기본 Notification 기능을 그대로 사용합니다.

### 1) SMS · LMS

```bash
php artisan make:notification SendPurchaseReceipt
```

```php
<?php

namespace App\Notifications;

use Daworks\Sens\Sms\SmsChannel;
use Daworks\Sens\Sms\SmsMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SendPurchaseReceipt extends Notification
{
    use Queueable;

    public function via($notifiable)
    {
        return [SmsChannel::class];
    }

    public function toSms($notifiable)
    {
        return (new SmsMessage)
            ->to($notifiable->phone)
            ->from('055-000-0000')   // 생략하면 설정값을 사용합니다.
            ->content('주문이 접수되었습니다.')
            ->contentType('AD')      // 생략 가능 (기본: COMM)
            ->type('SMS');           // 생략 가능 (기본: SMS)
    }
}
```

```php
use App\Models\User;
use App\Notifications\SendPurchaseReceipt;

User::find(1)->notify(new SendPurchaseReceipt);
```

장문을 보낼 때는 `type('LMS')`로 바꾸고 `subject()`로 제목을 지정합니다. 본문 길이 제한을
넘으면 SENS가 요청을 거절하므로, 정확한 한도는 [SMS API 명세](https://api.ncloud-docs.com/docs/ai-application-service-sens-smsv2)를
확인하십시오.

한 번의 요청으로 여러 명에게 보내려면 `to()`를 여러 번 호출합니다.

```php
(new SmsMessage)
    ->to('01000000001')
    ->to('01000000002')
    ->content('공지 사항입니다.');
```

### 2) MMS

```php
public function toSms($notifiable)
{
    return (new SmsMessage)
        ->type('MMS')
        ->to($notifiable->phone)
        ->subject('청구서')
        ->content("청구서를 첨부합니다.")
        /* 파일 경로 문자열 또는 Illuminate\Http\UploadedFile 객체 */
        ->file('invoice.jpg', $this->image);
}
```

> **첨부 파일에 대하여**
> SENS는 MMS 첨부 파일을 먼저 업로드해 파일 아이디를 받은 뒤 발송하는 방식입니다.
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

### 3) 알림톡

```php
<?php

namespace App\Notifications;

use Daworks\Sens\AlimTalk\AlimTalkChannel;
use Daworks\Sens\AlimTalk\AlimTalkMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SendShippingNotice extends Notification
{
    use Queueable;

    public function via($notifiable)
    {
        return [AlimTalkChannel::class];
    }

    public function toAlimTalk($notifiable)
    {
        return (new AlimTalkMessage())
            ->templateCode('TEMPLATE001')                            // 필수
            ->to($notifiable->phone)                                 // 필수
            ->content('주문하신 상품이 발송되었습니다.')                  // 필수
            ->countryCode('82')                                      // 선택
            ->addButton(['type' => 'DS', 'name' => '배송 조회'])        // 선택
            ->setReserved('2026-08-31 14:20', 'Asia/Seoul');         // 선택
    }
}
```

`content`는 승인된 템플릿 본문과 일치해야 합니다. 일치하지 않으면 발송이 거절됩니다.
채널 아이디를 메시지마다 다르게 쓰려면 생성자나 `plusFriendId()`로 지정합니다.

```php
new AlimTalkMessage('@another-channel');
// 또는
(new AlimTalkMessage)->plusFriendId('@another-channel');
```

### 4) Notification 없이 직접 발송

파사드로 클라이언트를 직접 사용할 수 있습니다. 이 경우 응답 객체를 그대로 돌려받습니다.

```php
use Daworks\Sens\Facades\Sens;
use Daworks\Sens\Sms\SmsMessage;

$message = (new SmsMessage)->to('01000000000')->content('안녕하세요.');

$response = Sens::sms()->send($message->toArray());
```

클라이언트는 컨테이너에 싱글톤으로 등록되어 있으므로 주입해서 써도 됩니다.

```php
public function __construct(private \Daworks\Sens\Sms\Sms $sms) {}
```

## 메시지 빌더 레퍼런스

### `SmsMessage`

| 메서드 | 설명 |
| --- | --- |
| `type(string)` | `SMS` / `LMS` / `MMS`. 기본 `SMS`. 대문자로 변환됩니다. |
| `contentType(string)` | `COMM`(일반) / `AD`(광고). 기본 `COMM`. |
| `countryCode(int)` | 국가 번호. 기본 `82`. |
| `from(string)` | 발신 번호. 하이픈은 자동으로 제거됩니다. 생략하면 설정값을 사용합니다. |
| `subject(string)` | 제목. LMS·MMS에서만 사용됩니다. |
| `content(string)` | 본문. |
| `to(string)` | 수신 번호. 하이픈은 자동으로 제거됩니다. **호출할 때마다 수신자가 추가됩니다.** |
| `setReserved(string, string)` | 예약 발송 일시(`YYYY-MM-DD HH:mm`)와 타임존(기본 `Asia/Seoul`). |
| `file(string, string\|UploadedFile)` | MMS 첨부. 발송 직전에 업로드됩니다. |
| `fileId(string)` | 이미 업로드한 첨부 파일의 아이디. |
| `toArray()` | 요청 배열로 변환합니다. |

### `AlimTalkMessage`

| 메서드 | 설명 |
| --- | --- |
| `__construct(?string $friendId)` | 채널 아이디. 생략하면 `plus_friend_id` 설정값을 사용합니다. |
| `plusFriendId(string)` | 채널 아이디를 지정합니다. |
| `templateCode(string)` | 템플릿 코드 (필수). |
| `to(string)` | 수신 번호 (필수). **`SmsMessage`와 달리 한 번에 한 명이며, 다시 호출하면 덮어씁니다. 하이픈도 제거되지 않으니 숫자만 넣으십시오.** |
| `content(string)` | 본문 (필수). 승인된 템플릿과 일치해야 합니다. |
| `countryCode(string)` | 국가 번호. 기본 `'82'`. |
| `addButton(array)` | 버튼을 추가합니다. 버튼 형식은 알림톡 API 명세를 따릅니다. |
| `setReserved(string, string)` | 예약 발송 일시와 타임존(기본 `Asia/Seoul`). |
| `setSchedule(string)` | 스케줄 코드. 지정하지 않으면 요청에 포함되지 않습니다. |
| `toArray()` | 요청 배열로 변환합니다. |

## 발송 결과 추적

발송 API는 비동기입니다. `202` 응답은 **요청이 접수되었다**는 뜻이며, 수신자에게 전달되었다는
보장이 아닙니다. 실제 수신 여부는 발송 시 받은 `requestId`로 조회해야 합니다.

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

예약이 아닌 즉시 발송이라면 알림톡은 수신자별 접수 결과를 응답에 함께 담아 줍니다. HTTP 응답이 `202`여도
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

채널이 돌려준 값을 라라벨이 `NotificationSent` 이벤트의 `$response`에 담아 전달합니다.
큐로 발송하면 호출부에서는 반환값을 받을 수 없으므로, **이벤트 리스너로 기록하는 방식을 권장합니다.**

```php
use Daworks\Sens\Responses\SendResponse;
use Illuminate\Notifications\Events\NotificationSent;

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

$result->isSuccessful();     // 수신 성공 여부
$result->isPending();        // 아직 처리 중인지 (실패로 단정하면 안 됩니다)
$result->getStatusCode();    // '3018'
$result->getStatusMessage(); // '발신번호 변작 방지 서비스에 가입된 번호'
$result->toArray();          // SENS 원본 응답
```

알림톡도 동일한 방법으로 조회합니다. 알림톡은 SMS 대체 발송(failover)이 있으므로
"알림톡 자체의 성공"과 "최종 전달 성공"을 구분할 수 있습니다.

```php
$result = Sens::alimTalk()->findMessage($messageId);

$result->isSuccessful();      // 알림톡으로 전달되었는지
$result->failoverSucceeded(); // SMS 대체 발송이 성공했는지
$result->isDelivered();       // 둘 중 하나로 전달되었는지
```

> **폴링할 때 주의**
> 예약 발송이거나 발송 직후에는 SENS가 빈 메시지 목록을 돌려줍니다.
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
    $next = Sens::sms()->findMessages([/* ... */ 'nextToken' => $list->nextToken]);
}
```

SMS 목록 조회는 `requestId`, `requestStartTime` + `requestEndTime`,
`completeStartTime` + `completeEndTime` 중 하나를 반드시 지정해야 합니다.
알림톡을 `requestId` 없이 조회하면 설정된 채널 아이디(`plus_friend_id`)가 자동으로 쓰입니다.

## 예약 발송 관리

예약 아이디는 발송 시 받은 `requestId`와 같습니다.

```php
$status = Sens::sms()->getReservationStatus($requestId);

$status->reserveStatus; // 'READY'
$status->isPending();   // 아직 발송되지 않음 (이 상태에서만 취소할 수 있습니다)

if ($status->isPending()) {
    Sens::sms()->cancelReservation($requestId);
}
```

`isCanceled()`, `isCompleted()`, `isFailed()`로도 상태를 확인할 수 있습니다.

## 오류 처리

인증 실패나 잘못된 요청처럼 SENS가 오류를 돌려주면 `SensException`이 발생하며,
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

MMS를 `file()`로 발송할 때는 첨부 파일을 먼저 업로드하므로,
`send()`에서 발생한 예외가 **업로드 단계의 실패**일 수 있습니다.
이 경우 발송 요청이 나가지 않았으므로 `requestId`가 없습니다.
두 단계를 구분해서 처리하려면 `uploadAttachment()`를 직접 호출하고 `fileId()`로 넘기십시오.

## 테스트

```bash
composer install
vendor/bin/phpunit
```

테스트는 실제 SENS API를 호출하지 않습니다. 애플리케이션 코드에서 응답을 가로채고 싶다면
`setHttpClient()`로 Guzzle 클라이언트를 교체할 수 있습니다.

```php
Sens::sms()->setHttpClient($mockedGuzzleClient);
```

## 기능

- SMS · LMS · MMS 발송
- 카카오 알림톡 발송
- 발송 결과 추적 (`requestId` / `messageId` 기반 조회)
- 예약 발송 상태 조회 및 취소

## 커뮤니티

- [라라벨코리아](https://laravel.kr/)
- [라라벨코리아 오픈채팅](https://open.kakao.com/o/g3dWlf0)

## 라이선스

이 패키지는 [Apache License 2.0](LICENSE.md)으로 배포됩니다.

원본인 [seungmun/laravel-sens](https://github.com/seungmun/laravel-sens)는 MIT 라이선스로 공개되었으며,
MIT 라이선스가 요구하는 저작권 고지는 [NOTICE](NOTICE) 파일에 그대로 보존되어 있습니다.
원본에서 유래한 부분에는 해당 고지가 계속 적용됩니다.

<details>
<summary><strong>변경 사항 안내</strong> (발송 결과 추적 기능 도입 시점)</summary>

발송 결과 추적 기능이 추가되면서 다음이 바뀌었습니다. 기존 발송 코드는 그대로 동작합니다.

- `Sms::send()` / `AlimTalk::send()`가 `void` 대신 `SendResponse`를 반환합니다.
- 두 Notification 채널의 `send()`에서 `: void` 반환 타입 선언이 제거되었습니다.
  채널을 상속해 재정의한 경우 시그니처를 맞춰 주십시오.
- `Contracts\Sens` 인터페이스에 조회 메서드가 추가되었습니다.
  이 인터페이스를 직접 구현한 코드가 있다면 새 메서드를 구현해야 합니다.
- SMS/알림톡 클라이언트가 컨테이너에 싱글톤으로 등록되어,
  채널 밖에서도 `app(Sms::class)` 또는 `Sens` 파사드로 사용할 수 있습니다.
- MMS 첨부 파일이 현재 API 명세에 맞춰 **업로드 후 파일 아이디로 발송**하는 방식으로 바뀌었습니다.
  `SmsMessage::file()`을 쓰던 코드는 그대로 동작하며, 발송할 때 업로드도 함께 처리합니다.
- `AlimTalkMessage::setSchedule()`이 값을 설정하지 못하던 문제를 고쳤습니다.
  더불어 스케줄 코드를 지정하지 않은 경우 요청에 `scheduleCode: null`을 보내지 않습니다.
- 발신 번호를 `config/laravel-sens.php`의 `sms_from`으로도 설정할 수 있습니다.
  기존 `config/services.php` 설정이 있으면 그 값이 우선합니다.

</details>
