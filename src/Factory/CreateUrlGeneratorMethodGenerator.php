<?php

/*
 * This file is part of the puli/asset-plugin package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\AssetPlugin\Factory;

use Puli\AssetPlugin\Api\Target\InstallTargetManager;
use Puli\Manager\Api\Php\Argument;
use Puli\Manager\Api\Php\Clazz;
use Puli\Manager\Api\Php\Import;
use Puli\Manager\Api\Php\Method;
use Puli\Manager\Api\Php\ReturnValue;

/**
 * Generates the `createUrlGenerator()` method of the Puli factory.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class CreateUrlGeneratorMethodGenerator
{
    /**
     * @var InstallTargetManager
     */
    private $targetManager;

    public function __construct(InstallTargetManager $targetManager)
    {
        $this->targetManager = $targetManager;
    }

    public function addCreateUrlGeneratorMethod(Clazz $class)
    {
        $class->addImport(new Import('Puli\Discovery\Api\ResourceDiscovery'));
        $class->addImport(new Import('Puli\AssetPlugin\Api\Factory\UrlGeneratorFactory'));
        $class->addImport(new Import('Puli\AssetPlugin\Api\Target\InstallTargetCollection'));
        $class->addImport(new Import('Puli\AssetPlugin\Api\UrlGenerator\AssetUrlGenerator'));
        $class->addImport(new Import('Puli\AssetPlugin\UrlGenerator\DiscoveryUrlGenerator'));

        $class->addImplementedInterface('UrlGeneratorFactory');

        $method = new Method('createUrlGenerator');
        $method->setDescription('Creates the URL generator.');

        $arg = new Argument('discovery');
        $arg->setTypeHint('ResourceDiscovery');
        $arg->setType('ResourceDiscovery');
        $arg->setDescription('The resource discovery to read from.');
        $method->addArgument($arg);

        $method->setReturnValue(new ReturnValue('$generator', 'AssetUrlGenerator', 'The created URL generator.'));

        $targets = $this->targetManager->getTargets();
        $targetsString = '';

        foreach ($targets as $target) {
            $parameters = '';

            foreach ($target->getParameterValues() as $name => $value) {
                $parameters .= sprintf(
                    "\n        %s => %s,",
                    var_export($name, true),
                    var_export($value, true)
                );
            }

            if ($parameters) {
                $parameters .= "\n    ";
            }

            $targetsString .= sprintf(
                "\n    new InstallTarget(%s, %s, %s, %s, array(%s)),",
                var_export($target->getName(), true),
                var_export($target->getInstallerName(), true),
                var_export($target->getLocation(), true),
                var_export($target->getUrlFormat(), true),
                $parameters
            );
        }

        if ($targetsString) {
            $class->addImport(new Import('Puli\AssetPlugin\Api\Target\InstallTarget'));
            $targetsString = "array($targetsString\n)";
        }

        $method->addBody("\$targets = new InstallTargetCollection($targetsString);");

        if ($targetsString) {
            $method->addBody("\$targets->setDefaultTarget('{$targets->getDefaultTarget()->getName()}');");
        }

        $method->addBody("\$generator = new DiscoveryUrlGenerator(\$discovery, \$targets);");

        $class->addMethod($method);
    }
}
