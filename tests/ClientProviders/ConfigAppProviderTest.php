<?php

namespace BeyondCode\LaravelWebSockets\Tests\ClientProviders;

use BeyondCode\LaravelWebSockets\Tests\TestCase;
use BeyondCode\LaravelWebSockets\Apps\ConfigAppProvider;

class ConfigAppProviderTest extends TestCase
{
    /** @var \BeyondCode\LaravelWebSockets\Apps\ConfigAppProvider */
    protected $configAppProvider;

    public function setUp()
    {
        parent::setUp();

        $this->configAppProvider = new ConfigAppProvider();
    }

    /** @test */
    public function it_can_get_apps_from_the_config_file()
    {
        $apps = $this->configAppProvider->all();

        $this->assertCount(1, $apps);

        /** @var $app */
        $app = $apps[0];

        $this->assertEquals('Test App', $app->getName());
        $this->assertEquals(1234, $app->getId());
        $this->assertEquals('TestKey', $app->getKey());
        $this->assertEquals('TestSecret', $app->getSecret());
        $this->assertFalse($app->isClientMessagesEnabled());
        $this->assertTrue($app->isStatisticsEnabled());
    }

    /** @test */
    public function it_can_find_app_by_id()
    {
        $app = $this->configAppProvider->findById(0000);

        $this->assertNull($app);

        $app = $this->configAppProvider->findById(1234);

        $this->assertEquals('Test App', $app->getName());
        $this->assertEquals(1234, $app->getId());
        $this->assertEquals('TestKey', $app->getKey());
        $this->assertEquals('TestSecret', $app->getSecret());
        $this->assertFalse($app->isClientMessagesEnabled());
        $this->assertTrue($app->isStatisticsEnabled());
    }

    /** @test */
    public function it_can_find_app_by_key()
    {
        $app = $this->configAppProvider->findByKey('InvalidKey');

        $this->assertNull($app);

        $app = $this->configAppProvider->findByKey('TestKey');

        $this->assertEquals('Test App', $app->getName());
        $this->assertEquals(1234, $app->getId());
        $this->assertEquals('TestKey', $app->getKey());
        $this->assertEquals('TestSecret', $app->getSecret());
        $this->assertFalse($app->isClientMessagesEnabled());
        $this->assertTrue($app->isStatisticsEnabled());
    }
}
