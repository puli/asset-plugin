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

use Puli\Cli\Util\StringUtil;
use Puli\WebResourcePlugin\Api\Installer\InstallerDescriptor;
use Puli\WebResourcePlugin\Api\Installer\InstallerManager;
use RuntimeException;
use Webmozart\Console\Api\Args\Args;
use Webmozart\Console\Api\IO\IO;
use Webmozart\Console\UI\Component\Table;
use Webmozart\Console\UI\Style\TableStyle;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class InstallerCommandHandler
{
    /**
     * @var InstallerManager
     */
    private $installerManager;

    public function __construct(InstallerManager $installerManager)
    {
        $this->installerManager = $installerManager;
    }

    public function handleList(Args $args, IO $io)
    {
        $table = new Table(TableStyle::borderless());

        foreach ($this->installerManager->getInstallerDescriptors() as $descriptor) {
            $className = $descriptor->getClassName();

            if (!$args->isOptionSet('long')) {
                $className = StringUtil::getShortClassName($className);
            }

            $table->addRow(array(
                '<u>'.$descriptor->getName().'</u>',
                '<em>'.$className.'</em>',
                $descriptor->getDescription()
            ));
        }

        $table->render($io);

        return 0;
    }

    public function handleAdd(Args $args)
    {
        $this->installerManager->addInstallerDescriptor(new InstallerDescriptor(
            $args->getArgument('name'),
            $args->getArgument('class'),
            $args->getOption('description')
        ));

        return 0;
    }

    public function handleRemove(Args $args)
    {
        $installerName = $args->getArgument('name');

        if (!$this->installerManager->hasInstallerDescriptor($installerName)) {
            throw new RuntimeException(sprintf(
                'The installer "%s" does not exist.',
                $installerName
            ));
        }

        $this->installerManager->removeInstallerDescriptor($installerName);

        return 0;
    }
}
