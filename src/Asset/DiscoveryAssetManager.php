<?php

/*
 * This file is part of the puli/asset-plugin package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\AssetPlugin\Asset;

use Puli\AssetPlugin\Api\Asset\AssetManager;
use Puli\AssetPlugin\Api\Asset\AssetMapping;
use Puli\AssetPlugin\Api\Asset\DuplicateAssetMappingException;
use Puli\AssetPlugin\Api\Asset\NoSuchAssetMappingException;
use Puli\AssetPlugin\Api\AssetPlugin;
use Puli\AssetPlugin\Api\Target\InstallTargetCollection;
use Puli\AssetPlugin\Api\Target\NoSuchTargetException;
use Puli\Manager\Api\Discovery\BindingDescriptor;
use Puli\Manager\Api\Discovery\DiscoveryManager;
use Puli\Manager\Api\Package\RootPackage;
use Rhumsaa\Uuid\Uuid;
use Webmozart\Expression\Expr;
use Webmozart\Expression\Expression;

/**
 * An asset manager that uses a {@link DiscoveryManager} as storage backend.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class DiscoveryAssetManager implements AssetManager
{
    /**
     * @var DiscoveryManager
     */
    private $discoveryManager;

    /**
     * @var InstallTargetCollection
     */
    private $installTargets;

    /**
     * @var BindingExpressionBuilder
     */
    private $exprBuilder;

    public function __construct(DiscoveryManager $discoveryManager, InstallTargetCollection $installTargets)
    {
        $this->discoveryManager = $discoveryManager;
        $this->installTargets = $installTargets;
        $this->exprBuilder = new BindingExpressionBuilder();
    }

    /**
     * {@inheritdoc}
     */
    public function addAssetMapping(AssetMapping $mapping, $flags = 0)
    {
        if (!($flags & self::IGNORE_TARGET_NOT_FOUND) && !$this->installTargets->contains($mapping->getTargetName())) {
            throw NoSuchTargetException::forTargetName($mapping->getTargetName());
        }

        if (!($flags & self::OVERRIDE) && $this->hasAssetMapping($mapping->getUuid())) {
            throw DuplicateAssetMappingException::forUuid($mapping->getUuid());
        }

        $this->discoveryManager->addRootBinding(new BindingDescriptor(
            // Match directories as well as all of their contents
            $mapping->getGlob().'{,/**/*}',
            AssetPlugin::BINDING_TYPE,
            array(
                AssetPlugin::TARGET_PARAMETER => $mapping->getTargetName(),
                AssetPlugin::PATH_PARAMETER => $mapping->getWebPath(),
            ),
            'glob',
            $mapping->getUuid()
        ), ($flags & self::OVERRIDE) ? DiscoveryManager::OVERRIDE : 0);
    }

    /**
     * {@inheritdoc}
     */
    public function removeAssetMapping(Uuid $uuid)
    {
        $expr = Expr::same($uuid->toString(), BindingDescriptor::UUID)
            ->andX($this->exprBuilder->buildExpression());

        $this->discoveryManager->removeRootBindings($expr);
    }

    /**
     * {@inheritdoc}
     */
    public function removeAssetMappings(Expression $expr)
    {
        $this->discoveryManager->removeRootBindings($this->exprBuilder->buildExpression($expr));
    }

    /**
     * {@inheritdoc}
     */
    public function clearAssetMappings()
    {
        $this->discoveryManager->removeRootBindings($this->exprBuilder->buildExpression());
    }

    /**
     * {@inheritdoc}
     */
    public function getAssetMapping(Uuid $uuid)
    {
        $expr = Expr::same($uuid->toString(), BindingDescriptor::UUID)
            ->andX($this->exprBuilder->buildExpression());

        $bindings = $this->discoveryManager->findBindings($expr);

        if (!$bindings) {
            throw NoSuchAssetMappingException::forUuid($uuid);
        }

        // Since we are looking for enabled bindings only, there should only be
        // one result for the given UUID
        return $this->bindingToMapping(reset($bindings));
    }

    /**
     * {@inheritdoc}
     */
    public function getAssetMappings()
    {
        $bindings = $this->discoveryManager->findBindings($this->exprBuilder->buildExpression());
        $mappings = array();

        foreach ($bindings as $binding) {
            $mappings[] = $this->bindingToMapping($binding);
        }

        return $mappings;
    }

    /**
     * {@inheritdoc}
     */
    public function findAssetMappings(Expression $expr)
    {
        $bindings = $this->discoveryManager->findBindings($this->exprBuilder->buildExpression($expr));
        $mappings = array();

        foreach ($bindings as $binding) {
            $mappings[] = $this->bindingToMapping($binding);
        }

        return $mappings;
    }

    /**
     * {@inheritdoc}
     */
    public function hasAssetMapping(Uuid $uuid)
    {
        $expr = Expr::same($uuid->toString(), BindingDescriptor::UUID)
            ->andX($this->exprBuilder->buildExpression());

        return $this->discoveryManager->hasBindings($expr);
    }

    /**
     * {@inheritdoc}
     */
    public function hasAssetMappings(Expression $expr = null)
    {
        return $this->discoveryManager->hasBindings($this->exprBuilder->buildExpression($expr));
    }

    private function bindingToMapping(BindingDescriptor $binding)
    {
        return new AssetMapping(
            // Remove "{,/**/*}" suffix
            substr($binding->getQuery(), 0, -8),
            $binding->getParameterValue(AssetPlugin::TARGET_PARAMETER),
            $binding->getParameterValue(AssetPlugin::PATH_PARAMETER),
            $binding->getUuid()
        );
    }
}
