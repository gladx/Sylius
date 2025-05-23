<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Sylius Sp. z o.o.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace spec\Sylius\Bundle\ApiBundle\Doctrine\ORM\QueryExtension\Shop\ExchangeRate;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use PhpSpec\ObjectBehavior;
use stdClass;
use Sylius\Bundle\ApiBundle\SectionResolver\AdminApiSection;
use Sylius\Bundle\ApiBundle\SectionResolver\ShopApiSection;
use Sylius\Bundle\ApiBundle\Serializer\ContextKeys;
use Sylius\Bundle\CoreBundle\SectionResolver\SectionProviderInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Currency\Model\CurrencyInterface;
use Sylius\Component\Currency\Model\ExchangeRate;
use Sylius\Component\Currency\Model\ExchangeRateInterface;

final class ChannelBasedExtensionSpec extends ObjectBehavior
{
    function let(SectionProviderInterface $sectionProvider): void
    {
        $this->beConstructedWith($sectionProvider);
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(QueryCollectionExtensionInterface::class);
        $this->shouldHaveType(QueryItemExtensionInterface::class);
    }

    public function it_does_not_apply_conditions_to_collection_for_unsupported_resource(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
    ): void {
        $this->applyToCollection($queryBuilder, $queryNameGenerator, stdClass::class);

        $queryBuilder->getRootAliases()->shouldNotHaveBeenCalled();
        $queryBuilder->andWhere()->shouldNotHaveBeenCalled();
    }

    public function it_does_not_apply_conditions_to_item_for_unsupported_resource(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
    ): void {
        $this->applyToItem($queryBuilder, $queryNameGenerator, stdClass::class, []);

        $queryBuilder->getRootAliases()->shouldNotHaveBeenCalled();
        $queryBuilder->andWhere()->shouldNotHaveBeenCalled();
    }

    function it_does_not_apply_conditions_to_collection_for_admin_api_section(
        SectionProviderInterface $sectionProvider,
        AdminApiSection $adminApiSection,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
    ): void {
        $sectionProvider->getSection()->willReturn($adminApiSection);

        $this->applyToCollection($queryBuilder, $queryNameGenerator, ExchangeRateInterface::class);

        $queryBuilder->getRootAliases()->shouldNotHaveBeenCalled();
        $queryBuilder->andWhere()->shouldNotHaveBeenCalled();
    }

    function it_does_not_apply_conditions_to_item_for_admin_api_section(
        SectionProviderInterface $sectionProvider,
        AdminApiSection $adminApiSection,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
    ): void {
        $sectionProvider->getSection()->willReturn($adminApiSection);

        $this->applyToItem($queryBuilder, $queryNameGenerator, ExchangeRateInterface::class, []);

        $queryBuilder->getRootAliases()->shouldNotHaveBeenCalled();
        $queryBuilder->andWhere()->shouldNotHaveBeenCalled();
    }

    function it_throws_an_exception_during_apply_collection_if_context_has_no_channel(
        SectionProviderInterface $sectionProvider,
        ShopApiSection $shopApiSection,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
    ): void {
        $sectionProvider->getSection()->willReturn($shopApiSection);

        $this
            ->shouldThrow(\InvalidArgumentException::class)
            ->during('applyToCollection', [$queryBuilder, $queryNameGenerator, ExchangeRate::class])
        ;
    }

    function it_throws_an_exception_during_apply_item_if_context_has_no_channel(
        SectionProviderInterface $sectionProvider,
        ShopApiSection $shopApiSection,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
    ): void {
        $sectionProvider->getSection()->willReturn($shopApiSection);

        $this
            ->shouldThrow(\InvalidArgumentException::class)
            ->during('applyToItem', [$queryBuilder, $queryNameGenerator, ExchangeRate::class, []])
        ;
    }

    function it_applies_conditions_to_collection_for_shop_api_section(
        SectionProviderInterface $sectionProvider,
        ShopApiSection $shopApiSection,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        ChannelInterface $channel,
        CurrencyInterface $currency,
        Expr $expr,
        Expr\Orx $exprOrx,
        Expr\Comparison $exprComparison,
    ): void {
        $sectionProvider->getSection()->willReturn($shopApiSection);
        $channel->getBaseCurrency()->willReturn($currency);
        $queryNameGenerator->generateParameterName(':currency')->willReturn(':currency');

        $queryBuilder->getRootAliases()->willReturn(['o']);
        $queryBuilder->expr()->willReturn($expr);
        $expr->eq('o.sourceCurrency', ':currency')->willReturn($exprComparison);
        $expr->eq('o.targetCurrency', ':currency')->willReturn($exprComparison);
        $expr->orX($exprComparison, $exprComparison)->willReturn($exprOrx);

        $queryBuilder->andWhere($exprOrx)->shouldBeCalled()->willReturn($queryBuilder->getWrappedObject());
        $queryBuilder->setParameter(':currency', $currency)->willReturn($queryBuilder->getWrappedObject());

        $this->applyToCollection(
            $queryBuilder,
            $queryNameGenerator,
            ExchangeRateInterface::class,
            null,
            [
                ContextKeys::CHANNEL => $channel->getWrappedObject(),
            ],
        );

        $queryBuilder->setParameter(':currency', $currency)->shouldHaveBeenCalledOnce();
    }

    function it_applies_conditions_to_item_for_shop_api_section(
        SectionProviderInterface $sectionProvider,
        ShopApiSection $shopApiSection,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        ChannelInterface $channel,
        CurrencyInterface $currency,
        Expr $expr,
        Expr\Orx $exprOrx,
        Expr\Comparison $exprComparison,
    ): void {
        $sectionProvider->getSection()->willReturn($shopApiSection);
        $channel->getBaseCurrency()->willReturn($currency);
        $queryNameGenerator->generateParameterName(':currency')->willReturn(':currency');

        $queryBuilder->getRootAliases()->willReturn(['o']);
        $queryBuilder->expr()->willReturn($expr);
        $expr->eq('o.sourceCurrency', ':currency')->willReturn($exprComparison);
        $expr->eq('o.targetCurrency', ':currency')->willReturn($exprComparison);
        $expr->orX($exprComparison, $exprComparison)->willReturn($exprOrx);

        $queryBuilder->andWhere($exprOrx)->shouldBeCalled()->willReturn($queryBuilder->getWrappedObject());
        $queryBuilder->setParameter(':currency', $currency)->willReturn($queryBuilder->getWrappedObject());

        $this->applyToItem(
            $queryBuilder,
            $queryNameGenerator,
            ExchangeRateInterface::class,
            [],
            null,
            [
                ContextKeys::CHANNEL => $channel->getWrappedObject(),
            ],
        );

        $queryBuilder->setParameter(':currency', $currency)->shouldHaveBeenCalledOnce();
    }
}
