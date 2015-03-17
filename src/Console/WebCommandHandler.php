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
use Puli\WebResourcePlugin\Api\WebPath\WebPathManager;
use Puli\WebResourcePlugin\Api\WebPath\WebPathMapping;
use Webmozart\Console\Api\Args\Args;
use Webmozart\Console\Api\IO\IO;
use Webmozart\Console\UI\Component\Table;
use Webmozart\Console\UI\Style\TableStyle;
use Webmozart\Expression\Expr;

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

    public function __construct(WebPathManager $webPathManager, InstallationManager $installationManager)
    {
        $this->webPathManager = $webPathManager;
        $this->installationManager = $installationManager;
    }

    public function handleList(Args $args, IO $io)
    {
        $table = new Table(TableStyle::borderless());

        foreach ($this->webPathManager->getWebPathMappings() as $mapping) {
            $glob = $mapping->getGlob();
            $webPath = $mapping->getWebPath();

            $table->addRow(array(
                substr($mapping->getUuid()->toString(), 0, 6),
                '<em>'.$glob.'</em>',
                '<u>'.$mapping->getTargetName().'</u>',
                '<real-path>'.$webPath.'</real-path>'
            ));
        }

        $table->render($io);

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

        /** @var InstallationParams[] $paramsToInstall */
        $paramsToInstall = array();

        // Prepare and validate the installation of all matching mappings
        foreach ($mappings as $mapping) {
            $paramsToInstall[] = $this->installationManager->prepareInstallation($mapping);
        }

        foreach ($paramsToInstall as $params) {
            foreach ($params->getResources() as $resource) {
                $webPath = rtrim($params->getTargetLocation(), '/').$params->getWebPath();

                $io->writeLine(sprintf(
                    'Installing <em>%s</em> in <real-path>%s</real-path> via <u>%s</u>...',
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
