<?php

namespace Daworks\Sens\AlimTalk;

use Illuminate\Notifications\Notification;

class AlimTalkChannel
{
    /**
     * SENS instance implements.
     *
     * @var \Daworks\Sens\AlimTalk\AlimTalk
     */
    protected $alimtalk;

    /**
     * Create a new SENS alimtalk channel instance.
     *
     * @param  \Daworks\Sens\AlimTalk\AlimTalk  $sens
     */
    public function __construct(AlimTalk $sens)
    {
        $this->alimtalk = $sens;
    }

    /**
     * Send the specified SENS notification.
     *
     * 반환값은 라라벨이 NotificationSent 이벤트의 $response 로 전달한다.
     * 큐로 발송하는 경우 호출부에서는 반환값을 받을 수 없으므로,
     * requestId 를 기록하려면 이벤트 리스너를 사용해야 한다.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return \Daworks\Sens\Responses\AlimTalkSendResponse
     *
     * @throws \Daworks\Sens\Exceptions\SensException
     */
    public function send($notifiable, Notification $notification)
    {
        /** @var \Daworks\Sens\AlimTalk\AlimTalkMessage $message */
        $message = $notification->{'toAlimTalk'}($notifiable);

        return $this->alimtalk->send($message->toArray());
    }
}
