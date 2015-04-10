<?php

/*
 * This file is part of the puli/asset-plugin package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\AssetPlugin\Tests\Asset;

use PHPUnit_Framework_TestCase;
use Puli\AssetPlugin\Api\Asset\AssetMapping;
use Puli\AssetPlugin\Api\AssetPlugin;
use Puli\AssetPlugin\Asset\BindingExpressionBuilder;
use Puli\Manager\Api\Discovery\BindingDescriptor;
use Puli\Manager\Api\Discovery\BindingState;
use Webmozart\Expression\Expr;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class BindingExpressionBuilderTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var BindingExpressionBuilder
     */
    private $builder;

    protected function setUp()
    {
        $this->builder = new BindingExpressionBuilder();
    }

    public function testBuildDefaultExpression()
    {
        $expr = Expr::same(BindingState::ENABLED, BindingDescriptor::STATE)
            ->andSame(AssetPlugin::BINDING_TYPE, BindingDescriptor::TYPE_NAME)
            ->andEndsWith('{,/**/*}', BindingDescriptor::QUERY);

        $this->assertEquals($expr, $this->builder->buildExpression());
    }

    public function testBuildExpressionWithCustomCriteria()
    {
        $expr1 = Expr::startsWith('abcd', AssetMapping::UUID)
            ->orSame('local', AssetMapping::TARGET_NAME)
            ->orX(
                Expr::same('/path', AssetMapping::GLOB)
                    ->andSame('css', AssetMapping::WEB_PATH)
            );

        $expr2 = Expr::same(BindingState::ENABLED, BindingDescriptor::STATE)
            ->andSame(AssetPlugin::BINDING_TYPE, BindingDescriptor::TYPE_NAME)
            ->andEndsWith('{,/**/*}', BindingDescriptor::QUERY)
            ->andX(
                Expr::startsWith('abcd', BindingDescriptor::UUID)
                    ->orKey(BindingDescriptor::PARAMETER_VALUES, Expr::key(AssetPlugin::TARGET_PARAMETER, Expr::same('local')))
                    ->orX(
                        Expr::same('/path{,/**/*}', BindingDescriptor::QUERY)
                            ->andKey(BindingDescriptor::PARAMETER_VALUES, Expr::key(AssetPlugin::PATH_PARAMETER, Expr::same('css')))
                    )
            );

        $this->assertEquals($expr2, $this->builder->buildExpression($expr1));
    }

    public function testAppendDefaultQuerySuffixForSame()
    {
        $expr1 = Expr::same('/path', AssetMapping::GLOB);

        $expr2 = Expr::same(BindingState::ENABLED, BindingDescriptor::STATE)
            ->andSame(AssetPlugin::BINDING_TYPE, BindingDescriptor::TYPE_NAME)
            ->andEndsWith('{,/**/*}', BindingDescriptor::QUERY)
            ->andSame('/path{,/**/*}', BindingDescriptor::QUERY);

        $this->assertEquals($expr2, $this->builder->buildExpression($expr1));
    }

    public function testAppendDefaultQuerySuffixForEquals()
    {
        $expr1 = Expr::equals('/path', AssetMapping::GLOB);

        $expr2 = Expr::same(BindingState::ENABLED, BindingDescriptor::STATE)
            ->andSame(AssetPlugin::BINDING_TYPE, BindingDescriptor::TYPE_NAME)
            ->andEndsWith('{,/**/*}', BindingDescriptor::QUERY)
            ->andEquals('/path{,/**/*}', BindingDescriptor::QUERY);

        $this->assertEquals($expr2, $this->builder->buildExpression($expr1));
    }

    public function testAppendDefaultQuerySuffixForNotSame()
    {
        $expr1 = Expr::notSame('/path', AssetMapping::GLOB);

        $expr2 = Expr::same(BindingState::ENABLED, BindingDescriptor::STATE)
            ->andSame(AssetPlugin::BINDING_TYPE, BindingDescriptor::TYPE_NAME)
            ->andEndsWith('{,/**/*}', BindingDescriptor::QUERY)
            ->andNotSame('/path{,/**/*}', BindingDescriptor::QUERY);

        $this->assertEquals($expr2, $this->builder->buildExpression($expr1));
    }

    public function testAppendDefaultQuerySuffixForNotEquals()
    {
        $expr1 = Expr::notEquals('/path', AssetMapping::GLOB);

        $expr2 = Expr::same(BindingState::ENABLED, BindingDescriptor::STATE)
            ->andSame(AssetPlugin::BINDING_TYPE, BindingDescriptor::TYPE_NAME)
            ->andEndsWith('{,/**/*}', BindingDescriptor::QUERY)
            ->andNotEquals('/path{,/**/*}', BindingDescriptor::QUERY);

        $this->assertEquals($expr2, $this->builder->buildExpression($expr1));
    }

    public function testAppendDefaultQuerySuffixForEndsWith()
    {
        $expr1 = Expr::endsWith('.css', AssetMapping::GLOB);

        $expr2 = Expr::same(BindingState::ENABLED, BindingDescriptor::STATE)
            ->andSame(AssetPlugin::BINDING_TYPE, BindingDescriptor::TYPE_NAME)
            ->andEndsWith('{,/**/*}', BindingDescriptor::QUERY)
            ->andEndsWith('.css{,/**/*}', BindingDescriptor::QUERY);

        $this->assertEquals($expr2, $this->builder->buildExpression($expr1));
    }
}
