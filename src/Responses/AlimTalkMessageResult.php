<?php

namespace Daworks\Sens\Responses;

use Daworks\Sens\Contracts\MessageResult;

/**
 * 알림톡 개별 메시지의 발송 결과.
 *
 * 알림톡은 상태를 두 단계로 나누어 보고한다.
 *
 *  - requestStatus* : SENS 가 발송 요청을 접수했는지 여부 (A000 이 성공)
 *  - messageStatus* : 카카오가 실제로 수신자에게 전달했는지 여부 (0000 이 성공)
 *
 * 발송 직후의 응답에는 requestStatus* 만 존재하므로, 그 시점에는 아직
 * "전달 완료"를 단정할 수 없다.
 *
 * @see https://api.ncloud-docs.com/docs/sens-alimtalk-get
 */
class AlimTalkMessageResult extends Response implements MessageResult
{
    /** 요청 접수 성공 코드. */
    public const REQUEST_SUCCESS_CODE = 'A000';

    /** 수신 성공 코드. */
    public const MESSAGE_SUCCESS_CODE = '0000';

    /** @var string|null 요청 아이디 */
    public $requestId;

    /** @var string|null 메시지 아이디 */
    public $messageId;

    /** @var string|null 요청 일시 */
    public $requestTime;

    /** @var string|null 완료 일시 */
    public $completeTime;

    /** @var string|null 채널 아이디 */
    public $plusFriendId;

    /** @var string|null 템플릿 코드 */
    public $templateCode;

    /** @var string|null 수신 번호 */
    public $to;

    /** @var string|null 메시지 내용 */
    public $content;

    /** @var string|null 요청 상태 코드 (A000 이 성공) */
    public $requestStatusCode;

    /** @var string|null 요청 상태 (success / fail) */
    public $requestStatusName;

    /** @var string|null 요청 상태 설명 */
    public $requestStatusDesc;

    /** @var string|null 수신 상태 코드 (0000 이 성공) */
    public $messageStatusCode;

    /** @var string|null 수신 상태 (success / processing / fail) */
    public $messageStatusName;

    /** @var string|null 수신 상태 설명 */
    public $messageStatusDesc;

    /** @var bool SMS 대체 발송 사용 여부 */
    public $useSmsFailover = false;

    /** @var array|null SMS 대체 발송 정보 */
    public $failover;

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
        $this->plusFriendId = $this->get('plusFriendId');
        $this->templateCode = $this->get('templateCode');
        $this->to = $this->get('to');
        $this->content = $this->get('content');
        $this->requestStatusCode = $this->get('requestStatusCode');
        $this->requestStatusName = $this->get('requestStatusName');
        $this->requestStatusDesc = $this->get('requestStatusDesc');
        $this->messageStatusCode = $this->get('messageStatusCode');
        $this->messageStatusName = $this->get('messageStatusName');
        $this->messageStatusDesc = $this->get('messageStatusDesc');
        $this->useSmsFailover = (bool) $this->get('useSmsFailover', false);
        $this->failover = $this->get('failover');
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
     * SENS 가 발송 요청을 정상적으로 접수했는지 여부.
     *
     * 접수에 실패하면 발송 자체가 이루어지지 않는다.
     *
     * @return bool
     */
    public function isAccepted()
    {
        return $this->requestStatusCode === self::REQUEST_SUCCESS_CODE;
    }

    /**
     * 알림톡이 수신자에게 전달되었는지 여부.
     *
     * SMS 대체 발송 결과는 고려하지 않는다. 대체 발송까지 포함한 최종 전달
     * 여부는 isDelivered() 를 사용한다.
     *
     * @return bool
     */
    public function isSuccessful()
    {
        if (! $this->isAccepted()) {
            return false;
        }

        return $this->messageStatusCode === self::MESSAGE_SUCCESS_CODE
            || $this->messageStatusName === 'success';
    }

    /**
     * 알림톡 또는 SMS 대체 발송 중 어느 하나로 수신자에게 전달되었는지 여부.
     *
     * @return bool
     */
    public function isDelivered()
    {
        return $this->isSuccessful() || $this->failoverSucceeded();
    }

    /**
     * SMS 대체 발송이 실제로 실행되었는지 여부.
     *
     * useSmsFailover 는 "대체 발송을 사용하도록 설정했는가"일 뿐이며,
     * 실제 실행 여부는 failover 객체의 존재로 판단한다.
     *
     * @return bool
     */
    public function hasFailover()
    {
        return ! empty($this->failover);
    }

    /**
     * SMS 대체 발송이 성공했는지 여부.
     *
     * @return bool
     */
    public function failoverSucceeded()
    {
        if (! $this->hasFailover()) {
            return false;
        }

        return $this->get('failover.messageStatusName') === 'success';
    }

    /**
     * 아직 발송 처리가 끝나지 않았는지 여부.
     *
     * 요청 접수 직후에는 수신 상태가 아직 존재하지 않으므로 처리 중으로 본다.
     *
     * @return bool
     */
    public function isPending()
    {
        if (! $this->isAccepted()) {
            return false;
        }

        return $this->messageStatusName === 'processing' || $this->messageStatusName === null;
    }

    /**
     * 수신 상태 코드. 접수 단계에서 실패한 경우 요청 상태 코드를 돌려준다.
     *
     * @return string|null
     */
    public function getStatusCode()
    {
        return $this->isAccepted() ? $this->messageStatusCode : $this->requestStatusCode;
    }

    /**
     * @return string|null
     */
    public function getStatusName()
    {
        return $this->isAccepted() ? $this->messageStatusName : $this->requestStatusName;
    }

    /**
     * @return string|null
     */
    public function getStatusMessage()
    {
        return $this->isAccepted() ? $this->messageStatusDesc : $this->requestStatusDesc;
    }
}
