<?php

/*
 * This file is part of the puli/web-resource-plugin package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\WebResourcePlugin\Console;

use Puli\WebResourcePlugin\Api\Installation\InstallationManager;
use Puli\WebResourcePlugin\Api\Installation\InstallationParams;
use Puli\WebResourcePlugin\Api\Target\InstallTarget;
use Puli\WebResourcePlugin\Api\Target\InstallTargetManager;
use Puli\WebResourcePlugin\Api\WebPath\WebPathManager;
use Puli\WebResourcePlugin\Api\WebPath\WebPathMapping;
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
class WebCommandHandler
{
    /**
     * @var WebPathManager
     */
    private $webPathManager;

    /**
     * @var InstallationManager
     */
    private $installationManager;

    /**
     * @var InstallTargetManager
     */
    private $targetManager;

    public function __construct(WebPathManager $webPathManager, InstallationManager $installationManager, InstallTargetManager $targetManager)
    {
        $this->webPathManager = $webPathManager;
        $this->installationManager = $installationManager;
        $this->targetManager = $targetManager;
    }

    public function handleList(Args $args, IO $io)
    {
        /** @var WebPathMapping[][] $mappingsByTarget */
        $mappingsByTarget = array();

        /** @var InstallTarget[] $targets */
        $targets = array();

        // Assemble mappings and validate targets
        foreach ($this->webPathManager->getWebPathMappings() as $mapping) {
            $targetName = $mapping->getTargetName();

            if (!isset($mappingsByTarget[$targetName])) {
                $mappingsByTarget[$targetName] = array();
                $targets[$targetName] = $this->targetManager->getTarget($targetName);
            }

            $mappingsByTarget[$targetName][] = $mapping;
        }

        if (!$mappingsByTarget) {
            $io->writeLine('No web resources. Use "puli web add <path> <web-path>" to map web resources.');

            return 0;
        }

        $io->writeLine('The following web resources are currently enabled:');
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

        $io->writeLine('Use "puli web install" to install the resources in their targets.');

        return 0;
    }

    public function handleAdd(Args $args)
    {
        $this->webPathManager->addWebPathMapping(new WebPathMapping(
            $args->getArgument('path'),
            $args->getOption('target'),
            $args->getArgument('web-path')
        ));

        return 0;
    }

    public function handleRemove(Args $args)
    {
        $expr = Expr::startsWith(WebPathMapping::UUID, $args->getArgument('uuid'));

        $mappings = $this->webPathManager->findWebPathMappings($expr);

        if (!$mappings) {
            throw new RuntimeException(sprintf(
                'The mapping with the UUID (prefix) "%s" does not exist.',
                $args->getArgument('uuid')
            ));
        }

        foreach ($mappings as $mapping) {
            $this->webPathManager->removeWebPathMapping($mapping->getUuid());
        }

        return 0;
    }

    public function handleInstall(Args $args, IO $io)
    {
        if ($args->isArgumentSet('target')) {
            $expr = Expr::same(WebPathMapping::TARGET_NAME, $args->getArgument('target'));
            $mappings = $this->webPathManager->findWebPathMappings($expr);
        } else {
            $mappings = $this->webPathManager->getWebPathMappings();
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
