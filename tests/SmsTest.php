<?php

namespace Daworks\Sens\Tests;

use Daworks\Sens\Exceptions\SensException;
use Daworks\Sens\Responses\SmsMessageResult;
use Daworks\Sens\Responses\SmsSendResponse;
use Daworks\Sens\Sms\Sms;
use GuzzleHttp\Psr7\Response;

class SmsTest extends TestCase
{
    /**
     * @param  array  $responses
     * @return \Daworks\Sens\Sms\Sms
     */
    protected function sms(array $responses)
    {
        return (new Sms($this->config()))->setHttpClient($this->fakeClient($responses));
    }

    /**
     * 계정을 아직 발급받지 못했거나 퍼블리시한 설정에서 기본값을 지워 env() 가
     * null 을 돌려주는 경우가 흔하다. 그때 생성자에서 TypeError 로 죽으면
     * "설정이 비었다" 는 사유를 남길 자리조차 없이 앱이 부팅에 실패한다.
     */
    public function testConstructsWithMissingOrNullCredentials(): void
    {
        $this->assertInstanceOf(Sms::class, new Sms([]));

        $this->assertInstanceOf(Sms::class, new Sms([
            'service_id' => null,
            'access_key' => null,
            'secret_key' => null,
            'base_url' => null,
        ]));
    }

    /**
     * 타임아웃이 없으면 SENS 가 응답을 물고 있는 동안 호출한 쪽(큐 워커)이
     * 한없이 붙들린다. 기본 클라이언트에 상한이 실제로 실렸는지 확인한다.
     */
    public function testDefaultHttpClientCarriesTimeouts(): void
    {
        $client = function (array $config) {
            $sms = new Sms($config);

            $property = new \ReflectionProperty(\Daworks\Sens\Sens::class, 'http');

            return $property->getValue($sms);
        };

        $default = $client([]);
        $this->assertSame(10, $default->getConfig('timeout'));
        $this->assertSame(5, $default->getConfig('connect_timeout'));

        $tuned = $client(['http_timeout' => 20, 'http_connect_timeout' => 3]);
        $this->assertSame(20, $tuned->getConfig('timeout'));
        $this->assertSame(3, $tuned->getConfig('connect_timeout'));
    }

    public function testSendReturnsRequestId(): void
    {
        $sms = $this->sms([
            new Response(202, [], json_encode([
                'requestId' => 'RSLA-20251125093940535-IZJQgZEc',
                'requestTime' => '2025-11-25T09:39:40.535',
                'statusCode' => '202',
                'statusName' => 'success',
            ])),
        ]);

        $response = $sms->send(['type' => 'SMS', 'content' => '테스트']);

        $this->assertInstanceOf(SmsSendResponse::class, $response);
        $this->assertSame('RSLA-20251125093940535-IZJQgZEc', $response->requestId);
        $this->assertSame('2025-11-25T09:39:40.535', $response->requestTime);
        $this->assertTrue($response->isAccepted());
        $this->assertTrue($response->isSuccessful());
    }

    public function testSendPostsTheGivenPayload(): void
    {
        $sms = $this->sms([new Response(202, [], json_encode(['statusCode' => '202']))]);

        $sms->send(['type' => 'SMS', 'content' => '테스트']);

        $request = $this->lastRequest();

        $this->assertSame('POST', $request->getMethod());
        $this->assertSame(
            '/sms/v2/services/ncp:sms:kr:123456789:sens/messages',
            $request->getUri()->getPath()
        );
        $this->assertSame(
            ['type' => 'SMS', 'content' => '테스트'],
            json_decode((string) $request->getBody(), true)
        );
        $this->assertSignatureMatchesRequest($request);
    }

