<?php

namespace Daworks\Sens\Contracts;

use Daworks\Sens\Responses\MessageList;
use Daworks\Sens\Responses\ReservationStatus;
use Daworks\Sens\Responses\SendResponse;

interface Sens
{
    /** @var string */
    public const X_NCP_APIGW_TIMESTAMP = 'x-ncp-apigw-timestamp';

    /** @var string */
    public const X_NCP_IAM_ACCESS_KEY = 'x-ncp-iam-access-key';

    /** @var string */
    public const X_NCP_APIGW_SIGNATURE_V2 = 'x-ncp-apigw-signature-v2';

    /**
     * 메시지를 발송하고 요청 아이디를 담은 응답을 돌려준다.
     *
     * 발송 API 는 비동기이므로, 응답은 "요청이 접수되었다"는 사실만 알려준다.
     * 실제 수신 여부는 응답의 requestId 로 조회해야 한다.
     *
     * @param  array  $params
     * @return \Daworks\Sens\Responses\SendResponse
     *
     * @throws \Daworks\Sens\Exceptions\SensException
     */
    public function send(array $params): SendResponse;

    /**
     * 요청 아이디로 해당 발송 요청에 속한 메시지 목록을 조회한다.
     *
     * @param  string  $requestId
     * @param  array  $filters
     * @return \Daworks\Sens\Responses\MessageList
     *
     * @throws \Daworks\Sens\Exceptions\SensException
     */
    public function findByRequestId(string $requestId, array $filters = []): MessageList;

    /**
     * 조건으로 메시지 발송 목록을 조회한다.
     *
     * @param  array  $filters
     * @return \Daworks\Sens\Responses\MessageList
     *
     * @throws \Daworks\Sens\Exceptions\SensException
     */
    public function findMessages(array $filters = []): MessageList;

    /**
     * 메시지 아이디로 개별 메시지의 발송 결과를 조회한다.
     *
     * @param  string  $messageId
     * @return \Daworks\Sens\Contracts\MessageResult|null
     *
     * @throws \Daworks\Sens\Exceptions\SensException
     */
    public function findMessage(string $messageId): ?MessageResult;

    /**
     * 예약 발송의 상태를 조회한다. 예약 아이디는 발송 시 받은 requestId 이다.
     *
     * @param  string  $requestId
     * @return \Daworks\Sens\Responses\ReservationStatus
     *
     * @throws \Daworks\Sens\Exceptions\SensException
     */
    public function getReservationStatus(string $requestId): ReservationStatus;

    /**
     * 예약 발송을 취소한다. 예약 아이디는 발송 시 받은 requestId 이다.
     *
     * @param  string  $requestId
     * @return bool
     *
     * @throws \Daworks\Sens\Exceptions\SensException
     */
    public function cancelReservation(string $requestId): bool;
}
