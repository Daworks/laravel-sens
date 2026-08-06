<?php

namespace Daworks\Sens\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** 테스트에 사용하는 액세스 키. */
    public const ACCESS_KEY = 'test-access-key';

    /** 테스트에 사용하는 시크릿 키. */
    public const SECRET_KEY = 'test-secret-key';

    /** @var array 실제로 전송된 요청 기록 */
    protected $history = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->history = [];
    }

    /**
     * 주어진 응답을 순서대로 돌려주는 HTTP 클라이언트를 만든다.
     *
     * @param  \Psr\Http\Message\ResponseInterface[]|\Throwable[]  $responses
     * @return \GuzzleHttp\Client
     */
    protected function fakeClient(array $responses)
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($this->history));

        return new Client(['handler' => $stack]);
    }

    /**
     * @param  array  $overrides
     * @return array
     */
    protected function config(array $overrides = [])
    {
        return array_merge([
            'service_id' => 'ncp:sms:kr:123456789:sens',
            'alimtalk_service_id' => 'ncp:kkobizmsg:kr:123456789:sens',
            'plus_friend_id' => '@daworks',
            'access_key' => self::ACCESS_KEY,
            'secret_key' => self::SECRET_KEY,
        ], $overrides);
    }

    /**
     * 마지막으로 전송된 요청.
     *
     * @return \GuzzleHttp\Psr7\Request
     */
    protected function lastRequest()
    {
        $this->assertNotEmpty($this->history, 'No HTTP request was sent.');

        return $this->history[count($this->history) - 1]['request'];
    }

    /**
     * 요청에 실제로 사용된 경로. 쿼리 스트링을 포함한다.
     *
     * @param  \GuzzleHttp\Psr7\Request  $request
     * @return string
     */
    protected function requestTarget(Request $request)
    {
        $uri = $request->getUri();
        $query = $uri->getQuery();

        return $uri->getPath() . ($query === '' ? '' : '?' . $query);
    }

    /**
     * NCP API Gateway v2 서명이 실제 요청과 일치하는지 검증한다.
     *
     * @param  \GuzzleHttp\Psr7\Request  $request
     * @return void
     */
    protected function assertSignatureMatchesRequest(Request $request)
    {
        $timestamp = $request->getHeaderLine('x-ncp-apigw-timestamp');

        $message = implode("\n", [
            $request->getMethod() . ' ' . $this->requestTarget($request),
            $timestamp,
            self::ACCESS_KEY,
        ]);

        $this->assertSame(
            base64_encode(hex2bin(hash_hmac('sha256', $message, self::SECRET_KEY))),
            $request->getHeaderLine('x-ncp-apigw-signature-v2'),
            'The signature does not cover the request target that was actually sent.'
        );
    }
}
