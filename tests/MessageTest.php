<?php

namespace Daworks\Sens\Tests;

use Daworks\Sens\AlimTalk\AlimTalkMessage;
use Daworks\Sens\Sms\SmsMessage;
use Illuminate\Container\Container;

/**
 * 메시지 객체가 SENS 요청 형식으로 올바르게 직렬화되는지 검증한다.
 */
class MessageTest extends TestCase
{
    /**
     * config() 헬퍼가 동작하도록 최소한의 컨테이너를 준비한다.
     *
     * @param  array  $values
     * @return void
     */
    protected function fakeConfig(array $values = [])
    {
        $container = new Container;

        $container->instance('config', new class($values) {
            public function __construct(private array $values)
            {
            }

            public function get($key, $default = null)
            {
                return $this->values[$key] ?? $default;
            }
        });

        Container::setInstance($container);
    }

    protected function tearDown(): void
    {
        // 컨테이너를 남겨두면 다른 테스트에 새어 나간다.
        Container::setInstance(null);

        parent::tearDown();
    }

    public function testSmsMessagePrefersTheSenderFromServicesConfig(): void
    {
        $this->fakeConfig([
            'services.sens.services.sms.sender' => '0551110000',
            'laravel-sens.sms_from' => '0552220000',
        ]);

        $this->assertSame('0551110000', (new SmsMessage)->from);
    }

    public function testSmsMessageFallsBackToThePackageConfig(): void
    {
        $this->fakeConfig(['laravel-sens.sms_from' => '0552220000']);

        $this->assertSame('0552220000', (new SmsMessage)->from);
    }

    public function testSmsMessageCarriesReservationFields(): void
    {
        $this->fakeConfig();

        $payload = (new SmsMessage)
            ->to('010-1111-2222')
            ->content('테스트')
            ->setReserved('2025-11-25 09:50')
            ->toArray();

        $this->assertSame('2025-11-25 09:50', $payload['reserveTime']);
        $this->assertSame('Asia/Seoul', $payload['reserveTimeZone']);
        $this->assertSame([['to' => '01011112222']], $payload['messages']);
    }

    public function testSmsMessageOmitsReservationFieldsWhenNotReserved(): void
    {
        $this->fakeConfig();

        $payload = (new SmsMessage)->to('01011112222')->content('테스트')->toArray();

        $this->assertArrayNotHasKey('reserveTime', $payload);
        $this->assertArrayNotHasKey('reserveTimeZone', $payload);
    }

    public function testSmsMessageAcceptsAPreUploadedFileId(): void
    {
        $this->fakeConfig();

        $payload = (new SmsMessage)
            ->type('MMS')
            ->to('01011112222')
            ->content('테스트')
            ->fileId('a136000074f7')
            ->toArray();

        $this->assertSame([['fileId' => 'a136000074f7']], $payload['files']);
    }

    public function testSmsMessageEncodesFileContentsForLaterUpload(): void
    {
        $this->fakeConfig();

        $path = tempnam(sys_get_temp_dir(), 'sens');
        file_put_contents($path, 'image-binary');

        try {
            $payload = (new SmsMessage)
                ->type('MMS')
                ->to('01011112222')
                ->content('테스트')
                ->file('invoice.jpg', $path)
                ->toArray();

            $this->assertSame(
                [['name' => 'invoice.jpg', 'body' => base64_encode('image-binary')]],
                $payload['files']
            );
        } finally {
            @unlink($path);
        }
    }

    public function testAlimTalkMessageSetsTheScheduleCode(): void
    {
        $this->fakeConfig(['laravel-sens.plus_friend_id' => '@daworks']);

        $payload = (new AlimTalkMessage)
            ->templateCode('TEMPLATE001')
            ->to('01011112222')
            ->content('테스트')
            ->setSchedule('SCHEDULE001')
            ->toArray();

        $this->assertSame('SCHEDULE001', $payload['scheduleCode']);
        $this->assertSame('@daworks', $payload['plusFriendId']);
    }

    public function testAlimTalkMessageOmitsTheScheduleCodeWhenNotSet(): void
    {
        $this->fakeConfig(['laravel-sens.plus_friend_id' => '@daworks']);

        $payload = (new AlimTalkMessage)
            ->templateCode('TEMPLATE001')
            ->to('01011112222')
            ->content('테스트')
            ->toArray();

        $this->assertArrayNotHasKey('scheduleCode', $payload);
    }
}
