<?php

namespace Daworks\Sens;

use Daworks\Sens\Contracts\Sens as SensContract;
use Daworks\Sens\Exceptions\SensException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Throwable;

abstract class Sens implements SensContract
{
    /** SENS API 기본 호스트. */
    public const BASE_URL = 'https://sens.apigw.ntruss.com';

    /** @var \GuzzleHttp\Client */
    protected $http;

    /** @var string */
    private $serviceId;

    /** @var string */
    private $accessKey;

    /** @var string */
    private $secretKey;

    /** @var string */
    protected $baseUrl;

    /** @var array */
    protected $config = [];

    /** @var array */
    private $headers = [];

    /**
     * Create a new SENS instance.
     *
     * @param  array  $config
     */
    public function __construct(array $config)
    {
        $this->httpClient();

        /*
         * 빠졌거나 null 인 값은 빈 문자열로 받는다.
         *
         * 계정을 아직 발급받지 못했거나, 퍼블리시한 설정 파일에서 기본값을 지워
         * env() 가 null 을 돌려주는 경우가 흔하다. 그대로 넘기면 setter 의 string
         * 타입에서 TypeError 가 나 컨테이너가 이 객체를 만들지도 못한다 — 그러면
         * "설정이 비었다" 는 사유를 남길 자리조차 없이 죽는다. 값이 비어 있다는
         * 판단은 실제로 발송을 시도하는 쪽이 해야 한다.
         */
        $this->setServiceId((string) ($config['service_id'] ?? ''))
            ->setAccessKey((string) ($config['access_key'] ?? ''))
            ->setSecretKey((string) ($config['secret_key'] ?? ''));

        $this->baseUrl = rtrim((string) ($config['base_url'] ?? '') ?: self::BASE_URL, '/');

        $this->config = $config;
    }

    /**
     * 이 서비스의 API 경로 접두사. (예: /sms/v2/services/{serviceId})
     *
     * @return string
     */
    abstract protected function servicePath();

    /**
     * Create a new HTTP Request Client.
     *
     * @return \GuzzleHttp\Client
     */
    protected function httpClient()
    {
        return $this->http ?: $this->http = new Client();
    }

    /**
     * HTTP 클라이언트를 교체한다. 테스트에서 응답을 대체할 때 사용한다.
     *
     * @param  \GuzzleHttp\Client  $client
     * @return $this
     */
    public function setHttpClient(Client $client)
    {
        $this->http = $client;

        return $this;
    }

    /**
     * SENS API 를 호출하고 응답 본문을 배열로 돌려준다.
     *
     * @param  string  $method
     * @param  string  $path  호스트를 제외한 요청 경로
     * @param  array  $body  JSON 으로 직렬화할 요청 바디
     * @param  array  $query  쿼리 파라미터
     * @return array
     *
     * @throws \Daworks\Sens\Exceptions\SensException
     */
    protected function call($method, $path, array $body = [], array $query = [])
    {
        if (! $this->assertValidTokens()) {
            throw SensException::InvalidNCPTokens('NCP tokens are invalid.');
        }

        $method = strtoupper($method);

        // 서명은 실제로 전송되는 경로(쿼리 스트링 포함)와 완전히 일치해야 한다.
        // 그래서 쿼리 스트링을 한 번만 만들고 서명과 URL 양쪽에 같은 문자열을 쓴다.
        $path = $this->buildPath($path, $query);

        $options = ['headers' => $this->prepareRequestHeaders($method, $path)];

        if ($body !== []) {
            $options['body'] = json_encode($body);
        }

        try {
            $response = $this->httpClient()->request($method, $this->baseUrl . $path, $options);
        } catch (RequestException $e) {
            throw SensException::fromRequestException($e);
        } catch (SensException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw SensException::fromThrowable($e);
        }

        return $this->decodeResponse($response);
    }

    /**
     * 쿼리 파라미터를 경로에 붙인다. 값이 비어 있는 파라미터는 제외한다.
     *
     * @param  string  $path
     * @param  array  $query
     * @return string
     */
    protected function buildPath($path, array $query = [])
    {
        $query = array_filter($query, function ($value) {
            return $value !== null && $value !== '' && $value !== [];
        });

        if ($query === []) {
            return $path;
        }

        $queryString = http_build_query($query, '', '&', PHP_QUERY_RFC3986);

        // '@' 는 쿼리에서 인코딩 없이 사용할 수 있는 문자다. 알림톡 채널 아이디
        // (@channel)가 인코딩된 채로 전달되면 SENS 가 값을 찾지 못할 수 있으므로
        // 원래 문자로 되돌린다. 서명과 URL 에 같은 문자열을 쓰므로 서명은 유효하다.
        return $path . '?' . str_replace('%40', '@', $queryString);
    }

