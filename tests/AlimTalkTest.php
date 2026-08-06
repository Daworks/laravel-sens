<?php

namespace Daworks\Sens\Tests;

use Daworks\Sens\AlimTalk\AlimTalk;
use Daworks\Sens\Responses\AlimTalkMessageResult;
use Daworks\Sens\Responses\AlimTalkSendResponse;
use GuzzleHttp\Psr7\Response;

class AlimTalkTest extends TestCase
{
    /**
     * @param  array  $responses
     * @return \Daworks\Sens\AlimTalk\AlimTalk
     */
    protected function alimTalk(array $responses)
    {
        return (new AlimTalk($this->config()))->setHttpClient($this->fakeClient($responses));
    }

    /**
     * 알림톡은 SMS 와 다른 서비스 아이디를 쓴다. 알림톡을 쓰지 않아 설정을
     * 비워 둔 경우에도 SMS 와 마찬가지로 객체는 만들어져야 한다.
     */
    public function testConstructsWithMissingOrNullCredentials(): void
    {
        $this->assertInstanceOf(AlimTalk::class, new AlimTalk([]));

        $this->assertInstanceOf(AlimTalk::class, new AlimTalk([
            'alimtalk_service_id' => null,
            'access_key' => null,
            'secret_key' => null,
            'base_url' => null,
        ]));
    }

    public function testSendReturnsRequestIdAndPerRecipientResults(): void
    {
        $alimTalk = $this->alimTalk([
            new Response(202, [], json_encode([
                'requestId' => 'RBAA-20251125153920899-zgrtzVEW',
                'requestTime' => '2025-11-25T15:39:20.899',
                'statusCode' => '202',
                'statusName' => 'processing',
                'messages' => [
                    [
                        'messageId' => 'aa724ca6-0000-0000-0000-66dfc1a700e7',
                        'to' => '01011112222',
                        'requestStatusCode' => 'A000',
                        'requestStatusName' => 'success',
                        'requestStatusDesc' => '성공',
                        'useSmsFailover' => true,
                    ],
                ],
            ])),
        ]);

        $response = $alimTalk->send(['plusFriendId' => '@daworks']);

        $this->assertInstanceOf(AlimTalkSendResponse::class, $response);
        $this->assertSame('RBAA-20251125153920899-zgrtzVEW', $response->requestId);
        $this->assertTrue($response->isSuccessful());
        $this->assertSame(['aa724ca6-0000-0000-0000-66dfc1a700e7'], $response->messageIds());

        // 개별 메시지는 응답 최상위의 requestId 를 물려받는다.
        $this->assertSame('RBAA-20251125153920899-zgrtzVEW', $response->messages()[0]->getRequestId());

        $this->assertSignatureMatchesRequest($this->lastRequest());
    }

    public function testAcceptedResponseWithFailedRecipientIsNotSuccessful(): void
    {
        $alimTalk = $this->alimTalk([
            new Response(202, [], json_encode([
                'requestId' => 'RBAA-1',
                'statusCode' => '202',
                'statusName' => 'processing',
                'messages' => [
                    [
                        'messageId' => 'ok',
                        'to' => '01011112222',
                        'requestStatusCode' => 'A000',
                        'requestStatusName' => 'success',
                    ],
                    [
                        'messageId' => null,
                        'to' => '01033334444',
                        'requestStatusCode' => 'A003',
                        'requestStatusName' => 'fail',
                        'requestStatusDesc' => '템플릿과 내용이 일치하지 않음',
                    ],
                ],
            ])),
        ]);

        $response = $alimTalk->send(['plusFriendId' => '@daworks']);

        $this->assertTrue($response->isAccepted());
        $this->assertFalse($response->isSuccessful());

        $failed = $response->failedMessages();

        $this->assertCount(1, $failed);
        $this->assertSame('01033334444', $failed[0]->getTo());
        $this->assertSame('템플릿과 내용이 일치하지 않음', $failed[0]->getStatusMessage());
    }

    public function testFindMessageReadsTheFlatResponseBody(): void
    {
        $alimTalk = $this->alimTalk([
            new Response(200, [], json_encode([
                'requestId' => 'RBAA-1',
                'messageId' => 'aa724ca6',
                'to' => '01011112222',
                'templateCode' => 'temp001',
                'requestStatusCode' => 'A000',
                'requestStatusName' => 'success',
                'messageStatusCode' => '0000',
                'messageStatusName' => 'success',
                'messageStatusDesc' => '정상 발송',
                'useSmsFailover' => true,
            ])),
        ]);

        $result = $alimTalk->findMessage('aa724ca6');

        $this->assertInstanceOf(AlimTalkMessageResult::class, $result);
        $this->assertSame(
            '/alimtalk/v2/services/ncp:kkobizmsg:kr:123456789:sens/messages/aa724ca6',
            $this->lastRequest()->getUri()->getPath()
        );
        $this->assertTrue($result->isSuccessful());
        $this->assertFalse($result->isPending());
        $this->assertSame('0000', $result->getStatusCode());
    }

    public function testMessageWaitingForDeliveryIsPending(): void
    {
        $result = new AlimTalkMessageResult([
            'messageId' => 'aa724ca6',
            'requestStatusCode' => 'A000',
            'messageStatusName' => 'processing',
        ]);

        $this->assertTrue($result->isPending());
        $this->assertFalse($result->isSuccessful());
    }

    public function testFailedAlimTalkIsDeliveredWhenSmsFailoverSucceeded(): void
    {
        $result = new AlimTalkMessageResult([
            'messageId' => 'aa724ca6',
            'requestStatusCode' => 'A000',
            'messageStatusCode' => '3000',
            'messageStatusName' => 'fail',
            'useSmsFailover' => true,
            'failover' => [
                'requestId' => 'RSMA-1',
                'messageId' => 'sms-1',
                'messageStatus' => 'COMPLETED',
                'messageStatusName' => 'success',
            ],
        ]);

        $this->assertFalse($result->isSuccessful());
        $this->assertTrue($result->hasFailover());
        $this->assertTrue($result->failoverSucceeded());
        $this->assertTrue($result->isDelivered());
    }

    public function testFindMessagesFallsBackToTheConfiguredChannelId(): void
    {
        $alimTalk = $this->alimTalk([new Response(200, [], json_encode(['statusCode' => '202']))]);

        $alimTalk->findMessages(['to' => '01011112222']);

        $request = $this->lastRequest();

        // 채널 아이디의 '@' 는 인코딩하지 않고 그대로 전달한다.
        $this->assertStringContainsString('plusFriendId=@daworks', (string) $request->getUri());
        $this->assertSignatureMatchesRequest($request);
    }

    public function testFindByRequestIdDoesNotRequireTheChannelId(): void
    {
        $alimTalk = $this->alimTalk([new Response(200, [], json_encode(['statusCode' => '202']))]);

        $alimTalk->findByRequestId('RBAA-1');

        $this->assertSame('requestId=RBAA-1', $this->lastRequest()->getUri()->getQuery());
    }
}
