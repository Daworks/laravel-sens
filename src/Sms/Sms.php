<?php

namespace Daworks\Sens\Sms;

use Exception;
use Daworks\Sens\Sens;
use Daworks\Sens\Exceptions\SensException;

class Sms extends Sens
{
    /**
     * Handle the send action.
     *
     * @param  array  $params
     * @return void
     * @throws \Daworks\Sens\Exceptions\SensException
     */
    public function send(array $params)
    {
        if (! $this->assertValidTokens()) {
            throw SensException::InvalidNCPTokens('NCP tokens are invalid.');
        }

        try {
            $uri = '{method} https://sens.apigw.ntruss.com/sms/v2/services/{service}/messages';

            $endpoint = $this->resolveEndpoint($uri, [
                'method' => 'POST',
                'service' => $this->getServiceId(),
            ]);

            $this->httpClient()->post($endpoint['url'], [
                'headers' => $this->prepareRequestHeaders(
                    $endpoint['method'],
                    $endpoint['path']
                ),
                'body' => json_encode($params),
            ]);
        } catch (Exception $e) {
            throw new SensException($e);
        }
    }
}
