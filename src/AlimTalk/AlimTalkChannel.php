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
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @throws \Daworks\Sens\Exceptions\SensException
     */
    public function send($notifiable, Notification $notification): void
    {
        /** @var \Daworks\Sens\Sms\SmsMessage $message */
        $message = $notification->{'toAlimTalk'}($notifiable);

        $this->alimtalk->send($message->toArray());
    }
}
