<?php

namespace Daworks\Sens\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Daworks\Sens\Sms\Sms sms()
 * @method static \Daworks\Sens\AlimTalk\AlimTalk alimTalk()
 *
 * @see \Daworks\Sens\SensManager
 */
class Sens extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'sens';
    }
}
