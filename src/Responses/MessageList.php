<?php

namespace Daworks\Sens\Responses;

use ArrayIterator;
use Countable;
use Daworks\Sens\Contracts\MessageResult;
use IteratorAggregate;
use Traversable;

/**
 * 메시지 발송 목록 조회 결과.
 *
 * requestId 로 조회하면 해당 발송 요청에 포함된 모든 메시지가 담겨 온다.
 * 여기서 얻은 messageId 로 개별 메시지의 상세 결과를 조회할 수 있다.
 *
 * @see https://api.ncloud-docs.com/docs/sens-sms-list
 * @see https://api.ncloud-docs.com/docs/sens-alimtalk-list
 */
class MessageList extends Response implements Countable, IteratorAggregate
{
    /** @var string|null 요청 아이디 (requestId 로 조회한 경우) */
    public $requestId;

    /** @var string|null 상태 코드 */
    public $statusCode;

    /** @var string|null 상태 (success / processing / reserved / fail) */
    public $statusName;

    /** @var int|null 페이지당 항목 수 */
    public $pageSize;

    /** @var int|null 페이지 인덱스 */
    public $pageIndex;

    /** @var int|null 응답 결과 수 */
    public $itemCount;

    /** @var bool 다음 페이지 존재 여부 */
    public $hasMore = false;

    /** @var string|null 다음 페이지 조회에 사용할 토큰 */
    public $nextToken;

    /** @var \Daworks\Sens\Contracts\MessageResult[] */
    protected $messages = [];

    /**
     * @param  array  $payload
     * @param  callable  $factory  개별 메시지를 MessageResult 로 변환하는 함수
     */
    public function __construct(array $payload, callable $factory)
    {
        parent::__construct($payload);

        $this->requestId = $this->get('requestId');
        $this->statusCode = $this->get('statusCode');
        $this->statusName = $this->get('statusName');
        $this->pageSize = $this->get('pageSize');
        $this->pageIndex = $this->get('pageIndex');
        $this->itemCount = $this->get('itemCount');
        $this->hasMore = (bool) $this->get('hasMore', false);
        $this->nextToken = $this->get('nextToken');

        $this->messages = array_map($factory, (array) $this->get('messages', []));
    }

    /**
     * 조회된 메시지 목록.
     *
     * @return \Daworks\Sens\Contracts\MessageResult[]
     */
    public function messages()
    {
        return $this->messages;
    }

    /**
     * 첫 번째 메시지.
     *
     * @return \Daworks\Sens\Contracts\MessageResult|null
     */
    public function first()
    {
        return $this->messages[0] ?? null;
    }

    /**
     * 메시지 아이디 목록.
     *
     * @return string[]
     */
    public function messageIds()
    {
        return array_values(array_filter(array_map(
            function (MessageResult $message) {
                return $message->getMessageId();
            },
            $this->messages
        )));
    }

    /**
     * 수신에 성공한 메시지 목록.
     *
     * @return \Daworks\Sens\Contracts\MessageResult[]
     */
    public function successful()
    {
        return $this->filterMessages(function (MessageResult $message) {
            return $message->isSuccessful();
        });
    }

    /**
     * 수신에 실패한 메시지 목록. 아직 처리 중인 메시지는 제외한다.
     *
     * @return \Daworks\Sens\Contracts\MessageResult[]
     */
    public function failed()
    {
        return $this->filterMessages(function (MessageResult $message) {
            return ! $message->isSuccessful() && ! $message->isPending();
        });
    }

    /**
     * 아직 처리 중인 메시지 목록.
     *
     * @return \Daworks\Sens\Contracts\MessageResult[]
     */
    public function pending()
    {
        return $this->filterMessages(function (MessageResult $message) {
            return $message->isPending();
        });
    }

    /**
     * 조회된 메시지가 하나도 없는지 여부.
     *
     * 예약 발송이거나 발송 직후에는 SENS 가 메시지 목록을 비워서 응답한다.
     * 이 경우는 "실패"가 아니라 "아직 결과가 없음"으로 다루어야 한다.
     *
     * @return bool
     */
    public function isEmpty()
    {
        return $this->messages === [];
    }

    /**
     * 모든 메시지가 수신에 성공했는지 여부.
     *
     * 조회 결과가 비어 있으면 판단할 근거가 없으므로 false 를 돌려준다.
     * 실패와 구분하려면 isEmpty() 를 함께 확인해야 한다.
     *
     * @return bool
     */
    public function isSuccessful()
    {
        return ! $this->isEmpty() && $this->failed() === [] && $this->pending() === [];
    }

    /**
     * 아직 결과를 확정할 수 없는지 여부.
     *
     * 조회 결과가 비어 있거나 처리 중인 메시지가 남아 있으면 참이다.
     * 폴링할 때 이 값이 참이면 다시 조회해야 한다.
     *
     * @return bool
     */
    public function isPending()
    {
        return $this->isEmpty() || $this->pending() !== [];
    }

    /**
     * @param  callable  $callback
     * @return \Daworks\Sens\Contracts\MessageResult[]
     */
    protected function filterMessages(callable $callback)
    {
        return array_values(array_filter($this->messages, $callback));
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->messages);
    }

    /**
     * @return \Traversable
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->messages);
    }
}
