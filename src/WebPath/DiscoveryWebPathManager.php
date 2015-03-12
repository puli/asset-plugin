<?php

/*
 * This file is part of the puli/web-resource-plugin package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\WebResourcePlugin\WebPath;

use Puli\RepositoryManager\Api\Discovery\BindingDescriptor;
use Puli\RepositoryManager\Api\Discovery\DiscoveryManager;
use Puli\RepositoryManager\Api\Package\RootPackage;
use Puli\WebResourcePlugin\Api\Target\InstallTargetCollection;
use Puli\WebResourcePlugin\Api\Target\NoSuchTargetException;
use Puli\WebResourcePlugin\Api\WebPath\NoSuchWebPathMappingException;
use Puli\WebResourcePlugin\Api\WebPath\WebPathManager;
use Puli\WebResourcePlugin\Api\WebPath\WebPathMapping;
use Puli\WebResourcePlugin\Api\WebResourcePlugin;
use Rhumsaa\Uuid\Uuid;
use Webmozart\Expression\Expr;
use Webmozart\Expression\Expression;

/**
 * A web path manager that uses a {@link DiscoveryManager} as storage backend.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class DiscoveryWebPathManager implements WebPathManager
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
    public function addWebPathMapping(WebPathMapping $mapping)
    {
        if (!$this->installTargets->contains($mapping->getTargetName())) {
            throw NoSuchTargetException::forTargetName($mapping->getTargetName());
        }

        $this->discoveryManager->addBinding(new BindingDescriptor(
            // Match directories as well as all of their contents
            $mapping->getGlob().'{,/**}',
            WebResourcePlugin::BINDING_TYPE,
            array(
                WebResourcePlugin::TARGET_PARAMETER => $mapping->getTargetName(),
                WebResourcePlugin::PATH_PARAMETER => $mapping->getWebPath(),
            ),
            'glob',
            $mapping->getUuid()
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function removeWebPathMapping(Uuid $uuid)
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
    public function getWebPathMapping(Uuid $uuid)
    {
        $expr = Expr::same(BindingDescriptor::UUID, $uuid->toString())
            ->andX($this->exprBuilder->buildExpression());

        $bindings = $this->discoveryManager->findBindings($expr);

        if (!$bindings) {
            throw NoSuchWebPathMappingException::forUuid($uuid);
        }

        // Since we are looking for enabled bindings only, there should only be
        // one result for the given UUID
        return $this->bindingToMapping(reset($bindings));
    }

    /**
     * {@inheritdoc}
     */
    public function getWebPathMappings()
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
    public function findWebPathMappings(Expression $expr)
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
    public function hasWebPathMapping(Uuid $uuid)
    {
        $expr = Expr::same(BindingDescriptor::UUID, $uuid->toString())
            ->andX($this->exprBuilder->buildExpression());

        return $this->discoveryManager->hasBindings($expr);
    }

    /**
     * {@inheritdoc}
     */
    public function hasWebPathMappings(Expression $expr = null)
    {
        return $this->discoveryManager->hasBindings($this->exprBuilder->buildExpression($expr));
    }

    private function bindingToMapping(BindingDescriptor $binding)
    {
        return new WebPathMapping(
            // Remove "{,/**}" suffix
            substr($binding->getQuery(), 0, -6),
            $binding->getParameterValue(WebResourcePlugin::TARGET_PARAMETER),
            $binding->getParameterValue(WebResourcePlugin::PATH_PARAMETER),
            $binding->getUuid()
        );
    }
}
