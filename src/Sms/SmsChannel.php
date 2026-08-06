<?php

namespace Daworks\Sens\Sms;

use Illuminate\Notifications\Notification;

class SmsChannel
{
    /**
     * SENS instance implements.
     *
     * @var \Daworks\Sens\Sms\Sms
     */
    protected $sms;

    /**
     * Create a new SENS sms channel instance.
     *
     * @param  \Daworks\Sens\Sms\Sms  $sens
     */
    public function __construct(Sms $sens)
    {
        $this->sms = $sens;
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
     * @return \Daworks\Sens\Responses\SmsSendResponse
     *
     * @throws \Daworks\Sens\Exceptions\SensException
     */
    public function send($notifiable, Notification $notification)
    {
        /** @var \Daworks\Sens\Sms\SmsMessage $message */
        $message = $notification->{'toSms'}($notifiable);

        return $this->sms->send($message->toArray());
    }
}
