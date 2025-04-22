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
use Sylius\Component\Inventory\Checker\AvailabilityCheckerInterface;
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
        private readonly AvailabilityCheckerInterface $orderItemAvailabilityChecker
    ) {
    }

    public function requestAction(Request $request, int $itemId): Response
    {
        /** @var FlashBagInterface $flashBag */
        $flashBag = $request->getSession()->getBag('flashes');

        /** @var OrderItemInterface|null $orderItem */
        $orderItem = $this->orderItemRepository->find($itemId);
        if (null === $orderItem) {
            $flashBag->add('error', 'sylius.reorder.add_fail_item_not_found');

            return $this->redirectToReferer($request);
        }

        $productVariant = $orderItem->getVariant();
        if (null === $productVariant) {
            $flashBag->add('error', 'sylius.reorder.add_fail_variant_not_found');

            return $this->redirectToReferer($request);
        }

        $product = $productVariant->getProduct();
        if (!$product->isEnabled() || !$productVariant->isEnabled() || !$this->orderItemAvailabilityChecker->isStockSufficient($productVariant, 1)) {
            $flashBag->add('error', 'sylius.reorder.add_fail_not_available');
            return $this->redirectToReferer($request);
        }

        $cart = $this->cartContext->getCart();
        $cartItem = $this->cartItemFactory->createForProduct($productVariant->getProduct());
        $cartItem->setVariant($productVariant);
        $cartItem->setUnitPrice($orderItem->getUnitPrice());
        $addToCartCommand = $this->addToCartCommandFactory->createWithCartAndCartItem(
            $cart,
            $cartItem,
        );

        $this->eventDispatcher->dispatch(new GenericEvent($addToCartCommand), SyliusCartEvents::CART_ITEM_ADD);
        $this->manager->persist($addToCartCommand->getCart());
        $this->manager->flush();

        $flashBag->add('success', 'sylius.reorder.add_successful');

        return new RedirectResponse($this->router->generate('sylius_shop_cart_summary'));
    }

    private function redirectToReferer(Request $request): RedirectResponse
    {
        $referer = $request->headers->get('referer');
        if ($referer === null) {
            return new RedirectResponse($this->router->generate('sylius_shop_account_order_index'));
        }

        return new RedirectResponse($referer);
    }
}
