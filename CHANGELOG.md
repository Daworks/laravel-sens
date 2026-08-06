# 변경 이력

이 파일은 [Keep a Changelog](https://keepachangelog.com/) 형식을 따르며,
버전은 [유의적 버전](https://semver.org/lang/ko/)을 따릅니다.

## [1.3.1] - 2026-08-06

### 수정

- 자격 증명이 비어 있어도 클라이언트를 만들 수 있게 했습니다. 이전에는 설정값이 `null`이면
  컨테이너가 객체를 만드는 시점에 `TypeError`가 나서, 설정이 비었다는 사실을 알릴 자리조차
  없이 애플리케이션이 죽었습니다. 값이 비었는지는 실제로 발송하거나 조회할 때
  `SensException`으로 알립니다.

## [1.3.0] - 2026-08-06

### 추가

- 초당 발송 건수를 적어 두는 `rate_limit` 설정 (기본값 `30`, 환경 변수 `SENS_RATE_LIMIT`).
  패키지가 발송 속도를 직접 조절하지는 않으며, 실제 속도 제한은 이 값을 읽어 애플리케이션에서
  구현합니다.

## [1.2.0] - 2026-08-06

### 변경

- 라이선스를 MIT에서 Apache License 2.0으로 바꿨습니다. 원본인
  [seungmun/laravel-sens](https://github.com/seungmun/laravel-sens)의 MIT 저작권 고지는
  [NOTICE](NOTICE) 파일에 그대로 보존되어 있으며, 원본에서 유래한 부분에는 해당 고지가
  계속 적용됩니다.

## [1.1.0] - 2026-08-06

### 추가

- 발송 결과 추적. `requestId`로 발송 요청에 속한 메시지 목록을 조회하고, `messageId`로 개별
  메시지의 수신 상태를 확인할 수 있습니다.
- 예약 발송 상태 조회 및 취소.
- 라라벨 13.x 지원.

### 변경

기존 발송 코드는 그대로 동작합니다.

- `Sms::send()` / `AlimTalk::send()`가 `void` 대신 `SendResponse`를 반환합니다.
- 두 Notification 채널의 `send()`에서 `: void` 반환 타입 선언이 제거되었습니다.
  채널을 상속해 재정의한 경우 시그니처를 맞춰 주십시오.
- `Contracts\Sens` 인터페이스에 조회 메서드가 추가되었습니다.
  이 인터페이스를 직접 구현한 코드가 있다면 새 메서드를 구현해야 합니다.
- SMS/알림톡 클라이언트가 컨테이너에 싱글톤으로 등록되어,
  채널 밖에서도 `app(Sms::class)` 또는 `Sens` 파사드로 사용할 수 있습니다.
- MMS 첨부 파일이 현재 API 명세에 맞춰 **업로드 후 파일 아이디로 발송**하는 방식으로 바뀌었습니다.
  `SmsMessage::file()`을 쓰던 코드는 그대로 동작하며, 발송할 때 업로드도 함께 처리합니다.
- 발신 번호를 `config/laravel-sens.php`의 `sms_from`으로도 설정할 수 있습니다.
  기존 `config/services.php` 설정이 있으면 그 값이 우선합니다.

### 수정

- `AlimTalkMessage::setSchedule()`이 값을 설정하지 못하던 문제를 고쳤습니다.
  더불어 스케줄 코드를 지정하지 않은 경우 요청에 `scheduleCode: null`을 보내지 않습니다.

## [1.0.0] - 2025-07-29

### 추가

- [seungmun/laravel-sens](https://github.com/seungmun/laravel-sens)를 포크해 라라벨
  9.x ~ 12.x에서 동작하도록 고친 첫 릴리즈입니다.

[1.3.1]: https://github.com/Daworks/laravel-sens/compare/1.3.0...1.3.1
[1.3.0]: https://github.com/Daworks/laravel-sens/compare/1.2.0...1.3.0
[1.2.0]: https://github.com/Daworks/laravel-sens/compare/1.1.0...1.2.0
[1.1.0]: https://github.com/Daworks/laravel-sens/compare/1.0.0...1.1.0
[1.0.0]: https://github.com/Daworks/laravel-sens/releases/tag/1.0.0
