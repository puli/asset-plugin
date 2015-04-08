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

use Puli\AssetPlugin\Api\Asset\AssetManager;
use Puli\AssetPlugin\Api\Asset\AssetMapping;
use Puli\AssetPlugin\Api\Installation\InstallationManager;
use Puli\AssetPlugin\Api\Installation\InstallationParams;
use Puli\AssetPlugin\Api\Target\InstallTarget;
use Puli\AssetPlugin\Api\Target\InstallTargetManager;
use RuntimeException;
use Webmozart\Console\Api\Args\Args;
use Webmozart\Console\Api\IO\IO;
use Webmozart\Console\UI\Component\Table;
use Webmozart\Console\UI\Style\TableStyle;
use Webmozart\Expression\Expr;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class AssetCommandHandler
{
    /**
     * @var AssetManager
     */
    private $assetManager;

    /**
     * @var InstallationManager
     */
    private $installationManager;

    /**
     * @var InstallTargetManager
     */
    private $targetManager;

    public function __construct(AssetManager $assetManager, InstallationManager $installationManager, InstallTargetManager $targetManager)
    {
        $this->assetManager = $assetManager;
        $this->installationManager = $installationManager;
        $this->targetManager = $targetManager;
    }

    public function handleList(Args $args, IO $io)
    {
        /** @var AssetMapping[][] $mappingsByTarget */
        $mappingsByTarget = array();

        /** @var InstallTarget[] $targets */
        $targets = array();

        // Assemble mappings and validate targets
        foreach ($this->assetManager->getAssetMappings() as $mapping) {
            $targetName = $mapping->getTargetName();

            if (!isset($mappingsByTarget[$targetName])) {
                $mappingsByTarget[$targetName] = array();
                $targets[$targetName] = $this->targetManager->getTarget($targetName);
            }

            $mappingsByTarget[$targetName][] = $mapping;
        }

        if (!$mappingsByTarget) {
            $io->writeLine('No assets are mapped. Use "puli asset map <path> <web-path>" to map assets.');

            return 0;
        }

        $io->writeLine('The following web assets are currently enabled:');
        $io->writeLine('');

        foreach ($mappingsByTarget as $targetName => $mappings) {
            $targetTitle = 'Target <bu>'.$targetName.'</bu>';

            if ($targetName === InstallTarget::DEFAULT_TARGET) {
                $targetTitle .= ' (alias of: <bu>'.$targets[$targetName]->getName().'</bu>)';
            }

            $io->writeLine("    <b>$targetTitle</b>");
            $io->writeLine("    Location:   <c2>{$targets[$targetName]->getLocation()}</c2>");
            $io->writeLine("    Installer:  {$targets[$targetName]->getInstallerName()}");
            $io->writeLine("    URL Format: <c1>{$targets[$targetName]->getUrlFormat()}</c1>");
            $io->writeLine('');

            $table = new Table(TableStyle::borderless());

            foreach ($mappings as $mapping) {
                $glob = $mapping->getGlob();
                $webPath = $mapping->getWebPath();

                $table->addRow(array(
                    substr($mapping->getUuid()->toString(), 0, 6),
                    '<c1>'.$glob.'</c1>',
                    '<c2>'.$webPath.'</c2>'
                ));
            }

            $table->render($io, 8);

            $io->writeLine('');
        }

        $io->writeLine('Use "puli asset install" to install the assets in their targets.');

        return 0;
    }

    public function handleMap(Args $args)
    {
        $flags = $args->isOptionSet('force') ? AssetManager::NO_TARGET_CHECK : 0;

        $this->assetManager->addAssetMapping(new AssetMapping(
            $args->getArgument('path'),
            $args->getOption('target'),
            $args->getArgument('web-path')
        ), $flags);

        return 0;
    }

    public function handleRemove(Args $args)
    {
        $expr = Expr::startsWith(AssetMapping::UUID, $args->getArgument('uuid'));

        $mappings = $this->assetManager->findAssetMappings($expr);

        if (!$mappings) {
            throw new RuntimeException(sprintf(
                'The mapping with the UUID (prefix) "%s" does not exist.',
                $args->getArgument('uuid')
            ));
        }

        foreach ($mappings as $mapping) {
            $this->assetManager->removeAssetMapping($mapping->getUuid());
        }

        return 0;
    }

    public function handleInstall(Args $args, IO $io)
    {
        if ($args->isArgumentSet('target')) {
            $expr = Expr::same(AssetMapping::TARGET_NAME, $args->getArgument('target'));
            $mappings = $this->assetManager->findAssetMappings($expr);
        } else {
            $mappings = $this->assetManager->getAssetMappings();
        }

        if (!$mappings) {
            $io->writeLine('Nothing to install.');

            return 0;
        }

        /** @var InstallationParams[] $paramsToInstall */
        $paramsToInstall = array();

        // Prepare and validate the installation of all matching mappings
        foreach ($mappings as $mapping) {
            $paramsToInstall[] = $this->installationManager->prepareInstallation($mapping);
        }

        foreach ($paramsToInstall as $params) {
            foreach ($params->getResources() as $resource) {
                $webPath = rtrim($params->getTargetLocation(), '/').$params->getWebPathForResource($resource);

                $io->writeLine(sprintf(
                    'Installing <c1>%s</c1> into <c2>%s</c2> via <u>%s</u>...',
                    $resource->getRepositoryPath(),
                    trim($webPath, '/'),
                    $params->getInstallerDescriptor()->getName()
                ));

                $this->installationManager->installResource($resource, $params);
            }
        }

        return 0;
    }
}