    /**
     * 응답 본문을 배열로 변환한다. 204 No Content 는 빈 배열이 된다.
     *
     * @param  \Psr\Http\Message\ResponseInterface  $response
     * @return array
     *
     * @throws \Daworks\Sens\Exceptions\SensException
     */
    protected function decodeResponse(ResponseInterface $response)
    {
        $body = trim((string) $response->getBody());

        if ($body === '') {
            return [];
        }

        $decoded = json_decode($body, true);

        if (! is_array($decoded)) {
            throw SensException::malformedResponse($body);
        }

        return $decoded;
    }

    /**
     * Determine if tokens are exists normally.
     *
     * @return bool
     */
    public function assertValidTokens()
    {
        return ! empty($this->getServiceId()) &&
            ! empty($this->getAccessKey()) &&
            ! empty($this->getSecretKey());
    }

    /**
     * Get SENS service identifier.
     *
     * @return string
     */
    public function getServiceId()
    {
        return $this->serviceId;
    }

    /**
     * Set SENS service identifier.
     *
     * @param  string  $serviceId
     * @return \Daworks\Sens\Sens
     */
    public function setServiceId(string $serviceId)
    {
        $this->serviceId = $serviceId;

        return $this;
    }

    /**
     * Get SENS access key.
     *
     * @return string
     */
    public function getAccessKey()
    {
        return $this->accessKey;
    }

    /**
     * Set SENS access key.
     *
     * @param  string  $accessKey
     * @return \Daworks\Sens\Sens
     */
    public function setAccessKey(string $accessKey)
    {
        $this->accessKey = $accessKey;

        return $this;
    }

    /**
     * Get SENS secret key.
     *
     * @return string
     */
    public function getSecretKey()
    {
        return $this->secretKey;
    }

    /**
     * Set SENS secret key.
     *
     * @param  string  $secretKey
     * @return \Daworks\Sens\Sens
     */
    public function setSecretKey(string $secretKey)
    {
        $this->secretKey = $secretKey;

        return $this;
    }

    /**
     * Resolve the given uri to http request url.
     *
     * @param  string  $uri
     * @param  array  $params
     * @return array
     */
    public function resolveEndpoint($uri, $params)
    {
        foreach ($params as $key => $value) {
            $uri = str_replace('{' . $key . '}', $value, $uri);
        }

        $tokens = explode(' ', $uri);

        return [
            'method' => $tokens[0],
            'url' => $tokens[1],
            'path' => parse_url($tokens[1], PHP_URL_PATH),
            'host' => parse_url($tokens[1], PHP_URL_HOST),
        ];
    }

    /**
     * Prepare HTTP headers for request NCLOUD API v2 authentication.
     *
     * @param  string  $method
     * @param  string  $uri
     * @return array
     */
    public function prepareRequestHeaders($method, $uri)
    {
        $timestamp = $this->timestamp();

        $this->addHeader('Content-Type', 'application/json; charset=utf-8');
        $this->addHeader(self::X_NCP_APIGW_TIMESTAMP, $timestamp);
        $this->addHeader(self::X_NCP_IAM_ACCESS_KEY, $this->getAccessKey());
        $this->addHeader(self::X_NCP_APIGW_SIGNATURE_V2, $this->makeSignature($method, $uri, $timestamp));

        return $this->headers();
    }

    /**
     * Get current timestamp to compare api server.
     *
     * @return string
     */
    protected function timestamp()
    {
        return strval((int)round(microtime(true) * 1000));
    }

    /**
     * Add a new HTTP header attribute.
     *
     * @param  string  $key
     * @param  string  $value
     * @return $this
     */
    public function addHeader(string $key, string $value)
    {
        $this->headers[$key] = $value;

        return $this;
    }

    /**
     * generate x-ncp-apigw-signature-v2 token for authentication.
     *
     * @param  string  $method
     * @param  string  $uri  쿼리 스트링을 포함한 요청 경로
     * @param  string  $timestamp
     * @return string
     */
    public function makeSignature($method, $uri, $timestamp)
    {
        $buffer = [];

        // Important - do not change these all lines down here ever!
        array_push($buffer, strtoupper($method) . " " . $uri);
        array_push($buffer, $timestamp);
        array_push($buffer, $this->getAccessKey());

        $message = implode("\n", $buffer);
        $hash = hex2bin(hash_hmac('sha256', $message, $this->getSecretKey()));

        return base64_encode($hash);
    }

    /**
     * HTTP Header Attributes
     *
     * @return array
     */
    public function headers()
    {
        return $this->headers;
    }

    /**
     * Remove the given HTTP header.
     *
     * @param  string  $key
     * @return \Daworks\Sens\Sens
     */
    public function removeHeader(string $key)
    {
        unset($this->headers[$key]);

        return $this;
    }
}
