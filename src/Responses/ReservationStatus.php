<?php

namespace Daworks\Sens\Responses;

/**
 * 메시지 예약 상태 조회 결과.
 *
 * 예약 요청 아이디(reserveId)는 발송 시 응답받은 requestId 와 동일하다.
 *
 * @see https://api.ncloud-docs.com/docs/sens-sms-reservation-status-get
 * @see https://api.ncloud-docs.com/docs/sens-alimtalk-reservation-status-get
 */
class ReservationStatus extends Response
{
    /** 대기중 */
    public const READY = 'READY';

    /** 처리중 */
    public const PROCESSING = 'PROCESSING';

    /** 취소됨 */
    public const CANCELED = 'CANCELED';

    /** 실패 */
    public const FAIL = 'FAIL';

    /** 완료 */
    public const DONE = 'DONE';

    /** 실패 (시간 초과) */
    public const STALE = 'STALE';

    /** 실패 (서비스 없음) */
    public const SKIP = 'SKIP';

    /** @var string|null 예약 요청 아이디 */
    public $reserveId;

    /** @var string|null 예약 일시 */
    public $reserveTime;

    /** @var string|null 예약 타임존 */
    public $reserveTimeZone;

    /** @var string|null 예약 상태 */
    public $reserveStatus;

    /**
     * @param  array  $payload
     */
    public function __construct(array $payload)
    {
        parent::__construct($payload);

        $this->reserveId = $this->get('reserveId');
        $this->reserveTime = $this->get('reserveTime');
        $this->reserveTimeZone = $this->get('reserveTimeZone');
        $this->reserveStatus = $this->get('reserveStatus');
    }

    /**
     * 아직 발송되지 않고 대기 중인지 여부. 이 상태에서만 예약을 취소할 수 있다.
     *
     * @return bool
     */
    public function isPending()
    {
        return in_array($this->reserveStatus, [self::READY, self::PROCESSING], true);
    }

    /**
     * 예약이 취소되었는지 여부.
     *
     * @return bool
     */
    public function isCanceled()
    {
        return $this->reserveStatus === self::CANCELED;
    }

    /**
     * 예약 발송이 완료되었는지 여부.
     *
     * @return bool
     */
    public function isCompleted()
    {
        return $this->reserveStatus === self::DONE;
    }

    /**
     * 예약 발송이 실패했는지 여부.
     *
     * @return bool
     */
    public function isFailed()
    {
        return in_array($this->reserveStatus, [self::FAIL, self::STALE, self::SKIP], true);
    }
}
