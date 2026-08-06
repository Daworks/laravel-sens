<?php

namespace Daworks\Sens\Responses;

use Daworks\Sens\Contracts\MessageResult;

/**
 * SMS / LMS / MMS 개별 메시지의 발송 결과.
 *
 * @see https://api.ncloud-docs.com/docs/sens-sms-get
 */
class SmsMessageResult extends Response implements MessageResult
{
    /** 발송 처리가 완료된 상태. */
    public const STATUS_COMPLETED = 'COMPLETED';

    /** @var string|null 요청 아이디 */
    public $requestId;

    /** @var string|null 메시지 아이디 */
    public $messageId;

    /** @var string|null 요청 일시 */
    public $requestTime;

    /** @var string|null 완료 일시 */
    public $completeTime;

    /** @var string|null 메시지 타입 (SMS / LMS / MMS) */
    public $type;

    /** @var string|null 발신 번호 */
    public $from;

    /** @var string|null 수신 번호 */
    public $to;

    /** @var string|null 요청 상태 (READY / PROCESSING / COMPLETED) */
    public $status;

    /** @var string|null 수신 결과 코드 */
    public $statusCode;

    /** @var string|null 수신 상태 (success / fail) */
    public $statusName;

    /** @var string|null 수신 상태 메시지 */
    public $statusMessage;

    /**
     * @param  array  $payload
     */
    public function __construct(array $payload)
    {
        parent::__construct($payload);

        $this->requestId = $this->get('requestId');
        $this->messageId = $this->get('messageId');
        $this->requestTime = $this->get('requestTime');
        $this->completeTime = $this->get('completeTime');
        $this->type = $this->get('type');
        $this->from = $this->get('from');
        $this->to = $this->get('to');
        $this->status = $this->get('status');
        $this->statusCode = $this->get('statusCode');
        $this->statusName = $this->get('statusName');
        $this->statusMessage = $this->get('statusMessage');
    }

    /**
     * @return string|null
     */
    public function getMessageId()
    {
        return $this->messageId;
    }

    /**
     * @return string|null
     */
    public function getRequestId()
    {
        return $this->requestId;
    }

    /**
     * @return string|null
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * 수신 성공 여부.
     *
     * 발송 처리가 끝나기 전에는 statusName 이 비어 있을 수 있으므로,
     * 완료 상태이면서 성공으로 보고된 경우에만 성공으로 판단한다.
     *
     * @return bool
     */
    public function isSuccessful()
    {
        return ! $this->isPending() && $this->statusName === 'success';
    }

    /**
     * 아직 발송 처리가 끝나지 않았는지 여부.
     *
     * @return bool
     */
    public function isPending()
    {
        return $this->status !== null && $this->status !== self::STATUS_COMPLETED;
    }

    /**
     * @return string|null
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @return string|null
     */
    public function getStatusName()
    {
        return $this->statusName;
    }

    /**
     * @return string|null
     */
    public function getStatusMessage()
    {
        return $this->statusMessage;
    }
}
