<?php

namespace Daworks\Sens\Sms;

use Daworks\Sens\Contracts\SensMessage;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

class SmsMessage implements SensMessage
{
    /** @var string */
    public $type = 'SMS';

    /** @var string */
    public $contentType = 'COMM';

    /** @var int */
    public $countryCode = 82;

    /** @var string */
    public $from;

    /** @var string */
    public $subject = null;

    /** @var string */
    public $content;

    /** @var array */
    public $messages = [];

    /** @var array */
    public $files = [];

    /** @var string|null 예약 일시 (YYYY-MM-DD HH:mm) */
    public $reserveTime;

    /** @var string|null 예약 타임존 */
    public $reserveTimeZone;

    /**
     * Create a new SensSmsMessage instance.
     *
     * 발신 번호는 기존 설정 위치인 services.php 를 먼저 확인하고,
     * 값이 없으면 이 패키지의 설정을 사용한다.
     *
     * @return void
     */
    public function __construct()
    {
        $this->from = config('services.sens.services.sms.sender')
            ?: config('laravel-sens.sms_from');
    }

    /**
     * Set SMS Type (ex: SMS, LMS)
     *
     * @param  string  $type
     * @return $this
     */
    public function type(string $type)
    {
        $this->type = strtoupper($type);

        return $this;
    }

    /**
     * Set SMS Content Type (ex: COMM / AD)
     *
     * @param  string  $contentType
     * @return $this
     */
    public function contentType(string $contentType)
    {
        $this->contentType = strtoupper($contentType);

        return $this;
    }

    /**
     * Set Country Code.
     *
     * @param  int  $countryCode
     * @return $this
     */
    public function countryCode(int $countryCode)
    {
        $this->countryCode = $countryCode;

        return $this;
    }

    /**
     * Set Sender's tel number.
     *
     * @param  string  $from
     * @return $this
     */
    public function from(string $from)
    {
        $this->from = str_replace('-', '', $from);

        return $this;
    }

    /**
     * Set title only for LMS.
     *
     * @param  string  $subject
     * @return $this
     */
    public function subject(string $subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Set SMS Contents. (SMS: 80byte, LMS: 2000byte)
     *
     * @param  string  $content
     * @return $this
     */
    public function content(string $content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Set Recipient's number.
     *
     * @param  string  $to
     * @return $this
     */
    public function to(string $to)
    {
        array_push($this->messages, [
            'to' => str_replace('-', '', $to),
        ]);

        return $this;
    }

    /**
     * 예약 발송 일시를 설정한다.
     *
     * 예약 발송의 상태 조회와 취소는 발송 응답의 requestId 로 할 수 있다.
     *
     * @param  string  $reserveTime  YYYY-MM-DD HH:mm 형식
     * @param  string  $reserveTimeZone
     * @return $this
     */
    public function setReserved(string $reserveTime, string $reserveTimeZone = 'Asia/Seoul')
    {
        $this->reserveTime = $reserveTime;
        $this->reserveTimeZone = $reserveTimeZone;

        return $this;
    }

    /**
     * 이미 업로드한 첨부 파일을 파일 아이디로 추가한다.
     *
     * 같은 이미지를 여러 번 발송할 때 매번 업로드하지 않으려면
     * Sens::sms()->uploadAttachment() 로 받은 파일 아이디를 재사용한다.
     *
     * @param  string  $fileId
     * @return $this
     */
    public function fileId(string $fileId)
    {
        array_push($this->files, [
            'fileId' => $fileId,
        ]);

        return $this;
    }

    /**
     * Add a new file into files for MMS message.
     *
     * 여기서는 파일을 보관만 하고, 실제 업로드는 발송 직전에 이루어진다.
     * jpg, jpeg 이미지만 사용할 수 있다.
     *
     * @param  string  $name
     * @param  mixed  $file
     * @return $this
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function file(string $name, $file)
    {
        $body = null;

        if ($file instanceof \Illuminate\Http\UploadedFile) {
            /** @var \Illuminate\Http\UploadedFile $file */
            $body = base64_encode($file->get());
        } elseif (is_string($file)) {
            $body = base64_encode(file_get_contents($file));
        } else {
            throw new FileNotFoundException();
        }

        array_push($this->files, [
            'name' => $name,
            'body' => $body,
        ]);

        return $this;
    }

    /**
     * Serialize to Array.
     *
     * @return array
     */
    public function toArray()
    {
        $resource = [
            'type' => $this->type,
            'contentType' => $this->contentType,
            'countryCode' => strval($this->countryCode),
            'from' => $this->from,
            'subject' => $this->subject,
            'content' => $this->content,
            'messages' => $this->messages,
        ];

        if (! empty($this->files)) {
            $resource['files'] = $this->files;
        }

        if ($this->reserveTime) {
            $resource['reserveTime'] = $this->reserveTime;
            $resource['reserveTimeZone'] = $this->reserveTimeZone;
        }

        return $resource;
    }
}
