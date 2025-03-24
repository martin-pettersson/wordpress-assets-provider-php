<?php

/*
 * Copyright (c) 2025 Martin Pettersson
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace N7e\WordPress;

use N7e\Configuration\ConfigurationInterface;
use N7e\DependencyInjection\ContainerBuilderInterface;
use N7e\DependencyInjection\ContainerInterface;
use N7e\RootDirectoryAggregateInterface;
use N7e\RootUrlAggregateInterface;
use N7e\WordPress\Assets\AssetRegistry;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(AssetsProvider::class)]
#[CoversClass(InvalidAssetDefinitionException::class)]
class AssetsProviderTest extends TestCase
{
    use PHPMock;

    const string URL = 'http://example.com/assets';

    private AssetsProvider $provider;
    private MockObject $containerBuilderMock;
    private MockObject $containerMock;
    private MockObject $configurationMock;
    private MockObject $rootDirectoryAggregateMock;
    private MockObject $rootUrlAggregateMock;

    #[Before]
    public function setUp(): void
    {
        $this->containerBuilderMock = $this->getMockBuilder(ContainerBuilderInterface::class)->getMock();
        $this->containerMock = $this->getMockBuilder(ContainerInterface::class)->getMock();
        $this->configurationMock = $this->getMockBuilder(ConfigurationInterface::class)->getMock();
        $this->rootDirectoryAggregateMock = $this->getMockBuilder(RootDirectoryAggregateInterface::class)->getMock();
        $this->rootUrlAggregateMock = $this->getMockBuilder(RootUrlAggregateInterface::class)->getMock();
        $this->provider = new AssetsProvider();

        $this->containerBuilderMock->method('build')->willReturn($this->containerMock);
        $this->containerMock->method('get')
            ->willReturnOnConsecutiveCalls(
                $this->configurationMock,
                $this->rootDirectoryAggregateMock,
                $this->rootUrlAggregateMock,
                $this->configurationMock
            );
        $this->rootDirectoryAggregateMock->method('getRootDirectory')->willReturn(__DIR__ . '/fixtures');
        $this->rootUrlAggregateMock->method('getRootUrl')->willReturn(AssetsProviderTest::URL);
    }

    #[Test]
    public function shouldRegisterAssetRegistry(): void
    {
        $this->rootDirectoryAggregateMock->expects($this->once())->method('getRootDirectory');
        $this->rootUrlAggregateMock->expects($this->once())->method('getRootUrl');
        $this->containerBuilderMock
            ->expects($this->once())
            ->method('addFactory')
            ->with(AssetRegistry::class, $this->isCallable());

        $this->provider->configure($this->containerBuilderMock);
    }

    #[Test]
    public function shouldNotRegisterAnyAssetsIfConfigurationIsEmpty(): void
    {
        $this->configurationMock
            ->expects($this->exactly(3))
            ->method('get')
            ->willReturnOnConsecutiveCalls('', '', []);
        $this->getFunctionMock(__NAMESPACE__, 'add_action')
            ->expects($this->once())
            ->with('wp_enqueue_scripts', $this->isCallable())
            ->willReturnCallback(static fn($hook, $callback) => $callback());

        $this->provider->configure($this->containerBuilderMock);
        $this->provider->load($this->containerMock);
    }

    #[Test]
    public function shouldThrowExceptionIfInvalidAssetDefinition(): void
    {
        $this->expectException(InvalidAssetDefinitionException::class);

        $this->configurationMock
            ->expects($this->exactly(3))
            ->method('get')
            ->willReturnOnConsecutiveCalls('', '', [[]]);

        $this->provider->configure($this->containerBuilderMock);
        $this->provider->load($this->containerMock);
    }

    #[Test]
    public function shouldRegisterStylesFromConfiguration(): void
    {
        $handle = 'handle';
        $name = 'name';

        $this->configurationMock
            ->expects($this->exactly(3))
            ->method('get')
            ->willReturnOnConsecutiveCalls(
                '',
                '',
                [
                    [
                        'type' => 'style',
                        'handle' => $handle,
                        'name' => $name,
                        'mediaType' => 'screen'
                    ]
                ]
            );
        $this->getFunctionMock(__NAMESPACE__ . '\\Assets', 'wp_enqueue_style')
            ->expects($this->once())
            ->with($handle, AssetsProviderTest::URL . '/' . $name . '.css', ['dependency'], 'style-version', 'screen');
        $this->getFunctionMock(__NAMESPACE__, 'add_action')
            ->expects($this->once())
            ->with('wp_enqueue_scripts', $this->isCallable())
            ->willReturnCallback(static fn($hook, $callback) => $callback());

        $this->provider->configure($this->containerBuilderMock);
        $this->provider->load($this->containerMock);
    }

    #[Test]
    public function shouldRegisterScriptsFromConfiguration(): void
    {
        $handle = 'handle';
        $name = 'name';

        $this->configurationMock
            ->expects($this->exactly(3))
            ->method('get')
            ->willReturnOnConsecutiveCalls(
                '',
                '',
                [
                    [
                        'type' => 'script',
                        'handle' => $handle,
                        'name' => $name,
                        'targetLocation' => 'head',
                        'translations' => [
                            [
                                'domain' => 'translation-one',
                                'path' => 'translation-one-path'
                            ],
                            [
                                'domain' => 'translation-two'
                            ]
                        ]
                    ]
                ]
            );
        $this->getFunctionMock(__NAMESPACE__ . '\\Assets', 'wp_register_script')
            ->expects($this->once())
            ->with($handle, AssetsProviderTest::URL . '/' . $name . '.js', ['dependency'], 'script-version', false);
        $this->getFunctionMock(__NAMESPACE__ . '\\Assets', 'wp_enqueue_script')
            ->expects($this->once())
            ->with($handle);
        $this->getFunctionMock(__NAMESPACE__ . '\\Assets', 'wp_set_script_translations')
            ->expects($this->exactly(2));
        $this->getFunctionMock(__NAMESPACE__, 'add_action')
            ->expects($this->once())
            ->with('wp_enqueue_scripts', $this->isCallable())
            ->willReturnCallback(static fn($hook, $callback) => $callback());

        $this->provider->configure($this->containerBuilderMock);
        $this->provider->load($this->containerMock);
    }
}
