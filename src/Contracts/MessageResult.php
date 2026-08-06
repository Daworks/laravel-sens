<?php

namespace Daworks\Sens\Contracts;

/**
 * 채널(SMS / 알림톡)에 관계없이 개별 메시지의 발송 결과를 동일한 방법으로
 * 확인하기 위한 계약.
 *
 * SENS 는 채널마다 상태 필드의 이름이 다르다.
 * (SMS: status/statusCode/statusName, 알림톡: messageStatusCode/messageStatusName ...)
 * 이 계약은 그 차이를 흡수한다.
 */
interface MessageResult
{
    /**
     * 메시지 아이디. 개별 메시지의 발송 결과를 조회할 때 사용한다.
     *
     * @return string|null
     */
    public function getMessageId();

    /**
     * 요청 아이디. 하나의 발송 요청에 포함된 모든 메시지가 공유한다.
     *
     * @return string|null
     */
    public function getRequestId();

    /**
     * 수신 번호.
     *
     * @return string|null
     */
    public function getTo();

    /**
     * 수신자에게 정상적으로 전달되었는지 여부.
     *
     * @return bool
     */
    public function isSuccessful();

    /**
     * 아직 발송 처리가 끝나지 않았는지 여부.
     * 처리 중인 메시지는 실패로 단정할 수 없다.
     *
     * @return bool
     */
    public function isPending();

    /**
     * 수신 결과 코드.
     *
     * @return string|null
     */
    public function getStatusCode();

    /**
     * 수신 상태. (success / processing / fail)
     *
     * @return string|null
     */
    public function getStatusName();

    /**
     * 수신 상태에 대한 설명. 실패 사유를 담고 있다.
     *
     * @return string|null
     */
    public function getStatusMessage();

    /**
     * SENS 가 응답한 원본 배열.
     *
     * @return array
     */
    public function toArray();
}
