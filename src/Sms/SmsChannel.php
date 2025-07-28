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
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @throws \Daworks\Sens\Exceptions\SensException
     */
    public function send($notifiable, Notification $notification): void
    {
        /** @var \Daworks\Sens\Sms\SmsMessage $message */
        $message = $notification->{'toSms'}($notifiable);

        $this->sms->send($message->toArray());
    }
}
