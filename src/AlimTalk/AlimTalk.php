<?php

namespace Daworks\Sens\AlimTalk;

use Daworks\Sens\Contracts\MessageResult;
use Daworks\Sens\Responses\AlimTalkMessageResult;
use Daworks\Sens\Responses\AlimTalkSendResponse;
use Daworks\Sens\Responses\MessageList;
use Daworks\Sens\Responses\ReservationStatus;
use Daworks\Sens\Responses\SendResponse;
use Daworks\Sens\Sens;

/**
 * SENS 카카오 알림톡 API 클라이언트.
 *
 * @see https://api.ncloud-docs.com/docs/ai-application-service-sens-alimtalkv2
 */
class AlimTalk extends Sens
{
    /**
     * @param  array  $config
     */
    public function __construct(array $config)
    {
        parent::__construct($config);

        $this->setServiceId($config['alimtalk_service_id']);
    }

    /**
     * @return string
     */
    protected function servicePath()
    {
        return '/alimtalk/v2/services/' . $this->getServiceId();
    }

    /**
     * 알림톡을 발송한다.
     *
     * @param  array  $params
     * @return \Daworks\Sens\Responses\AlimTalkSendResponse
     *
     * @throws \Daworks\Sens\Exceptions\SensException
     */
    public function send(array $params): SendResponse
    {
        return new AlimTalkSendResponse(
            $this->call('POST', $this->servicePath() . '/messages', $params)
        );
    }

    /**
     * 요청 아이디로 해당 발송 요청에 속한 메시지 목록을 조회한다.
     *
     * @param  string  $requestId
     * @param  array  $filters
     * @return \Daworks\Sens\Responses\MessageList
     *
     * @throws \Daworks\Sens\Exceptions\SensException
     */
    public function findByRequestId(string $requestId, array $filters = []): MessageList
    {
        return $this->findMessages(array_merge($filters, ['requestId' => $requestId]));
    }

    /**
     * 조건으로 메시지 발송 목록을 조회한다. 최근 30일 이내만 조회할 수 있다.
     *
     * requestId 없이 조회할 때는 채널 아이디(plusFriendId)가 필요하며,
     * 지정하지 않으면 설정값을 사용한다.
     *
     * @param  array  $filters
     * @return \Daworks\Sens\Responses\MessageList
     *
     * @throws \Daworks\Sens\Exceptions\SensException
     */
    public function findMessages(array $filters = []): MessageList
    {
        if (empty($filters['requestId']) && empty($filters['plusFriendId'])) {
            $filters['plusFriendId'] = $this->config['plus_friend_id'] ?? null;
        }

        return $this->newMessageList(
            $this->call('GET', $this->servicePath() . '/messages', [], $filters)
        );
    }

    /**
     * 메시지 아이디로 개별 메시지의 발송 결과를 조회한다.
     *
     * SMS 와 달리 알림톡 단건 조회는 메시지 정보를 감싸지 않고 그대로 응답한다.
     *
     * @param  string  $messageId
     * @return \Daworks\Sens\Responses\AlimTalkMessageResult|null
     *
     * @throws \Daworks\Sens\Exceptions\SensException
     */
    public function findMessage(string $messageId): ?MessageResult
    {
        $payload = $this->call('GET', $this->servicePath() . '/messages/' . $messageId);

        return $payload === [] ? null : new AlimTalkMessageResult($payload);
    }

    /**
     * 예약 발송의 상태를 조회한다.
     *
     * @param  string  $requestId  발송 시 응답받은 요청 아이디
     * @return \Daworks\Sens\Responses\ReservationStatus
     *
     * @throws \Daworks\Sens\Exceptions\SensException
     */
    public function getReservationStatus(string $requestId): ReservationStatus
    {
        return new ReservationStatus($this->call(
            'GET',
            $this->servicePath() . '/reservations/' . $requestId . '/reserve-status'
        ));
    }

    /**
     * 예약 발송을 취소한다.
     *
     * @param  string  $requestId  발송 시 응답받은 요청 아이디
     * @return bool
     *
     * @throws \Daworks\Sens\Exceptions\SensException
     */
    public function cancelReservation(string $requestId): bool
    {
        $this->call('DELETE', $this->servicePath() . '/reservations/' . $requestId);

        return true;
    }

    /**
     * @param  array  $payload
     * @return \Daworks\Sens\Responses\MessageList
     */
    protected function newMessageList(array $payload)
    {
        return new MessageList($payload, function (array $message) {
            return new AlimTalkMessageResult($message);
        });
    }
}
