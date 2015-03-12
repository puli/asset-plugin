<?php

/*
 * This file is part of the puli/web-resource-plugin package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\WebResourcePlugin\Tests\Installation\Installer;

use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;
use Puli\RepositoryManager\Api\Package\Package;
use Puli\RepositoryManager\Api\Package\PackageCollection;
use Puli\RepositoryManager\Api\Package\PackageFile;
use Puli\RepositoryManager\Api\Package\RootPackage;
use Puli\RepositoryManager\Api\Package\RootPackageFile;
use Puli\RepositoryManager\Api\Package\RootPackageFileManager;
use Puli\RepositoryManager\Tests\TestException;
use Puli\WebResourcePlugin\Api\Installation\Installer\InstallerDescriptor;
use Puli\WebResourcePlugin\Api\Installation\Installer\InstallerParameter;
use Puli\WebResourcePlugin\Api\WebResourcePlugin;
use Puli\WebResourcePlugin\Installation\Installer\PackageFileInstallerManager;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class PackageFileInstallerManagerUnloadedTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var RootPackageFile
     */
    protected $rootPackageFile;

    /**
     * @var PackageFile
     */
    protected $packageFile1;

    /**
     * @var PackageFile
     */
    protected $packageFile2;

    /**
     * @var RootPackage
     */
    protected $rootPackage;

    /**
     * @var Package
     */
    protected $package1;

    /**
     * @var Package
     */
    protected $package2;

    /**
     * @var PackageCollection
     */
    protected $packages;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|RootPackageFileManager
     */
    protected $packageFileManager;

    /**
     * @var PackageFileInstallerManager
     */
    protected $manager;

    protected function setUp()
    {
        $this->packageFileManager = $this->getMock('Puli\RepositoryManager\Api\Package\RootPackageFileManager');
        $this->rootPackageFile = new RootPackageFile('vendor/root');
        $this->packageFile1 = new PackageFile('vendor/package1');
        $this->packageFile2 = new PackageFile('vendor/package2');
        $this->rootPackage = new RootPackage($this->rootPackageFile, '/path');
        $this->package1 = new Package($this->packageFile1, '/path');
        $this->package2 = new Package($this->packageFile2, '/path');
        $this->packages = new PackageCollection(array(
            $this->rootPackage,
            $this->package1,
            $this->package2,
        ));
        $this->manager = new PackageFileInstallerManager($this->packageFileManager, $this->packages);
    }

    public function testGetInstallerDescriptor()
    {
        $this->populateDefaultManager();

        $descriptor = new InstallerDescriptor('symlink', 'SymlinkInstaller');

        $this->assertEquals($descriptor, $this->manager->getInstallerDescriptor('symlink'));
    }

    /**
     * @expectedException \Puli\WebResourcePlugin\Api\Installation\Installer\NoSuchInstallerException
     * @expectedExceptionMessage foobar
     */
    public function testGetInstallerDescriptorFailsIfNotFound()
    {
        $this->manager->getInstallerDescriptor('foobar');
    }

    /**
     * @expectedException \Webmozart\Json\ValidationFailedException
     */
    public function testGetInstallerDescriptorFailsIfJsonIsInvalid()
    {
        $this->packageFile1->setExtraKey(WebResourcePlugin::INSTALLERS_KEY, array(
            (object) array(
                'name' => 'symlink',
                'class' => 'Package1SymlinkInstaller',
            )
        ));

        $this->manager->getInstallerDescriptor('symlink');
    }

    public function testGetInstallerDescriptorLoadsFullyConfiguredInstaller()
    {
        $this->rootPackageFile->setExtraKey(WebResourcePlugin::INSTALLERS_KEY, (object) array(
            'symlink' => (object) array(
                'class' => 'SymlinkInstaller',
                'description' => 'The description',
                'parameters' => (object) array(
                    'required' => (object) array(
                        'required' => true,
                        'description' => 'The parameter description 1',
                    ),
                    'optional' => (object) array(
                        'description' => 'The parameter description 2',
                    ),
                    'optional-with-default' => (object) array(
                        'default' => 'foobar',
                    ),
                    'optional-empty' => (object) array(),
                )
            )
        ));

        $descriptor = new InstallerDescriptor('symlink', 'SymlinkInstaller', 'The description', array(
            new InstallerParameter('required', InstallerParameter::REQUIRED, null, 'The parameter description 1'),
            new InstallerParameter('optional', InstallerParameter::OPTIONAL, null, 'The parameter description 2'),
            new InstallerParameter('optional-with-default', InstallerParameter::OPTIONAL, 'foobar'),
            new InstallerParameter('optional-empty'),
        ));

        $this->assertEquals($descriptor, $this->manager->getInstallerDescriptor('symlink'));
    }

    public function testGetInstallerDescriptors()
    {
        $this->populateDefaultManager();

        $descriptor1 = new InstallerDescriptor('symlink', 'SymlinkInstaller');
        $descriptor2 = new InstallerDescriptor('rsync', 'RsyncInstaller');

        $this->assertEquals(array(
            'symlink' => $descriptor1,
            'rsync' => $descriptor2,
        ), $this->manager->getInstallerDescriptors());
    }

    public function testHasInstallerDescriptor()
    {
        $this->populateDefaultManager();

        $this->assertTrue($this->manager->hasInstallerDescriptor('symlink'));
        $this->assertTrue($this->manager->hasInstallerDescriptor('rsync'));
        $this->assertFalse($this->manager->hasInstallerDescriptor('foobar'));
    }

    public function testHasInstallerDescriptors()
    {
        $this->populateDefaultManager();

        $this->assertTrue($this->manager->hasInstallerDescriptors());
    }

    public function testHasNoInstallerDescriptors()
    {
        $this->assertFalse($this->manager->hasInstallerDescriptors());
    }

    public function testAddInstallerDescriptor()
    {
        $this->populateDefaultManager();

        $this->packageFileManager->expects($this->once())
            ->method('setExtraKey')
            ->with(WebResourcePlugin::INSTALLERS_KEY, (object) array(
                'symlink' => (object) array(
                    'class' => 'SymlinkInstaller',
                ),
                'cdn' => (object) array(
                    'class' => 'CdnInstaller',
                ),
            ));

        $descriptor = new InstallerDescriptor('cdn', 'CdnInstaller');

        $this->manager->addInstallerDescriptor($descriptor);

        $this->assertSame($descriptor, $this->manager->getInstallerDescriptor('cdn'));
    }

    public function testAddFullyConfiguredInstallerDescriptor()
    {
        $this->populateDefaultManager();

        $this->packageFileManager->expects($this->once())
            ->method('setExtraKey')
            ->with(WebResourcePlugin::INSTALLERS_KEY, (object) array(
                'symlink' => (object) array(
                    'class' => 'SymlinkInstaller',
                ),
                'cdn' => (object) array(
                    'class' => 'CdnInstaller',
                    'description' => 'The description',
                    'parameters' => (object) array(
                        'required' => (object) array(
                            'required' => true,
                            'description' => 'The parameter description 1',
                        ),
                        'optional' => (object) array(
                            'description' => 'The parameter description 2',
                        ),
                        'optional-with-default' => (object) array(
                            'default' => 'foobar',
                        ),
                        'optional-empty' => (object) array(),
                    )
                )
            ));

        $descriptor = new InstallerDescriptor('cdn', 'CdnInstaller', 'The description', array(
            new InstallerParameter('required', InstallerParameter::REQUIRED, null, 'The parameter description 1'),
            new InstallerParameter('optional', InstallerParameter::OPTIONAL, null, 'The parameter description 2'),
            new InstallerParameter('optional-with-default', InstallerParameter::OPTIONAL, 'foobar'),
            new InstallerParameter('optional-empty'),
        ));

        $this->manager->addInstallerDescriptor($descriptor);

        $this->assertSame($descriptor, $this->manager->getInstallerDescriptor('cdn'));
    }

    public function testAddInstallerDescriptorOverridesPreviousRootInstaller()
    {
        $this->rootPackageFile->setExtraKey(WebResourcePlugin::INSTALLERS_KEY, (object) array(
            'symlink' => (object) array(
                'class' => 'PreviousInstaller',
            )
        ));

        $this->packageFileManager->expects($this->once())
            ->method('setExtraKey')
            ->with(WebResourcePlugin::INSTALLERS_KEY, (object) array(
                'symlink' => (object) array(
                    'class' => 'NewInstaller',
                ),
            ));

        $descriptor = new InstallerDescriptor('symlink', 'NewInstaller');

        $this->manager->addInstallerDescriptor($descriptor);

        $this->assertSame($descriptor, $this->manager->getInstallerDescriptor('symlink'));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testAddInstallerDescriptorFailsIfInstallerExistsInOtherPackage()
    {
        $this->packageFile1->setExtraKey(WebResourcePlugin::INSTALLERS_KEY, (object) array(
            'symlink' => (object) array(
                'class' => 'PreviousInstaller',
            )
        ));

        $this->packageFileManager->expects($this->never())
            ->method('setExtraKey');

        $descriptor = new InstallerDescriptor('symlink', 'NewInstaller');

        $this->manager->addInstallerDescriptor($descriptor);
    }

    public function testAddInstallerDescriptorRestoresPreviousInstallerIfSavingFails()
    {
        $this->rootPackageFile->setExtraKey(WebResourcePlugin::INSTALLERS_KEY, (object) array(
            'symlink' => (object) array(
                'class' => 'PreviousInstaller',
            )
        ));

        // The new installer should be saved in the root package
        $this->packageFileManager->expects($this->once())
            ->method('setExtraKey')
            ->with(WebResourcePlugin::INSTALLERS_KEY)
            ->willThrowException(new TestException());

        $previousDescriptor = new InstallerDescriptor('symlink', 'PreviousInstaller');
        $newDescriptor = new InstallerDescriptor('symlink', 'NewInstaller');

        try {
            $this->manager->addInstallerDescriptor($newDescriptor);
            $this->fail('Expected a TestException');
        } catch (TestException $e) {
        }

        $this->assertEquals($previousDescriptor, $this->manager->getInstallerDescriptor('symlink'));
    }

    public function testAddInstallerDescriptorRemovesNewInstallerIfSavingFails()
    {
        // The new installer should be saved in the root package
        $this->packageFileManager->expects($this->once())
            ->method('setExtraKey')
            ->with(WebResourcePlugin::INSTALLERS_KEY)
            ->willThrowException(new TestException());

        $newDescriptor = new InstallerDescriptor('symlink', 'NewInstaller');

        try {
            $this->manager->addInstallerDescriptor($newDescriptor);
            $this->fail('Expected a TestException');
        } catch (TestException $e) {
        }

        $this->assertFalse($this->manager->hasInstallerDescriptor('symlink'));
    }

    public function testRemoveInstallerDescriptor()
    {
        $this->rootPackageFile->setExtraKey(WebResourcePlugin::INSTALLERS_KEY, (object) array(
            'symlink' => (object) array(
                'class' => 'SymlinkInstaller',
            ),
            'cdn' => (object) array(
                'class' => 'CdnInstaller',
            ),
        ));

        $this->packageFileManager->expects($this->once())
            ->method('setExtraKey')
            ->with(WebResourcePlugin::INSTALLERS_KEY, (object) array(
                'cdn' => (object) array(
                    'class' => 'CdnInstaller',
                ),
            ));

        $this->manager->removeInstallerDescriptor('symlink');

        $this->assertTrue($this->manager->hasInstallerDescriptor('cdn'));
        $this->assertFalse($this->manager->hasInstallerDescriptor('symlink'));
    }

    public function testRemoveInstallerDescriptorRemovesExtraKeyAfterLastInstaller()
    {
        $this->rootPackageFile->setExtraKey(WebResourcePlugin::INSTALLERS_KEY, (object) array(
            'symlink' => (object) array(
                'class' => 'SymlinkInstaller',
            ),
        ));

        $this->packageFileManager->expects($this->once())
            ->method('removeExtraKey')
            ->with(WebResourcePlugin::INSTALLERS_KEY);

        $this->manager->removeInstallerDescriptor('symlink');

        $this->assertFalse($this->manager->hasInstallerDescriptor('symlink'));
    }

    public function testRemoveInstallerDescriptorRestoresPreviousInstallerIfSavingFails()
    {
        $this->rootPackageFile->setExtraKey(WebResourcePlugin::INSTALLERS_KEY, (object) array(
            'symlink' => (object) array(
                'class' => 'PreviousInstaller',
            )
        ));

        // The new installer should be saved in the root package
        $this->packageFileManager->expects($this->once())
            ->method('removeExtraKey')
            ->with(WebResourcePlugin::INSTALLERS_KEY)
            ->willThrowException(new TestException());

        $previousDescriptor = new InstallerDescriptor('symlink', 'PreviousInstaller');

        try {
            $this->manager->removeInstallerDescriptor('symlink');
            $this->fail('Expected a TestException');
        } catch (TestException $e) {
        }

        $this->assertEquals($previousDescriptor, $this->manager->getInstallerDescriptor('symlink'));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testRemoveInstallerDescriptorFailsIfInstallerNotInRoot()
    {
        $this->packageFile1->setExtraKey(WebResourcePlugin::INSTALLERS_KEY, (object) array(
            'symlink' => (object) array(
                'class' => 'SymlinkInstaller',
            ),
        ));

        $this->packageFileManager->expects($this->never())
            ->method('setExtraKey');

        $this->manager->removeInstallerDescriptor('symlink');
    }

    public function testRemoveInstallerDescriptorDoesNothingIfNotFound()
    {
        $this->populateDefaultManager();

        $this->packageFileManager->expects($this->never())
            ->method('setExtraKey');

        $this->manager->removeInstallerDescriptor('foobar');
    }

    protected function populateDefaultManager()
    {
        $this->rootPackageFile->setExtraKey(WebResourcePlugin::INSTALLERS_KEY, (object) array(
            'symlink' => (object) array(
                'class' => 'SymlinkInstaller',
            )
        ));
        $this->packageFile1->setExtraKey(WebResourcePlugin::INSTALLERS_KEY, (object) array(
            'rsync' => (object) array(
                'class' => 'RsyncInstaller',
            )
        ));
    }
}
