<?php

/*
 * This file is part of the puli/web-resource-plugin package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\WebResourcePlugin\Tests\Api\Target;

use PHPUnit_Framework_TestCase;
use Puli\WebResourcePlugin\Api\Installation\Installer\InstallerDescriptor;
use Puli\WebResourcePlugin\Api\Target\InstallTarget;
use Puli\WebResourcePlugin\Api\Target\InstallTargetCollection;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class InstallTargetCollectionTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var InstallTargetCollection
     */
    private $collection;

    /**
     * @var InstallTarget
     */
    private $target1;

    /**
     * @var InstallTarget
     */
    private $target2;

    /**
     * @var InstallTarget
     */
    private $target3;

    protected function setUp()
    {
        $this->collection = new InstallTargetCollection();
        $this->target1 = new InstallTarget('target1', 'symlink', 'web');
        $this->target2 = new InstallTarget('target2', 'rsync', 'ssh://my.cdn.com', 'http://my.cdn.com/%s');
        $this->target3 = new InstallTarget('target3', 'ftp', 'ftp://example.com/assets', 'http://example.com/assets/%s');
    }

    public function testCreate()
    {
        $collection = new InstallTargetCollection(array($this->target1, $this->target2));

        $this->assertSame(array(
            'target1' => $this->target1,
            'target2' => $this->target2,
        ), $collection->toArray());
    }

    public function testCreateEmpty()
    {
        $collection = new InstallTargetCollection();

        $this->assertSame(array(), $collection->toArray());
    }

    public function testAdd()
    {
        $this->collection->add($this->target1);
        $this->collection->add($this->target2);

        $this->assertSame(array(
            'target1' => $this->target1,
            'target2' => $this->target2,
        ), $this->collection->toArray());
    }

    public function testAddIgnoresDuplicates()
    {
        $this->collection->add($this->target1);
        $this->collection->add($this->target1);

        $this->assertSame(array(
            'target1' => $this->target1,
        ), $this->collection->toArray());
    }

    public function testMerge()
    {
        $this->collection->add($this->target1);
        $this->collection->merge(array($this->target2, $this->target3));

        $this->assertSame(array(
            'target1' => $this->target1,
            'target2' => $this->target2,
            'target3' => $this->target3,
        ), $this->collection->toArray());
    }

    public function testReplace()
    {
        $this->collection->add($this->target1);
        $this->collection->replace(array($this->target2, $this->target3));

        $this->assertSame(array(
            'target2' => $this->target2,
            'target3' => $this->target3,
        ), $this->collection->toArray());
    }

    public function testRemove()
    {
        $this->collection->add($this->target1);
        $this->collection->add($this->target2);
        $this->collection->remove('target1');

        $this->assertSame(array(
            'target2' => $this->target2,
        ), $this->collection->toArray());
    }

    public function testRemoveIgnoresNonExisting()
    {
        $this->collection->add($this->target1);
        $this->collection->add($this->target2);
        $this->collection->remove('foobar');

        $this->assertSame(array(
            'target1' => $this->target1,
            'target2' => $this->target2,
        ), $this->collection->toArray());
    }

    public function testClear()
    {
        $this->collection->add($this->target1);
        $this->collection->add($this->target2);
        $this->collection->clear();

        $this->assertSame(array(), $this->collection->toArray());
    }

    public function testGet()
    {
        $this->collection->add($this->target1);
        $this->collection->add($this->target2);

        $this->assertSame($this->target1, $this->collection->get('target1'));
        $this->assertSame($this->target2, $this->collection->get('target2'));
    }

    /**
     * @expectedException \Puli\WebResourcePlugin\Api\Target\NoSuchTargetException
     * @expectedExceptionMessage foobar
     */
    public function testGetFailsIfNotFound()
    {
        $this->collection->get('foobar');
    }

    public function testContains()
    {
        $this->collection->add($this->target1);

        $this->assertTrue($this->collection->contains('target1'));
        $this->assertFalse($this->collection->contains('foobar'));
    }

    public function testIsEmpty()
    {
        $this->assertTrue($this->collection->isEmpty());
        $this->collection->add($this->target1);
        $this->assertFalse($this->collection->isEmpty());
        $this->collection->clear();
        $this->assertTrue($this->collection->isEmpty());
    }

    public function testCount()
    {
        $this->assertSame(0, $this->collection->count());
        $this->collection->add($this->target1);
        $this->assertSame(1, $this->collection->count());
        $this->collection->add($this->target2);
        $this->assertSame(2, $this->collection->count());
    }

    public function testIterate()
    {
        $this->collection->add($this->target1);
        $this->collection->add($this->target2);

        $this->assertSame(array(
            'target1' => $this->target1,
            'target2' => $this->target2,
        ), iterator_to_array($this->collection));
    }

    public function testArrayAccess()
    {
        $this->collection[] = $this->target1;
        $this->collection[] = $this->target2;

        $this->assertSame(array(
            'target1' => $this->target1,
            'target2' => $this->target2,
        ), $this->collection->toArray());
        $this->assertSame($this->target1, $this->collection['target1']);
        $this->assertSame($this->target2, $this->collection['target2']);
        $this->assertTrue(isset($this->collection['target1']));
        $this->assertFalse(isset($this->collection['foobar']));

        unset($this->collection['target2']);
        unset($this->collection['foobar']);

        $this->assertSame(array(
            'target1' => $this->target1,
        ), $this->collection->toArray());
    }

    /**
     * @expectedException \LogicException
     */
    public function testArrayAccessFailsIfKeyIsPassed()
    {
        $this->collection['key'] = $this->target1;
    }

    public function testGetTargetNames()
    {
        $this->collection->add($this->target1);
        $this->collection->add($this->target2);

        $this->assertSame(array('target1', 'target2'), $this->collection->getTargetNames());
    }

    public function testAddSetsDefaultTarget()
    {
        $this->collection->add($this->target1);
        $this->collection->add($this->target2);

        $this->assertSame($this->target1, $this->collection->getDefaultTarget());
    }

    public function testRemoveUpdatesDefaultTarget()
    {
        $this->collection->add($this->target1);
        $this->collection->add($this->target2);
        $this->collection->remove('target1');

        $this->assertSame($this->target2, $this->collection->getDefaultTarget());

        $this->collection->remove('target2');
        $this->collection->add($this->target1);

        $this->assertSame($this->target1, $this->collection->getDefaultTarget());
    }

    public function testClearResetsDefaultTarget()
    {
        $this->collection->add($this->target1);
        $this->collection->add($this->target2);
        $this->collection->clear();
        $this->collection->add($this->target2);

        $this->assertSame($this->target2, $this->collection->getDefaultTarget());
    }

    public function testSetDefaultTarget()
    {
        $this->collection->add($this->target1);
        $this->collection->add($this->target2);
        $this->collection->setDefaultTarget('target2');

        $this->assertSame($this->target2, $this->collection->getDefaultTarget());
    }

    /**
     * @expectedException \Puli\WebResourcePlugin\Api\Target\NoSuchTargetException
     * @expectedExceptionMessage foobar
     */
    public function testSetDefaultTargetFailsIfNotFound()
    {
        $this->collection->setDefaultTarget('foobar');
    }

    /**
     * @expectedException \Puli\WebResourcePlugin\Api\Target\NoSuchTargetException
     */
    public function testGetDefaultTargetFailsIfEmpty()
    {
        $this->collection->getDefaultTarget();
    }

    public function testGetWithDefaultTarget()
    {
        $this->collection->add($this->target1);
        $this->collection->add($this->target2);

        $this->assertSame($this->target1, $this->collection->get(InstallTarget::DEFAULT_TARGET));

        $this->collection->setDefaultTarget('target2');

        $this->assertSame($this->target2, $this->collection->get(InstallTarget::DEFAULT_TARGET));
    }

    public function testRemoveWithDefaultTarget()
    {
        $this->collection->add($this->target1);
        $this->collection->add($this->target2);

        $this->collection->remove(InstallTarget::DEFAULT_TARGET);

        $this->assertSame($this->target2, $this->collection->get(InstallTarget::DEFAULT_TARGET));
    }

    public function testContainsWithDefaultTarget()
    {
        $this->assertFalse($this->collection->contains(InstallTarget::DEFAULT_TARGET));
        $this->collection->add($this->target1);
        $this->assertTrue($this->collection->contains(InstallTarget::DEFAULT_TARGET));
    }
}
