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

use Puli\WebResourcePlugin\Api\Target\InstallTarget;
use Puli\WebResourcePlugin\Api\Target\InstallTargetManager;
use Puli\WebResourcePlugin\Api\Target\NoSuchTargetException;
use Webmozart\Console\Api\Args\Args;
use Webmozart\Console\Api\IO\IO;
use Webmozart\Console\UI\Component\Table;
use Webmozart\Console\UI\Style\TableStyle;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class TargetCommandHandler
{
    /**
     * @var InstallTargetManager
     */
    private $targetManager;

    public function __construct(InstallTargetManager $targetManager)
    {
        $this->targetManager = $targetManager;
    }

    public function handleList(Args $args, IO $io)
    {
        $table = new Table(TableStyle::borderless());

        $defaultTarget = $this->targetManager->hasTargets()
            ? $this->targetManager->getDefaultTarget()
            : null;

        foreach ($this->targetManager->getTargets() as $target) {
            $table->addRow(array(
                $defaultTarget === $target ? '*' : '',
                '<u>'.$target->getName().'</u>',
                $target->getInstallerName(),
                '<real-path>'.$target->getLocation().'</real-path>',
                '<em>'.$target->getUrlFormat().'</em>'
            ));
        }

        $table->render($io);

        return 0;
    }

    public function handleAdd(Args $args)
    {
        $this->targetManager->addTarget(new InstallTarget(
            $args->getArgument('name'),
            $args->getOption('installer'),
            $args->getArgument('location'),
            $args->getOption('url-format')
            // TODO parameters
        ));

        return 0;
    }

    public function handleRemove(Args $args)
    {
        $targetName = $args->getArgument('name');

        if (!$this->targetManager->hasTarget($targetName)) {
            throw NoSuchTargetException::forTargetName($targetName);
        }

        $this->targetManager->removeTarget($targetName);

        return 0;
    }

    public function handleSetDefault(Args $args)
    {
        $this->targetManager->setDefaultTarget($args->getArgument('name'));

        return 0;
    }
}
