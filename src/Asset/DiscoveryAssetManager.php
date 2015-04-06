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
    public function addAssetMapping(AssetMapping $mapping)
    {
        if (!$this->installTargets->contains($mapping->getTargetName())) {
            throw NoSuchTargetException::forTargetName($mapping->getTargetName());
        }

        $this->discoveryManager->addBinding(new BindingDescriptor(
            // Match directories as well as all of their contents
            $mapping->getGlob().'{,/**/*}',
            AssetPlugin::BINDING_TYPE,
            array(
                AssetPlugin::TARGET_PARAMETER => $mapping->getTargetName(),
                AssetPlugin::PATH_PARAMETER => $mapping->getWebPath(),
            ),
            'glob',
            $mapping->getUuid()
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function removeAssetMapping(Uuid $uuid)
    {
        $expr = Expr::same(BindingDescriptor::UUID, $uuid->toString())
            ->andX($this->exprBuilder->buildExpression());

        $bindings = $this->discoveryManager->findBindings($expr);

        // There should be either none or one binding, as there cannot be more
        // than one enabled binding with the same UUID at the same time
        // Anyway, be defensive and loop over all bindings
        foreach ($bindings as $binding) {
            $package = $binding->getContainingPackage();

            if ($package instanceof RootPackage) {
                $this->discoveryManager->removeBinding($uuid);
            } else {
                $this->discoveryManager->disableBinding($uuid, $package->getName());
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getAssetMapping(Uuid $uuid)
    {
        $expr = Expr::same(BindingDescriptor::UUID, $uuid->toString())
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
        $expr = Expr::same(BindingDescriptor::UUID, $uuid->toString())
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
