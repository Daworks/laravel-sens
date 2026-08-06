<?php

namespace Daworks\Sens\Responses;

use Daworks\Sens\Contracts\MessageResult;

/**
 * 메시지 발송 요청에 대한 응답.
 *
 * 발송 API 는 비동기로 동작한다. 202 응답은 "요청이 접수되었다"는 뜻이며
 * 수신자에게 전달되었다는 보장이 아니다. 실제 수신 여부는 여기서 받은
 * requestId 로 조회해야 한다.
 */
abstract class SendResponse extends Response
{
    /** 발송 요청이 정상적으로 접수되었을 때의 상태 코드. */
    public const ACCEPTED = '202';

    /** @var string|null 요청 아이디. 발송 결과를 추적하는 열쇠. */
    public $requestId;

    /** @var string|null 요청 일시 */
    public $requestTime;

    /** @var string|null 상태 코드 (202 가 접수 성공) */
    public $statusCode;

    /** @var string|null 상태 (success / processing / reserved / fail) */
    public $statusName;

    /**
     * @param  array  $payload
     */
    public function __construct(array $payload)
    {
        parent::__construct($payload);

        $this->requestId = $this->get('requestId');
        $this->requestTime = $this->get('requestTime');
        $this->statusCode = $this->get('statusCode');
        $this->statusName = $this->get('statusName');
    }

    /**
     * SENS 가 발송 요청을 접수했는지 여부.
     *
     * @return bool
     */
    public function isAccepted()
    {
        return $this->statusCode === self::ACCEPTED;
    }

    /**
     * 예약 발송으로 접수되었는지 여부.
     *
     * @return bool
     */
    public function isReserved()
    {
        return $this->statusName === 'reserved';
    }

    /**
     * 이 응답만으로 확인 가능한 범위에서 발송 요청이 성공했는지 여부.
     *
     * @return bool
     */
    abstract public function isSuccessful();

    /**
     * 응답에 포함된 개별 메시지 결과.
     *
     * @return \Daworks\Sens\Contracts\MessageResult[]
     */
    public function messages()
    {
        return [];
    }

    /**
     * 개별 메시지 아이디 목록.
     *
     * @return string[]
     */
    public function messageIds()
    {
        return array_values(array_filter(array_map(
            function (MessageResult $message) {
                return $message->getMessageId();
            },
            $this->messages()
        )));
    }

    /**
     * 접수 단계에서 실패한 메시지 목록.
     *
     * @return \Daworks\Sens\Contracts\MessageResult[]
     */
    public function failedMessages()
    {
        return array_values(array_filter(
            $this->messages(),
            function (MessageResult $message) {
                return ! $message->isSuccessful() && ! $message->isPending();
            }
        ));
    }
}
