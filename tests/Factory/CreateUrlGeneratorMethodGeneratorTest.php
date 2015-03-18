<?php

/*
 * This file is part of the puli/web-resource-plugin package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\WebResourcePlugin\Tests\Factory;

use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;
use Puli\Manager\Api\Php\Clazz;
use Puli\Manager\Api\Php\Import;
use Puli\Manager\Php\ClassWriter;
use Puli\WebResourcePlugin\Api\Target\InstallTarget;
use Puli\WebResourcePlugin\Api\Target\InstallTargetCollection;
use Puli\WebResourcePlugin\Api\Target\InstallTargetManager;
use Puli\WebResourcePlugin\Factory\CreateUrlGeneratorMethodGenerator;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class CreateUrlGeneratorMethodGeneratorTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    private $tempFile;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|InstallTargetManager
     */
    private $targetManager;

    /**
     * @var CreateUrlGeneratorMethodGenerator
     */
    private $generator;

    protected function setUp()
    {
        $this->tempFile = tempnam(sys_get_temp_dir(), 'puli-web-plugin');
        $this->targetManager = $this->getMock('Puli\WebResourcePlugin\Api\Target\InstallTargetManager');
        $this->generator = new CreateUrlGeneratorMethodGenerator($this->targetManager);
    }

    public function testAddCreateUrlGeneratorMethod()
    {
        $targets = new InstallTargetCollection(array(
            new InstallTarget('local', 'symlink', 'public_html', '/%s'),
            new InstallTarget('remote', 'rsync', 'ssh://example.com', 'http://example.com/%s', array(
                'user' => 'webmozart',
                'password' => 'password',
            )),
        ));

        $targets->setDefaultTarget('remote');

        $this->targetManager->expects($this->any())
            ->method('getTargets')
            ->willReturn($targets);

        $class = new Clazz('Puli\MyFactory');
        $class->addImport(new Import('Puli\Factory\PuliFactory'));
        $class->addImplementedInterface('PuliFactory');
        $class->setFilePath($this->tempFile);

        $this->generator->addCreateUrlGeneratorMethod($class);

        $writer = new ClassWriter();
        $writer->writeClass($class);

        $expected = <<<EOF
<?php

namespace Puli;

use Puli\Discovery\Api\ResourceDiscovery;
use Puli\WebResourcePlugin\Api\Factory\PuliWebFactory;
use Puli\WebResourcePlugin\Api\Target\InstallTarget;
use Puli\WebResourcePlugin\Api\Target\InstallTargetCollection;
use Puli\WebResourcePlugin\Api\UrlGenerator\ResourceUrlGenerator;
use Puli\WebResourcePlugin\UrlGenerator\DiscoveryUrlGenerator;

class MyFactory implements PuliWebFactory
{
    /**
     * Creates the URL generator.
     *
     * @param ResourceDiscovery \$discovery The resource discovery to read from.
     *
     * @return ResourceUrlGenerator The created URL generator.
     */
    public function createUrlGenerator(ResourceDiscovery \$discovery)
    {
        \$targets = new InstallTargetCollection(array(
            new InstallTarget('local', 'symlink', 'public_html', '/%s', array()),
            new InstallTarget('remote', 'rsync', 'ssh://example.com', 'http://example.com/%s', array(
                'user' => 'webmozart',
                'password' => 'password',
            )),
        ));
        \$targets->setDefaultTarget('remote');
        \$generator = new DiscoveryUrlGenerator(\$discovery, \$targets);

        return \$generator;
    }
}

EOF;


        $this->assertSame($expected, file_get_contents($this->tempFile));
    }

    public function testAddCreateUrlGeneratorMethodWithoutTargets()
    {
        $targets = new InstallTargetCollection(array());

        $this->targetManager->expects($this->any())
            ->method('getTargets')
            ->willReturn($targets);

        $class = new Clazz('Puli\MyFactory');
        $class->addImport(new Import('Puli\Factory\PuliFactory'));
        $class->addImplementedInterface('PuliFactory');
        $class->setFilePath($this->tempFile);

        $this->generator->addCreateUrlGeneratorMethod($class);

        $writer = new ClassWriter();
        $writer->writeClass($class);

        $expected = <<<EOF
<?php

namespace Puli;

use Puli\Discovery\Api\ResourceDiscovery;
use Puli\WebResourcePlugin\Api\Factory\PuliWebFactory;
use Puli\WebResourcePlugin\Api\Target\InstallTargetCollection;
use Puli\WebResourcePlugin\Api\UrlGenerator\ResourceUrlGenerator;
use Puli\WebResourcePlugin\UrlGenerator\DiscoveryUrlGenerator;

class MyFactory implements PuliWebFactory
{
    /**
     * Creates the URL generator.
     *
     * @param ResourceDiscovery \$discovery The resource discovery to read from.
     *
     * @return ResourceUrlGenerator The created URL generator.
     */
    public function createUrlGenerator(ResourceDiscovery \$discovery)
    {
        \$targets = new InstallTargetCollection();
        \$generator = new DiscoveryUrlGenerator(\$discovery, \$targets);

        return \$generator;
    }
}

EOF;


        $this->assertSame($expected, file_get_contents($this->tempFile));
    }

}