    public function testSendUploadsAttachmentsAndReplacesThemWithFileId(): void
    {
        // 첨부 파일이 있으면 업로드 요청이 먼저 나가고 그 다음 발송 요청이 나간다.
        $sms = $this->sms([
            new Response(200, [], json_encode([
                'fileId' => 'a136000074f7',
                'createTime' => '2025-11-25T10:12:47.520',
                'expireTime' => '2025-11-27T10:12:47.520',
            ])),
            new Response(202, [], json_encode(['requestId' => 'RSLA-1', 'statusCode' => '202'])),
        ]);

        $response = $sms->send([
            'type' => 'MMS',
            'files' => [['name' => 'invoice.jpg', 'body' => 'BASE64IMAGE']],
        ]);

        $this->assertCount(2, $this->history);

        $upload = $this->history[0]['request'];

        $this->assertSame('POST', $upload->getMethod());
        $this->assertSame(
            '/sms/v2/services/ncp:sms:kr:123456789:sens/files',
            $upload->getUri()->getPath()
        );
        $this->assertSame(
            ['fileName' => 'invoice.jpg', 'fileBody' => 'BASE64IMAGE'],
            json_decode((string) $upload->getBody(), true)
        );
        $this->assertSignatureMatchesRequest($upload);

        // 발송 요청에는 파일 아이디만 남는다.
        $payload = json_decode((string) $this->history[1]['request']->getBody(), true);

        $this->assertSame([['fileId' => 'a136000074f7']], $payload['files']);
        $this->assertSame('RSLA-1', $response->requestId);
    }

    public function testSendDoesNotReuploadAnAlreadyUploadedAttachment(): void
    {
        // 응답을 하나만 준비한다. 다시 업로드하면 큐가 비어 실패한다.
        $sms = $this->sms([
            new Response(202, [], json_encode(['requestId' => 'RSLA-1', 'statusCode' => '202'])),
        ]);

        $sms->send([
            'type' => 'MMS',
            'files' => [['fileId' => 'a136000074f7']],
        ]);

        $this->assertCount(1, $this->history);

        $payload = json_decode((string) $this->lastRequest()->getBody(), true);

        $this->assertSame([['fileId' => 'a136000074f7']], $payload['files']);
    }

    public function testUploadAttachmentStripsTheDataUriPrefix(): void
    {
        $sms = $this->sms([new Response(200, [], json_encode(['fileId' => 'a136000074f7']))]);

        $attachment = $sms->uploadAttachment('invoice.jpg', 'data:image/jpeg;base64,BASE64IMAGE');

        $this->assertSame('a136000074f7', $attachment->fileId);
        $this->assertSame(
            ['fileName' => 'invoice.jpg', 'fileBody' => 'BASE64IMAGE'],
            json_decode((string) $this->lastRequest()->getBody(), true)
        );
    }

    public function testFindByRequestIdSignsTheQueryString(): void
    {
        $sms = $this->sms([
            new Response(200, [], json_encode([
                'statusCode' => '202',
                'statusName' => 'success',
                'messages' => [
                    [
                        'requestId' => 'RSMA-1',
                        'messageId' => 'f574d3f0-0000-0000-0000-daa31f50eaf5',
                        'to' => '01011112222',
                        'status' => 'COMPLETED',
                        'statusCode' => '0',
                        'statusName' => 'success',
                    ],
                ],
                'hasMore' => false,
            ])),
        ]);

        $list = $sms->findByRequestId('RSMA-1');

        $request = $this->lastRequest();

        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('requestId=RSMA-1', $request->getUri()->getQuery());

        // 서명이 쿼리 스트링을 포함하지 않으면 SENS 는 401 을 응답한다.
        $this->assertSignatureMatchesRequest($request);

        $this->assertCount(1, $list);
        $this->assertSame(['f574d3f0-0000-0000-0000-daa31f50eaf5'], $list->messageIds());
        $this->assertTrue($list->isSuccessful());
        $this->assertSame([], $list->failed());
    }

    public function testFindMessagesEncodesSpacesConsistentlyWithTheSignature(): void
    {
        $sms = $this->sms([new Response(200, [], json_encode(['statusCode' => '202']))]);

        $sms->findMessages([
            'requestStartTime' => '2025-11-25 09:10:00',
            'requestEndTime' => '2025-11-25 10:30:00',
            'pageSize' => 100,
        ]);

        $request = $this->lastRequest();

        $this->assertStringContainsString('requestStartTime=2025-11-25%2009%3A10%3A00', (string) $request->getUri());
        $this->assertSignatureMatchesRequest($request);
    }

    public function testFindMessagesDropsEmptyFilters(): void
    {
        $sms = $this->sms([new Response(200, [], json_encode(['statusCode' => '202']))]);

        $sms->findMessages(['requestId' => 'RSMA-1', 'to' => null, 'status' => '']);

        $this->assertSame('requestId=RSMA-1', $this->lastRequest()->getUri()->getQuery());
    }

