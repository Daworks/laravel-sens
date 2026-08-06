<?php

namespace Daworks\Sens\Exceptions;

use Exception;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class SensException extends Exception
{
    /** @var int|null HTTP 상태 코드 */
    protected $statusCode;

    /** @var array SENS 가 응답한 오류 본문 */
    protected $responseBody = [];

    /** @var string|null 요청 아이디 */
    protected $requestId;

    /**
     * Exception for Invalid NCLOUD SENS Tokens.
     *
     * @param  string  $message
     * @return \Daworks\Sens\Exceptions\SensException
     */
    public static function InvalidNCPTokens($message)
    {
        return new static($message);
    }

    /**
     * SENS API 가 오류를 응답했을 때의 예외.
     *
     * Guzzle 은 4xx/5xx 에서 예외를 던지므로, SENS 가 내려준 errorCode /
     * errorMessage 는 이 경로에서만 확인할 수 있다.
     *
     * @param  \GuzzleHttp\Exception\RequestException  $exception
     * @return \Daworks\Sens\Exceptions\SensException
     */
    public static function fromRequestException(RequestException $exception)
    {
        $response = $exception->getResponse();
        $body = static::decodeResponse($response);

        $instance = new static(
            static::resolveMessage($body, $exception->getMessage()),
            $response ? $response->getStatusCode() : 0,
            $exception
        );

        $instance->statusCode = $response ? $response->getStatusCode() : null;
        $instance->responseBody = $body;
        $instance->requestId = $body['requestId'] ?? null;

        return $instance;
    }

    /**
     * 네트워크 오류 등 응답을 받지 못한 경우의 예외.
     *
     * @param  \Throwable  $exception
     * @return \Daworks\Sens\Exceptions\SensException
     */
    public static function fromThrowable(Throwable $exception)
    {
        return new static($exception->getMessage(), (int) $exception->getCode(), $exception);
    }

    /**
     * SENS 응답을 해석할 수 없을 때의 예외.
     *
     * @param  string  $body
     * @return \Daworks\Sens\Exceptions\SensException
     */
    public static function malformedResponse($body)
    {
        $instance = new static('Unable to decode the SENS API response.');
        $instance->responseBody = ['raw' => $body];

        return $instance;
    }

    /**
     * HTTP 상태 코드.
     *
     * @return int|null
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * SENS 가 응답한 오류 본문.
     *
     * @return array
     */
    public function getResponseBody()
    {
        return $this->responseBody;
    }

    /**
     * 요청 아이디. 오류 응답에 포함된 경우에만 존재한다.
     *
     * @return string|null
     */
    public function getRequestId()
    {
        return $this->requestId;
    }

    /**
     * SENS 오류 코드.
     *
     * @return string|null
     */
    public function getErrorCode()
    {
        return $this->responseBody['errorCode'] ?? null;
    }

    /**
     * @param  \Psr\Http\Message\ResponseInterface|null  $response
     * @return array
     */
    protected static function decodeResponse(?ResponseInterface $response)
    {
        if ($response === null) {
            return [];
        }

        $decoded = json_decode((string) $response->getBody(), true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param  array  $body
     * @param  string  $fallback
     * @return string
     */
    protected static function resolveMessage(array $body, $fallback)
    {
        $message = $body['errorMessage'] ?? $body['message'] ?? null;
        $code = $body['errorCode'] ?? null;

        if ($message === null) {
            return $fallback;
        }

        return $code === null ? $message : sprintf('[%s] %s', $code, $message);
    }
}
