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

namespace Sylius\Behat\Context\Setup;

use Behat\Behat\Context\Context;
use Behat\Step\Given;
use Doctrine\Persistence\ObjectManager;
use Sylius\Behat\Service\SharedStorageInterface;
use Sylius\Bundle\CoreBundle\Fixture\Factory\ExampleFactoryInterface;
use Sylius\Bundle\PayumBundle\Model\GatewayConfigInterface;
use Sylius\Component\Core\Formatter\StringInflector;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Payment\Model\PaymentMethodTranslationInterface;
use Sylius\Component\Payment\Repository\PaymentMethodRepositoryInterface;
use Sylius\Resource\Factory\FactoryInterface;

final readonly class PaymentContext implements Context
{
    /**
     * @param PaymentMethodRepositoryInterface<PaymentMethodInterface> $paymentMethodRepository
     * @param ExampleFactoryInterface<PaymentMethodInterface> $paymentMethodExampleFactory
     * @param FactoryInterface<PaymentMethodTranslationInterface> $paymentMethodTranslationFactory
     * @param array<string, string> $gatewayFactories
     */
    public function __construct(
        private SharedStorageInterface $sharedStorage,
        private PaymentMethodRepositoryInterface $paymentMethodRepository,
        private ExampleFactoryInterface $paymentMethodExampleFactory,
        private FactoryInterface $paymentMethodTranslationFactory,
        private ObjectManager $paymentMethodManager,
        private array $gatewayFactories,
    ) {
    }

    #[Given('the store (also )allows paying (with ):paymentMethodName')]
    #[Given('the store (also )allows paying (with ):paymentMethodName at position :position')]
    public function storeAllowsPaying(string $paymentMethodName, ?int $position = null): void
    {
        $this->createPaymentMethod($paymentMethodName, StringInflector::nameToCode($paymentMethodName), 'Offline', 'Payment method', true, $position);
    }

    /**
     * @Given the store has disabled all payment methods
     */
    public function theStoreHasDisabledAllPaymentMethods(): void
    {
        $paymentMethods = $this->paymentMethodRepository->findAll();

        /** @var PaymentMethodInterface $paymentMethod */
        foreach ($paymentMethods as $paymentMethod) {
            $paymentMethod->setEnabled(false);
        }

        $this->paymentMethodManager->flush();
    }

    /**
     * @Given /^the store allows paying (\w+) for (all channels)$/
     */
    public function storeAllowsPayingForAllChannels($paymentMethodName, array $channels): void
    {
        $paymentMethod = $this->createPaymentMethod($paymentMethodName, StringInflector::nameToUppercaseCode($paymentMethodName), 'Offline', 'Payment method', false);

        foreach ($channels as $channel) {
            $paymentMethod->addChannel($channel);
        }
    }

    /**
     * @Given the store has (also) a payment method :paymentMethodName with a code :paymentMethodCode
     */
    public function theStoreHasAPaymentMethodWithACode(string $paymentMethodName, string $paymentMethodCode): void
    {
        $this->createPaymentMethod($paymentMethodName, $paymentMethodCode, 'Offline');
    }

    /**
     * @Given /^(this payment method) is named "([^"]+)" in the "([^"]+)" locale$/
     */
    public function thisPaymentMethodIsNamedIn(PaymentMethodInterface $paymentMethod, $name, $locale): void
    {
        /** @var PaymentMethodTranslationInterface $translation */
        $translation = $this->paymentMethodTranslationFactory->createNew();
        $translation->setLocale($locale);
        $translation->setName($name);

        $paymentMethod->addTranslation($translation);

        $this->paymentMethodManager->flush();
    }

    #[Given('/^(this payment method) is not using Payum$/')]
    public function thisPaymentMethodIsNotUsingPayum(PaymentMethodInterface $paymentMethod): void
    {
        /** @var GatewayConfigInterface $gatewayConfig */
        $gatewayConfig = $paymentMethod->getGatewayConfig();
        $gatewayConfig->setUsePayum(false);

        $this->paymentMethodManager->flush();
    }

    /**
     * @Given the payment method :paymentMethod is disabled
     * @Given /^(this payment method) (?:has been|is) disabled$/
     * @When the payment method :paymentMethod gets disabled
     */
    public function theStoreHasAPaymentMethodDisabled(PaymentMethodInterface $paymentMethod): void
    {
        $paymentMethod->disable();

        $this->paymentMethodManager->flush();
    }

    /**
     * @Given /^(it) has instructions "([^"]+)"$/
     */
    public function itHasInstructions(PaymentMethodInterface $paymentMethod, $instructions): void
    {
        $paymentMethod->setInstructions($instructions);

        $this->paymentMethodManager->flush();
    }

    /**
     * @Given the store has :paymentMethodName payment method not assigned to any channel
     */
    public function theStoreHasPaymentMethodNotAssignedToAnyChannel(string $paymentMethodName): void
    {
        $this->createPaymentMethod($paymentMethodName, 'PM_' . $paymentMethodName, 'Offline', 'Payment method', false);
    }

    /**
     * @Given the payment method :paymentMethod requires authorization before capturing
     */
    public function thePaymentMethodRequiresAuthorizationBeforeCapturing(PaymentMethodInterface $paymentMethod): void
    {
        /** @var GatewayConfigInterface $config */
        $config = $paymentMethod->getGatewayConfig();
        $config->setConfig(array_merge($config->getConfig(), ['use_authorize' => true]));
        $paymentMethod->setGatewayConfig($config);

        $this->paymentMethodManager->flush();
    }

    /**
     * @Given the store allows paying with :paymentMethodName in :channel channel
     */
    public function theStoreAllowsPayingWithInChannel(string $paymentMethodName, ChannelInterface $channel): void
    {
        $paymentMethod = $this->createPaymentMethod(
            $paymentMethodName,
            StringInflector::nameToUppercaseCode($paymentMethodName),
            'Offline',
            'Payment method',
            false,
        );

        $paymentMethod->addChannel($channel);
    }

    private function createPaymentMethod(
        string $name,
        string $code,
        string $gatewayFactory,
        string $description = '',
        bool $addForCurrentChannel = true,
        ?int $position = null,
    ): PaymentMethodInterface {
        $gatewayFactory = array_search($gatewayFactory, $this->gatewayFactories);

        /** @var PaymentMethodInterface $paymentMethod */
        $paymentMethod = $this->paymentMethodExampleFactory->create([
            'name' => ucfirst($name),
            'code' => $code,
            'description' => $description,
            'gatewayName' => $gatewayFactory,
            'gatewayFactory' => $gatewayFactory,
            'enabled' => true,
            'channels' => ($addForCurrentChannel && $this->sharedStorage->has('channel')) ? [$this->sharedStorage->get('channel')] : [],
        ]);

        if (null !== $position) {
            $paymentMethod->setPosition($position);
        }

        $this->sharedStorage->set('payment_method', $paymentMethod);
        $this->paymentMethodRepository->add($paymentMethod);

        return $paymentMethod;
    }
}