    public function testFindMessageUnwrapsTheMessageEnvelope(): void
    {
        $sms = $this->sms([
            new Response(200, [], json_encode([
                'statusCode' => '200',
                'statusName' => 'success',
                'messages' => [
                    [
                        'requestId' => 'RSMA-1',
                        'messageId' => 'f574d3f0',
                        'to' => '01011112222',
                        'status' => 'COMPLETED',
                        'statusCode' => '3018',
                        'statusName' => 'fail',
                        'statusMessage' => '발신번호 변작 방지 서비스에 가입된 번호',
                    ],
                ],
            ])),
        ]);

        $result = $sms->findMessage('f574d3f0');

        $this->assertInstanceOf(SmsMessageResult::class, $result);
        $this->assertSame(
            '/sms/v2/services/ncp:sms:kr:123456789:sens/messages/f574d3f0',
            $this->lastRequest()->getUri()->getPath()
        );
        $this->assertFalse($result->isSuccessful());
        $this->assertFalse($result->isPending());
        $this->assertSame('3018', $result->getStatusCode());
        $this->assertSame('발신번호 변작 방지 서비스에 가입된 번호', $result->getStatusMessage());
    }

    public function testEmptyListIsPendingRatherThanFailed(): void
    {
        // 예약 발송이거나 발송 직후에는 SENS 가 messages 를 비워서 응답한다.
        $sms = $this->sms([
            new Response(200, [], json_encode(['statusCode' => '202', 'statusName' => 'reserved'])),
        ]);

        $list = $sms->findByRequestId('RSSA-1');

        $this->assertTrue($list->isEmpty());
        $this->assertTrue($list->isPending());
        $this->assertFalse($list->isSuccessful());
        $this->assertSame([], $list->failed());
    }

    public function testFindMessageReturnsNullWhenThereIsNoResult(): void
    {
        $sms = $this->sms([new Response(200, [], json_encode(['statusCode' => '200', 'messages' => []]))]);

        $this->assertNull($sms->findMessage('unknown'));
    }

    public function testPendingMessageIsNeitherSuccessfulNorFailed(): void
    {
        $result = new SmsMessageResult([
            'messageId' => 'f574d3f0',
            'status' => 'PROCESSING',
        ]);

        $this->assertTrue($result->isPending());
        $this->assertFalse($result->isSuccessful());
    }

    public function testReservationStatusIsFetchedByRequestId(): void
    {
        $sms = $this->sms([
            new Response(200, [], json_encode([
                'reserveId' => 'RSSA-1',
                'reserveTimeZone' => 'Asia/Seoul',
                'reserveTime' => '2025-11-25T13:19:00+09:00',
                'reserveStatus' => 'READY',
            ])),
        ]);

        $status = $sms->getReservationStatus('RSSA-1');

        $this->assertSame(
            '/sms/v2/services/ncp:sms:kr:123456789:sens/reservations/RSSA-1/reserve-status',
            $this->lastRequest()->getUri()->getPath()
        );
        $this->assertTrue($status->isPending());
        $this->assertFalse($status->isCanceled());
    }

    public function testCancelReservationSendsDeleteAndAcceptsEmptyBody(): void
    {
        $sms = $this->sms([new Response(204)]);

        $this->assertTrue($sms->cancelReservation('RSSA-1'));

        $request = $this->lastRequest();

        $this->assertSame('DELETE', $request->getMethod());
        $this->assertSame(
            '/sms/v2/services/ncp:sms:kr:123456789:sens/reservations/RSSA-1',
            $request->getUri()->getPath()
        );
    }

    public function testApiErrorCarriesTheStatusCodeAndBody(): void
    {
        $sms = $this->sms([
            new Response(401, [], json_encode([
                'errorCode' => '210',
                'errorMessage' => 'Authentication Failed',
            ])),
        ]);

        try {
            $sms->send(['type' => 'SMS']);
            $this->fail('SensException was not thrown.');
        } catch (SensException $e) {
            $this->assertSame(401, $e->getStatusCode());
            $this->assertSame('210', $e->getErrorCode());
            $this->assertSame('[210] Authentication Failed', $e->getMessage());
            $this->assertSame(
                ['errorCode' => '210', 'errorMessage' => 'Authentication Failed'],
                $e->getResponseBody()
            );
        }
    }

    public function testMissingTokensThrowBeforeSendingAnyRequest(): void
    {
        $sms = (new Sms($this->config(['access_key' => ''])))
            ->setHttpClient($this->fakeClient([]));

        $this->expectException(SensException::class);

        try {
            $sms->send(['type' => 'SMS']);
        } finally {
            $this->assertEmpty($this->history);
        }
    }
}
