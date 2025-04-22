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

namespace Sylius\Bundle\ShopBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Bundle\CoreBundle\Doctrine\ORM\OrderItemRepository;
use Sylius\Bundle\OrderBundle\Factory\AddToCartCommandFactoryInterface;
use Sylius\Component\Core\Factory\CartItemFactoryInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Order\Context\CartContextInterface;
use Sylius\Component\Order\SyliusCartEvents;
use Sylius\Resource\Symfony\EventDispatcher\GenericEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Routing\RouterInterface;

final class ReorderController
{
    public function __construct(
        private readonly RouterInterface $router,
        private readonly OrderItemRepository $orderItemRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly EntityManagerInterface $manager,
        private readonly CartContextInterface $cartContext,
        private readonly CartItemFactoryInterface $cartItemFactory,
        private readonly AddToCartCommandFactoryInterface $addToCartCommandFactory,
    ) {
    }

    public function requestAction(Request $request, int $itemId): Response
    {
        /** @var FlashBagInterface $flashBag */
        $flashBag = $request->getSession()->getBag('flashes');

        /** @var OrderItemInterface|null $orderItem */
        $orderItem = $this->orderItemRepository->find($itemId);
        if (null === $orderItem) {
            $flashBag->add('error', 'item not found');

            return new RedirectResponse($request->headers->get('referer'));
        }

        $productVariant = $orderItem->getVariant();
        if (null === $productVariant) {
            $flashBag->add('error', 'varient not found');

            return new RedirectResponse($request->headers->get('referer'));
        }

        $cart = $this->cartContext->getCart();
        $cartItem = $this->cartItemFactory->createForProduct($productVariant->getProduct());
        $cartItem->setVariant($productVariant);
        $cartItem->setUnitPrice($orderItem->getUnitPrice());
        $addToCartCommand = $this->addToCartCommandFactory->createWithCartAndCartItem(
            $cart,
            $cartItem,
        );

        // copy from AddToCartFormComponent
        $this->eventDispatcher->dispatch(new GenericEvent($addToCartCommand), SyliusCartEvents::CART_ITEM_ADD);
        $this->manager->persist($addToCartCommand->getCart());
        $this->manager->flush();

        $flashBag->add('success', 'Item has been added to cart by reorderd');

        return new RedirectResponse($this->router->generate('sylius_shop_cart_summary'));
    }
}
