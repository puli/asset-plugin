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

use Puli\AssetPlugin\Api\Target\InstallTarget;
use Puli\AssetPlugin\Api\Target\InstallTargetManager;
use Puli\AssetPlugin\Api\Target\NoSuchTargetException;
use Puli\Cli\Util\StringUtil;
use RuntimeException;
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
        $targets = $this->targetManager->getTargets();

        if ($targets->isEmpty()) {
            $io->writeLine('No install targets. Use "puli target add <name> <directory>" to add a target.');

            return 0;
        }

        $defaultTarget = $targets->getDefaultTarget();

        foreach ($targets as $target) {
            $parameters = '';

            foreach ($target->getParameterValues() as $name => $value) {
                $parameters .= "\n<c1>".$name.'='.StringUtil::formatValue($value).'</c1>';
            }

            $table->addRow(array(
                $defaultTarget === $target ? '*' : '',
                '<u>'.$target->getName().'</u>',
                $target->getInstallerName(),
                '<c2>'.$target->getLocation().'</c2>'.$parameters,
                '<c1>'.$target->getUrlFormat().'</c1>'
            ));
        }

        $table->render($io);

        return 0;
    }

    public function handleAdd(Args $args)
    {
        $parameters = array();

        foreach ($args->getOption('param') as $parameter) {
            $pos = strpos($parameter, '=');

            if (false === $pos) {
                throw new RuntimeException(sprintf(
                    'Invalid parameter "%s". Expected "<name>=<value>".',
                    $parameter
                ));
            }

            $parameters[substr($parameter, 0, $pos)] = StringUtil::parseValue(substr($parameter, $pos + 1));
        }

        $this->targetManager->addTarget(new InstallTarget(
            $args->getArgument('name'),
            $args->getOption('installer'),
            $args->getArgument('location'),
            $args->getOption('url-format'),
            $parameters
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

    public function handleGetDefault(Args $args, IO $io)
    {
        $io->writeLine($this->targetManager->getDefaultTarget()->getName());

        return 0;
    }
}
