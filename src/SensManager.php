<?php

namespace Daworks\Sens;

use Daworks\Sens\AlimTalk\AlimTalk;
use Daworks\Sens\Sms\Sms;
use Illuminate\Contracts\Container\Container;

/**
 * SENS 채널별 클라이언트에 접근하기 위한 진입점.
 *
 * 발송은 Notification 채널로 하더라도, 발송 결과 조회는 이 매니저를 통해
 * 직접 호출하게 된다.
 *
 * @see \Daworks\Sens\Facades\Sens
 */
class SensManager
{
    /** @var \Illuminate\Contracts\Container\Container */
    protected $app;

    /**
     * @param  \Illuminate\Contracts\Container\Container  $app
     */
    public function __construct(Container $app)
    {
        $this->app = $app;
    }

    /**
     * SMS/LMS/MMS 클라이언트.
     *
     * @return \Daworks\Sens\Sms\Sms
     */
    public function sms()
    {
        return $this->app->make(Sms::class);
    }

    /**
     * 카카오 알림톡 클라이언트.
     *
     * PHP 는 메서드명의 대소문자를 구분하지 않으므로 alimtalk() 으로도 호출할 수 있다.
     *
     * @return \Daworks\Sens\AlimTalk\AlimTalk
     */
    public function alimTalk()
    {
        return $this->app->make(AlimTalk::class);
    }
}
