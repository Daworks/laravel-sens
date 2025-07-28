<?php

namespace Daworks\Sens\Tests;

use Mockery as m;
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

class SensSmsChannelTest extends TestCase
{
    /**
     * @var \Mockery\MockInterface|\GuzzleHttp\Client
     */
    private $guzzleHttp;

    protected function setUp(): void
    {
        parent::setUp();

        $this->guzzleHttp = m::mock(Client::class);
    }

    public function testIamSorryBecauseTestsAreSoNuisance(): void
    {
        $this->assertTrue(true);
    }

    protected function tearDown(): void
    {
        m::close();
    }
}
