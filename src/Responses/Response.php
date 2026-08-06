<?php

namespace Daworks\Sens\Responses;

use ArrayAccess;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use JsonSerializable;
use LogicException;

/**
 * SENS API 응답을 감싸는 읽기 전용 값 객체.
 *
 * 하위 클래스가 자주 쓰는 필드를 프로퍼티로 노출하지만, SENS 가 응답에 필드를
 * 추가하더라도 get() / toArray() 를 통해 원본에 그대로 접근할 수 있다.
 */
abstract class Response implements ArrayAccess, Arrayable, JsonSerializable
{
    /** @var array */
    protected $payload;

    /**
     * @param  array  $payload
     */
    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    /**
     * 원본 응답에서 값을 꺼낸다. "dot" 표기를 지원한다.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return Arr::get($this->payload, $key, $default);
    }

    /**
     * SENS 가 응답한 원본 배열.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->payload;
    }

    /**
     * @return array
     */
    public function jsonSerialize(): mixed
    {
        return $this->payload;
    }

    /**
     * @param  mixed  $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return Arr::has($this->payload, $offset);
    }

    /**
     * @param  mixed  $offset
     * @return mixed
     */
    public function offsetGet($offset): mixed
    {
        return $this->get($offset);
    }

    /**
     * @param  mixed  $offset
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($offset, $value): void
    {
        throw new LogicException('SENS response is read-only.');
    }

    /**
     * @param  mixed  $offset
     * @return void
     */
    public function offsetUnset($offset): void
    {
        throw new LogicException('SENS response is read-only.');
    }
}
