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
use N7e\ServiceProviderInterface;
use N7e\WordPress\Assets\AssetRegistry;
use N7e\WordPress\Assets\AssetRegistryInterface;
use N7e\WordPress\Assets\Script;
use N7e\WordPress\Assets\Style;
use Override;
use RuntimeException;

/**
 * Provides WordPress assets.
 */
final class AssetsProvider implements ServiceProviderInterface
{
    /**
     * Registered assets.
     *
     * @var \N7e\WordPress\Assets\AssetRegistryInterface|null
     */
    private ?AssetRegistryInterface $assets = null;

    /**
     * Root directory aggregate.
     *
     * @var \N7e\RootDirectoryAggregateInterface
     */
    private RootDirectoryAggregateInterface $rootDirectoryAggregate;

    #[Override]
    public function configure(ContainerBuilderInterface $containerBuilder): void
    {
        $containerBuilder->addFactory(AssetRegistryInterface::class, function () {
            if (is_null($this->assets)) {
                throw new RuntimeException('Cannot use asset registry before assets provider\'s load phase');
            }

            return $this->assets;
        })
            ->singleton();
    }

    #[Override]
    public function load(ContainerInterface $container): void
    {
        /** @var \N7e\Configuration\ConfigurationInterface $configuration */
        $configuration = $container->get(ConfigurationInterface::class);

        $this->rootDirectoryAggregate = $container->get(RootDirectoryAggregateInterface::class);

        /** @var \N7e\RootUrlAggregateInterface $rootUrlAggregate */
        $rootUrlAggregate = $container->get(RootUrlAggregateInterface::class);

        $this->assets = new AssetRegistry(
            $this->rootDirectoryAggregate->getRootDirectory() . $configuration->get('assets.directory', '/assets'),
            $rootUrlAggregate->getRootUrl() . $configuration->get('assets.url', '/assets')
        );

        $scripts = $configuration->get('assets.scripts', []);
        $styles = $configuration->get('assets.styles', []);

        foreach ([...$scripts, ...$styles] as $asset) {
            $this->register($asset);
        }

        add_action('wp_enqueue_scripts', [$this->assets, 'enqueue']);
    }

    /**
     * Register given asset definition.
     *
     * @param array $assetDefinition Arbitrary asset definition.
     */
    private function register(array $assetDefinition): void
    {
        if (
            ! array_key_exists('type', $assetDefinition) ||
            ! array_key_exists('handle', $assetDefinition) ||
            ! array_key_exists('name', $assetDefinition)
        ) {
            throw new InvalidAssetDefinitionException();
        }

        $asset = $assetDefinition['type'] === 'style' ?
            $this->registerStyle($assetDefinition) :
            $this->registerScript($assetDefinition);

        $asset->preload((bool) ($assetDefinition['preload'] ?? false));
    }

    /**
     * Register given style definition.
     *
     * @param array $styleDefinition Arbitrary style definition.
     * @return \N7e\WordPress\Assets\Style Registered style.
     */
    private function registerStyle(array $styleDefinition): Style
    {
        $style = $this->assets->registerStyle($styleDefinition['handle'], $styleDefinition['name']);

        if (array_key_exists('mediaType', $styleDefinition)) {
            $style->for($styleDefinition['mediaType']);
        }

        return $style;
    }

    /**
     * Register given script definition.
     *
     * @param array $scriptDefinition Arbitrary script definition.
     * @return \N7e\WordPress\Assets\Script Registered script.
     */
    private function registerScript(array $scriptDefinition): Script
    {
        $script = $this->assets->registerScript($scriptDefinition['handle'], $scriptDefinition['name']);

        if (array_key_exists('targetLocation', $scriptDefinition)) {
            $script->loadIn($scriptDefinition['targetLocation']);
        }

        if (array_key_exists('translations', $scriptDefinition)) {
            foreach ($scriptDefinition['translations'] as $translation) {
                $path = ltrim($translation['path'] ?? '', '/');

                $script->withTranslation(
                    $translation['domain'],
                    strlen($path) > 0 ?
                        $this->rootDirectoryAggregate->getRootDirectory() . '/' . $path :
                        null
                );
            }
        }

        return $script;
    }
}
