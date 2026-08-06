<?php

namespace Daworks\Sens\Responses;

/**
 * SMS / LMS / MMS 발송 요청에 대한 응답.
 *
 * SMS 발송 응답에는 개별 메시지 정보가 포함되지 않는다. 수신자별 결과는
 * requestId 로 발송 목록을 조회해야 확인할 수 있다.
 *
 * @see https://api.ncloud-docs.com/docs/sens-sms-send
 */
class SmsSendResponse extends SendResponse
{
    /**
     * 발송 요청이 정상적으로 접수되었는지 여부.
     *
     * @return bool
     */
    public function isSuccessful()
    {
        return $this->isAccepted();
    }
}
