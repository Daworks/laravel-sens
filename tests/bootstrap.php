<?php

require __DIR__ . '/../vendor/autoload.php';

/*
 * config() 헬퍼는 illuminate/foundation 에 정의되어 있다.
 * 이 패키지는 라라벨 애플리케이션 안에서 동작하므로 실제 환경에서는 항상
 * 존재하지만, 테스트에서는 프레임워크 전체를 띄우지 않으므로 직접 정의한다.
 */
if (! function_exists('config')) {
    /**
     * @param  array|string|null  $key
     * @param  mixed  $default
     * @return mixed
     */
    function config($key = null, $default = null)
    {
        $config = \Illuminate\Container\Container::getInstance()->make('config');

        if (is_null($key)) {
            return $config;
        }

        return $config->get($key, $default);
    }
}
