<?php

namespace Daworks\Sens\Sms;

use Daworks\Sens\Contracts\MessageResult;
use Daworks\Sens\Responses\Attachment;
use Daworks\Sens\Responses\MessageList;
use Daworks\Sens\Responses\ReservationStatus;
use Daworks\Sens\Responses\SendResponse;
use Daworks\Sens\Responses\SmsMessageResult;
use Daworks\Sens\Responses\SmsSendResponse;
use Daworks\Sens\Sens;

/**
 * SENS SMS/LMS/MMS API 클라이언트.
 *
 * @see https://api.ncloud-docs.com/docs/ai-application-service-sens-smsv2
 */
class Sms extends Sens
{
    /**
     * @return string
     */
    protected function servicePath()
    {
        return '/sms/v2/services/' . $this->getServiceId();
    }

    /**
     * 메시지를 발송한다.
     *
     * MMS 첨부 파일은 발송 전에 업로드해서 파일 아이디를 받아야 한다.
     * 파일 내용이 그대로 담겨 있으면 이 메서드가 먼저 업로드한 뒤 발송한다.
     *
     * @param  array  $params
     * @return \Daworks\Sens\Responses\SmsSendResponse
     *
     * @throws \Daworks\Sens\Exceptions\SensException
     */
    public function send(array $params): SendResponse
    {
        return new SmsSendResponse(
            $this->call('POST', $this->servicePath() . '/messages', $this->resolveAttachments($params))
        );
    }

    /**
     * MMS 발송에 사용할 첨부 파일을 업로드한다.
     *
     * jpg, jpeg 이미지만 업로드할 수 있으며 최대 300KB, 해상도 1500x1440 까지 허용된다.
     *
     * @param  string  $fileName  jpg 또는 jpeg 확장자를 가진 파일 이름 (0~40자)
     * @param  string  $fileBody  Base64 로 인코딩한 이미지
     * @return \Daworks\Sens\Responses\Attachment
     *
     * @throws \Daworks\Sens\Exceptions\SensException
     */
    public function uploadAttachment(string $fileName, string $fileBody)
    {
        return new Attachment($this->call('POST', $this->servicePath() . '/files', [
            'fileName' => $fileName,
            // data URI 접두어가 붙어 있으면 SENS 가 이미지를 해석하지 못한다.
            'fileBody' => preg_replace('/^data:[^;]*;base64,/', '', $fileBody),
        ]));
    }

    /**
     * 첨부 파일을 SENS 가 요구하는 파일 아이디 형태로 바꾼다.
     *
     * 이미 파일 아이디를 가지고 있으면 다시 업로드하지 않는다.
     *
     * @param  array  $params
     * @return array
     *
     * @throws \Daworks\Sens\Exceptions\SensException
     */
    protected function resolveAttachments(array $params)
    {
        if (empty($params['files'])) {
            return $params;
        }

        $params['files'] = array_map(function (array $file) {
            if (! empty($file['fileId'])) {
                return ['fileId' => $file['fileId']];
            }

            if (! isset($file['body'])) {
                return $file;
            }

            return [
                'fileId' => $this->uploadAttachment($file['name'] ?? '', $file['body'])->fileId,
            ];
        }, $params['files']);

        return $params;
    }

    /**
     * 요청 아이디로 해당 발송 요청에 속한 메시지 목록을 조회한다.
     *
     * SMS 발송 응답에는 messageId 가 없으므로, 개별 메시지를 추적하려면
     * 이 조회를 거쳐 messageId 를 얻어야 한다.
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
     * 조건으로 메시지 발송 목록을 조회한다. 최근 90일 이내만 조회할 수 있다.
     *
     * requestId, requestStartTime + requestEndTime,
     * completeStartTime + completeEndTime 중 하나는 반드시 지정해야 한다.
     *
     * @param  array  $filters
     * @return \Daworks\Sens\Responses\MessageList
     *
     * @throws \Daworks\Sens\Exceptions\SensException
     */
    public function findMessages(array $filters = []): MessageList
    {
        return $this->newMessageList(
            $this->call('GET', $this->servicePath() . '/messages', [], $filters)
        );
    }

    /**
     * 메시지 아이디로 개별 메시지의 발송 결과를 조회한다.
     *
     * @param  string  $messageId
     * @return \Daworks\Sens\Responses\SmsMessageResult|null
     *
     * @throws \Daworks\Sens\Exceptions\SensException
     */
    public function findMessage(string $messageId): ?MessageResult
    {
        $payload = $this->call('GET', $this->servicePath() . '/messages/' . $messageId);

        $message = $payload['messages'][0] ?? null;

        return $message === null ? null : new SmsMessageResult($message);
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
            return new SmsMessageResult($message);
        });
    }
}
