<?php

namespace Daworks\Sens\Responses;

/**
 * MMS 첨부 파일 업로드 결과.
 *
 * 업로드된 파일은 6일간 보관되며, 같은 이름과 크기의 파일을 다시 업로드하면
 * SENS 가 기존 파일을 재사용한다.
 *
 * @see https://api.ncloud-docs.com/docs/sens-sms-attachment-create
 */
class Attachment extends Response
{
    /** @var string|null 첨부 파일 아이디 */
    public $fileId;

    /** @var string|null 생성 일시 */
    public $createTime;

    /** @var string|null 만료 일시 */
    public $expireTime;

    /**
     * @param  array  $payload
     */
    public function __construct(array $payload)
    {
        parent::__construct($payload);

        $this->fileId = $this->get('fileId');
        $this->createTime = $this->get('createTime');
        $this->expireTime = $this->get('expireTime');
    }
}
