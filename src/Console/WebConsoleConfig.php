<?php

/*
 * This file is part of the puli/asset-plugin package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\AssetPlugin\Console;

use Puli\AssetPlugin\Api\AssetPlugin;
use Puli\AssetPlugin\Api\Target\InstallTarget;
use Webmozart\Console\Api\Args\Format\Argument;
use Webmozart\Console\Api\Args\Format\Option;
use Webmozart\Console\Api\Config\ApplicationConfig;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
final class WebConsoleConfig
{
    public static function addConfiguration(ApplicationConfig $config, AssetPlugin $plugin)
    {
        $config
            ->beginCommand('asset')
                ->setDescription('Manage web assets')
                ->setHandler(function () use ($plugin) {
                    return new AssetCommandHandler(
                        $plugin->getAssetManager(),
                        $plugin->getInstallationManager(),
                        $plugin->getInstallTargetManager()
                    );
                })

                ->beginSubCommand('map')
                    ->addArgument('path', Argument::REQUIRED, 'The resource path')
                    ->addArgument('web-path', Argument::REQUIRED, 'The path in the web directory')
                    ->addOption('target', 't', Option::REQUIRED_VALUE | Option::PREFER_LONG_NAME, 'The name of the installation target', InstallTarget::DEFAULT_TARGET)
                    ->addOption('force', 'f', Option::NO_VALUE, 'Map even if the target does not exist')
                    ->setHandlerMethod('handleMap')
                ->end()

                ->beginSubCommand('remove')
                    ->addArgument('uuid', Argument::REQUIRED, 'The UUID (prefix) of the mapping')
                    ->setHandlerMethod('handleRemove')
                ->end()

                ->beginSubCommand('list')
                    ->markDefault()
                    ->setHandlerMethod('handleList')
                ->end()

                ->beginSubCommand('install')
                    ->addArgument('target', Argument::OPTIONAL, 'The target to install. By default, all targets are installed')
                    ->setHandlerMethod('handleInstall')
                ->end()
            ->end()

            ->beginCommand('target')
                ->setDescription('Manage the install targets of your web resources')
                ->setHandler(function () use ($plugin) {
                    return new TargetCommandHandler($plugin->getInstallTargetManager());
                })

                ->beginSubCommand('list')
                    ->markDefault()
                    ->setHandlerMethod('handleList')
                ->end()

                ->beginSubCommand('add')
                    ->addArgument('name', Argument::REQUIRED, 'The name of the target')
                    ->addArgument('location', Argument::REQUIRED, 'The location where the web resources are installed')
                    ->addOption('installer', null, Option::REQUIRED_VALUE, 'The name of the used installer', 'symlink')
                    ->addOption('url-format', null, Option::REQUIRED_VALUE, 'The format of the generated resource URLs', InstallTarget::DEFAULT_URL_FORMAT)
                    ->addOption('param', null, Option::REQUIRED_VALUE | Option::MULTI_VALUED, 'Additional parameters to store with the target')
                    ->setHandlerMethod('handleAdd')
                ->end()

                ->beginSubCommand('remove')
                    ->addArgument('name', Argument::REQUIRED, 'The name of the target to remove')
                    ->setHandlerMethod('handleRemove')
                ->end()

                ->beginSubCommand('set-default')
                    ->addArgument('name', Argument::REQUIRED, 'The name of the default target')
                    ->setHandlerMethod('handleSetDefault')
                ->end()

                ->beginSubCommand('get-default')
                    ->setHandlerMethod('handleGetDefault')
                ->end()
            ->end()

            ->beginCommand('installer')
                ->setDescription('Manage the installers used to install web resources')
                ->setHandler(function () use ($plugin) {
                    return new InstallerCommandHandler($plugin->getInstallerManager());
                })

                ->beginSubCommand('list')
                    ->markDefault()
                    ->addOption('long', 'l', Option::NO_VALUE, 'Print the fully-qualified class name')
                    ->setHandlerMethod('handleList')
                ->end()

                ->beginSubCommand('add')
                    ->addArgument('name', Argument::REQUIRED, 'The name of the installer')
                    ->addArgument('class', Argument::REQUIRED, 'The fully-qualified class name of the installer')
                    ->addOption('description', null, Option::REQUIRED_VALUE | Option::MULTI_VALUED, 'The description of the installer')
                    ->addOption('param', null, Option::REQUIRED_VALUE | Option::MULTI_VALUED, 'Additional installer parameters')
                    ->setHandlerMethod('handleAdd')
                ->end()

                ->beginSubCommand('remove')
                    ->addArgument('name', Argument::REQUIRED, 'The name of the installer to remove')
                    ->setHandlerMethod('handleRemove')
                ->end()
            ->end()
        ;
    }

    private function __construct()
    {
    }
}
