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
use Webmozart\PathUtil\Path;

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

    /**
     * @var string
     */
    private $currentPath = '/';

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
        $nonExistingTargets = array();

        // Assemble mappings and validate targets
        foreach ($this->assetManager->getAssetMappings() as $mapping) {
            $targetName = $mapping->getTargetName();

            if (!isset($mappingsByTarget[$targetName])) {
                $mappingsByTarget[$targetName] = array();

                if ($this->targetManager->hasTarget($targetName)) {
                    $targets[$targetName] = $this->targetManager->getTarget($targetName);
                } else {
                    $nonExistingTargets[$targetName] = true;
                }
            }

            $mappingsByTarget[$targetName][] = $mapping;
        }

        if (!$mappingsByTarget) {
            $io->writeLine('No assets are mapped. Use "puli asset map <path> <web-path>" to map assets.');

            return 0;
        }

        if (count($targets) > 0) {
            $io->writeLine('The following web assets are currently enabled:');
            $io->writeLine('');

            foreach ($targets as $targetName => $target) {
                $targetTitle = 'Target <bu>'.$targetName.'</bu>';

                if ($targetName === InstallTarget::DEFAULT_TARGET) {
                    $targetTitle .= ' (alias of: <bu>'.$target->getName().'</bu>)';
                }

                $io->writeLine("    <b>$targetTitle</b>");
                $io->writeLine("    Location:   <c2>{$target->getLocation()}</c2>");
                $io->writeLine("    Installer:  {$target->getInstallerName()}");
                $io->writeLine("    URL Format: <c1>{$target->getUrlFormat()}</c1>");
                $io->writeLine('');

                $this->printMappingTable($io, $mappingsByTarget[$targetName]);
                $io->writeLine('');
            }

            $io->writeLine('Use "puli asset install" to install the assets in their targets.');
        }

        if (count($targets) > 0 && count($nonExistingTargets) > 0) {
            $io->writeLine('');
        }

        if (count($nonExistingTargets) > 0) {
            $io->writeLine('The following web assets are disabled since their target does not exist.');
            $io->writeLine('');

            foreach ($nonExistingTargets as $targetName => $_) {
                $io->writeLine("    <b>Target <bu>$targetName</bu></b>");
                $io->writeLine('');

                $this->printMappingTable($io, $mappingsByTarget[$targetName], false);
                $io->writeLine('');
            }

            $io->writeLine('Use "puli target add <target> <location>" to add a target.');
        }

        return 0;
    }

    public function handleMap(Args $args)
    {
        $flags = $args->isOptionSet('force') ? AssetManager::IGNORE_TARGET_NOT_FOUND : 0;
        $path = Path::makeAbsolute($args->getArgument('path'), $this->currentPath);

        $this->assetManager->addAssetMapping(new AssetMapping(
            $path,
            $args->getOption('target'),
            $args->getArgument('web-path')
        ), $flags);

        return 0;
    }

    public function handleUpdate(Args $args)
    {
        $flags = $args->isOptionSet('force')
            ? AssetManager::OVERRIDE | AssetManager::IGNORE_TARGET_NOT_FOUND
            : AssetManager::OVERRIDE;
        $mappingToUpdate = $this->getMappingByUuidPrefix($args->getArgument('uuid'));
        $path = $mappingToUpdate->getGlob();
        $webPath = $mappingToUpdate->getWebPath();
        $targetName = $mappingToUpdate->getTargetName();

        if ($args->isOptionSet('path')) {
            $path = Path::makeAbsolute($args->getOption('path'), $this->currentPath);
        }

        if ($args->isOptionSet('web-path')) {
            $webPath = $args->getOption('web-path');
        }

        if ($args->isOptionSet('target')) {
            $targetName = $args->getOption('target');
        }

        $updatedMapping = new AssetMapping($path, $targetName, $webPath, $mappingToUpdate->getUuid());

        if ($this->mappingsEqual($mappingToUpdate, $updatedMapping)) {
            throw new RuntimeException('Nothing to update.');
        }

        $this->assetManager->addAssetMapping($updatedMapping, $flags);

        return 0;
    }

    public function handleRemove(Args $args)
    {
        $mapping = $this->getMappingByUuidPrefix($args->getArgument('uuid'));

        $this->assetManager->removeAssetMapping($mapping->getUuid());

        return 0;
    }

    public function handleInstall(Args $args, IO $io)
    {
        if ($args->isArgumentSet('target')) {
            $expr = Expr::same($args->getArgument('target'), AssetMapping::TARGET_NAME);
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

    /**
     * @param IO             $io
     * @param AssetMapping[] $mappings
     * @param bool           $enabled
     */
    private function printMappingTable(IO $io, array $mappings, $enabled = true)
    {
        $table = new Table(TableStyle::borderless());

        $globTag = $enabled ? 'c1' : 'bad';
        $pathTag = $enabled ? 'c2' : 'bad';

        foreach ($mappings as $mapping) {
            $uuid = substr($mapping->getUuid()->toString(), 0, 6);
            $glob = $mapping->getGlob();
            $webPath = $mapping->getWebPath();

            if (!$enabled) {
                $uuid = "<bad>$uuid</bad>";
            }

            $table->addRow(array(
                $uuid,
                "<$globTag>$glob</$globTag>",
                "<$pathTag>$webPath</$pathTag>",
            ));
        }

        $table->render($io, 8);
    }

    /**
     * @param string $uuidPrefix
     *
     * @return AssetMapping
     */
    private function getMappingByUuidPrefix($uuidPrefix)
    {
        $expr = Expr::startsWith($uuidPrefix, AssetMapping::UUID);

        $mappings = $this->assetManager->findAssetMappings($expr);

        if (!$mappings) {
            throw new RuntimeException(sprintf(
                'The mapping with the UUID prefix "%s" does not exist.',
                $uuidPrefix
            ));
        }

        if (count($mappings) > 1) {
            throw new RuntimeException(sprintf(
                'More than one mapping matches the UUID prefix "%s".',
                $uuidPrefix
            ));
        }

        return reset($mappings);
    }

    private function mappingsEqual(AssetMapping $mapping1, AssetMapping $mapping2)
    {
        if ($mapping1->getUuid() !== $mapping2->getUuid()) {
            return false;
        }

        if ($mapping1->getGlob() !== $mapping2->getGlob()) {
            return false;
        }

        if ($mapping1->getWebPath() !== $mapping2->getWebPath()) {
            return false;
        }

        if ($mapping1->getTargetName() !== $mapping2->getTargetName()) {
            return false;
        }

        return true;
    }
}
