<?php

namespace Daworks\Sens\Tests;

use Daworks\Sens\AlimTalk\AlimTalk;
use Daworks\Sens\AlimTalk\AlimTalkChannel;
use Daworks\Sens\AlimTalk\AlimTalkMessage;
use Daworks\Sens\Responses\AlimTalkSendResponse;
use Daworks\Sens\Responses\SmsSendResponse;
use Daworks\Sens\Sms\SmsChannel;
use Daworks\Sens\Sms\SmsMessage;
use Daworks\Sens\Sms\Sms;
use Illuminate\Notifications\Notification;
use Mockery as m;

/**
 * 채널의 반환값은 라라벨이 NotificationSent 이벤트의 $response 로 전달한다.
 * 반환하지 않으면 발송한 메시지를 추적할 방법이 없다.
 */
class NotificationChannelTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();

        parent::tearDown();
    }

    public function testSmsChannelReturnsTheSendResponse(): void
    {
        $message = m::mock(SmsMessage::class);
        $message->shouldReceive('toArray')->once()->andReturn(['type' => 'SMS']);

        $expected = new SmsSendResponse(['requestId' => 'RSLA-1', 'statusCode' => '202']);

        $sms = m::mock(Sms::class);
        $sms->shouldReceive('send')->once()->with(['type' => 'SMS'])->andReturn($expected);

        $notification = new class($message) extends Notification {
            public function __construct(private $message)
            {
            }

            public function toSms($notifiable)
            {
                return $this->message;
            }
        };

        $response = (new SmsChannel($sms))->send(new \stdClass, $notification);

        $this->assertSame($expected, $response);
        $this->assertSame('RSLA-1', $response->requestId);
    }

    public function testAlimTalkChannelReturnsTheSendResponse(): void
    {
        $message = m::mock(AlimTalkMessage::class);
        $message->shouldReceive('toArray')->once()->andReturn(['plusFriendId' => '@daworks']);

        $expected = new AlimTalkSendResponse(['requestId' => 'RBAA-1', 'statusCode' => '202']);

        $alimTalk = m::mock(AlimTalk::class);
        $alimTalk->shouldReceive('send')->once()->with(['plusFriendId' => '@daworks'])->andReturn($expected);

        $notification = new class($message) extends Notification {
            public function __construct(private $message)
            {
            }

            public function toAlimTalk($notifiable)
            {
                return $this->message;
            }
        };

        $response = (new AlimTalkChannel($alimTalk))->send(new \stdClass, $notification);

        $this->assertSame($expected, $response);
        $this->assertSame('RBAA-1', $response->requestId);
    }
}
