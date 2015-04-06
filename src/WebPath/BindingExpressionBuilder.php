<?php

/*
 * This file is part of the puli/asset-plugin package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\AssetPlugin\WebPath;

use Puli\AssetPlugin\Api\AssetPlugin;
use Puli\AssetPlugin\Api\WebPath\WebPathMapping;
use Puli\Manager\Api\Discovery\BindingDescriptor;
use Puli\Manager\Api\Discovery\BindingState;
use Webmozart\Expression\Comparison\EndsWith;
use Webmozart\Expression\Comparison\Equals;
use Webmozart\Expression\Comparison\NotEquals;
use Webmozart\Expression\Comparison\NotSame;
use Webmozart\Expression\Comparison\Same;
use Webmozart\Expression\Expr;
use Webmozart\Expression\Expression;
use Webmozart\Expression\Key\Key;
use Webmozart\Expression\Logic\Conjunction;
use Webmozart\Expression\Traversal\ExpressionTraverser;
use Webmozart\Expression\Traversal\ExpressionVisitor;

/**
 * Transforms a {@link WebPathMapping} expression to a {@link BindingDescriptor}
 * expression.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 *
 * @internal
 */
class BindingExpressionBuilder implements ExpressionVisitor
{
    /**
     * @var Conjunction
     */
    private $defaultExpression;

    /**
     * Builds a {@link BindingDescriptor} expression for a given
     * {@link WebPathMapping} expression.
     *
     * @param Expression $expr The {@link WebPathMapping} expression.
     *
     * @return Expression The built expression.
     */
    public function buildExpression(Expression $expr = null)
    {
        if (!$this->defaultExpression) {
            $this->defaultExpression = Expr::same(BindingDescriptor::STATE, BindingState::ENABLED)
                ->andSame(BindingDescriptor::TYPE_NAME, AssetPlugin::BINDING_TYPE)
                ->andEndsWith(BindingDescriptor::QUERY, '{,/**}');
        }

        if (!$expr) {
            return $this->defaultExpression;
        }

        $traverser = new ExpressionTraverser();
        $traverser->addVisitor($this);

        return $this->defaultExpression->andX($traverser->traverse($expr));
    }

    /**
     * {@inheritdoc}
     */
    public function enterExpression(Expression $expr)
    {
        return $expr;
    }

    /**
     * {@inheritdoc}
     */
    public function leaveExpression(Expression $expr)
    {
        if ($expr instanceof Key) {
            switch ($expr->getKey()) {
                case WebPathMapping::UUID:
                    return new Key(BindingDescriptor::UUID, $expr->getExpression());

                case WebPathMapping::GLOB:
                    $queryExpr = $expr->getExpression();

                    if ($queryExpr instanceof Same) {
                        $queryExpr = new Same($queryExpr->getComparedValue().'{,/**}');
                    } elseif ($queryExpr instanceof Equals) {
                        $queryExpr = new Equals($queryExpr->getComparedValue().'{,/**}');
                    } elseif ($queryExpr instanceof NotSame) {
                        $queryExpr = new NotSame($queryExpr->getComparedValue().'{,/**}');
                    } elseif ($queryExpr instanceof NotEquals) {
                        $queryExpr = new NotEquals($queryExpr->getComparedValue().'{,/**}');
                    } elseif ($queryExpr instanceof EndsWith) {
                        $queryExpr = new EndsWith($queryExpr->getAcceptedSuffix().'{,/**}');
                    }

                    return new Key(BindingDescriptor::QUERY, $queryExpr);

                case WebPathMapping::TARGET_NAME:
                    return new Key(
                        BindingDescriptor::PARAMETER_VALUES,
                        new Key(AssetPlugin::TARGET_PARAMETER, $expr->getExpression())
                    );

                case WebPathMapping::WEB_PATH:
                    return new Key(
                        BindingDescriptor::PARAMETER_VALUES,
                        new Key(AssetPlugin::PATH_PARAMETER, $expr->getExpression())
                    );
            }
        }

        return $expr;
    }
}
