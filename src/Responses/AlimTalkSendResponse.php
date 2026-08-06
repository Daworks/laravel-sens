<?php

namespace Daworks\Sens\Responses;

/**
 * 알림톡 발송 요청에 대한 응답.
 *
 * 즉시 발송인 경우 응답에 수신자별 접수 결과(messages)가 포함된다.
 * 예약 발송인 경우에는 포함되지 않는다.
 *
 * @see https://api.ncloud-docs.com/docs/sens-alimtalk-send
 */
class AlimTalkSendResponse extends SendResponse
{
    /** @var \Daworks\Sens\Responses\AlimTalkMessageResult[] */
    protected $messages = [];

    /**
     * @param  array  $payload
     */
    public function __construct(array $payload)
    {
        parent::__construct($payload);

        $this->messages = array_map(
            function (array $message) {
                // 개별 메시지에는 requestId 가 없으므로 응답 최상위의 값을 물려준다.
                return new AlimTalkMessageResult($message + ['requestId' => $this->requestId]);
            },
            (array) $this->get('messages', [])
        );
    }

    /**
     * 수신자별 접수 결과.
     *
     * @return \Daworks\Sens\Responses\AlimTalkMessageResult[]
     */
    public function messages()
    {
        return $this->messages;
    }

    /**
     * 발송 요청이 접수되었고, 모든 수신자의 접수도 성공했는지 여부.
     *
     * 개별 메시지의 접수가 실패하더라도 HTTP 응답은 202 로 내려오므로
     * messages 를 함께 확인해야 한다.
     *
     * @return bool
     */
    public function isSuccessful()
    {
        if (! $this->isAccepted()) {
            return false;
        }

        foreach ($this->messages as $message) {
            if (! $message->isAccepted()) {
                return false;
            }
        }

        return true;
    }

    /**
     * 접수에 실패한 메시지 목록.
     *
     * @return \Daworks\Sens\Responses\AlimTalkMessageResult[]
     */
    public function failedMessages()
    {
        return array_values(array_filter(
            $this->messages,
            function (AlimTalkMessageResult $message) {
                return ! $message->isAccepted();
            }
        ));
    }
}
