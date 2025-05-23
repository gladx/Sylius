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

namespace Sylius\Behat\Context\Ui\Shop\Checkout;

use Behat\Behat\Context\Context;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use FriendsOfBehat\PageObjectExtension\Page\UnexpectedPageException;
use Sylius\Behat\Page\Shop\Checkout\AddressPageInterface;
use Sylius\Behat\Page\Shop\Checkout\SelectShippingPageInterface;
use Sylius\Behat\Service\Factory\AddressFactoryInterface;
use Sylius\Behat\Service\Helper\JavaScriptTestHelperInterface;
use Sylius\Behat\Service\SharedStorageInterface;
use Sylius\Component\Addressing\Comparator\AddressComparatorInterface;
use Sylius\Component\Addressing\Model\CountryInterface;
use Sylius\Component\Core\Model\AddressInterface;
use Webmozart\Assert\Assert;

final readonly class CheckoutAddressingContext implements Context
{
    public function __construct(
        private SharedStorageInterface $sharedStorage,
        private AddressPageInterface $addressPage,
        private AddressFactoryInterface $addressFactory,
        private AddressComparatorInterface $addressComparator,
        private SelectShippingPageInterface $selectShippingPage,
        private JavaScriptTestHelperInterface $testHelper,
    ) {
    }

    /**
     * @Given the visitor has completed the addressing step
     * @Given the customer has completed the addressing step
     * @When the customer completes the addressing step
     * @When the visitor completes the addressing step
     */
    public function theVisitorHasCompletedTheAddressingStep(): void
    {
        $this->addressPage->nextStep();
    }

    /**
     * @Given my billing address is fulfilled automatically through default address
     */
    public function myBillingAddressIsFulfilledAutomaticallyThroughDefaultAddress(): void
    {
        //intentionally blank line for api tests
    }

    /**
     * @Given I am at the checkout addressing step
     * @When I go to the checkout addressing step
     * @When I go back to addressing step of the checkout
     */
    public function iAmAtTheCheckoutAddressingStep(): void
    {
        $this->addressPage->open();
    }

    /**
     * @Given /^I have completed addressing step with email "([^"]+)" and ("[^"]+" based billing address)$/
     * @Given /^they have completed addressing step with email "([^"]+)" and ("[^"]+" based billing address)$/
     * @When /^I complete addressing step with email "([^"]+)" and ("[^"]+" based billing address)$/
     * @When /^they complete addressing step with email "([^"]+)" and ("[^"]+" based billing address)$/
     */
    public function iCompleteAddressingStepWithEmail(string $email, AddressInterface $address): void
    {
        $this->addressPage->open();
        $this->iSpecifyTheEmail($email);
        $this->iSpecifyTheBillingAddressAs($address);
        $this->addressPage->nextStep();
    }

    /**
     * @When /^I complete addressing step with ("[^"]+" based billing address)$/
     */
    public function iCompleteAddressingStepWithBasedBillingAddress(AddressInterface $address): void
    {
        $this->addressPage->open();
        $this->iSpecifyTheBillingAddressAs($address);
        $this->addressPage->nextStep();
    }

    /**
     * @When I specify the province name manually as :provinceName for shipping address
     */
    public function iSpecifyTheProvinceNameManuallyAsForShippingAddress(string $provinceName): void
    {
        $this->addressPage->specifyShippingAddressProvince($provinceName);
    }

    /**
     * @When I specify the province name manually as :provinceName for billing address
     */
    public function iSpecifyTheProvinceNameManuallyAsForBillingAddress(string $provinceName): void
    {
        $this->addressPage->specifyBillingAddressProvince($provinceName);
    }

    #[When('I try to open checkout addressing page')]
    public function iTryToOpenCheckoutAddressingPage(): void
    {
        $this->addressPage->tryToOpen();
    }

    /**
     * @When /^I choose ("[^"]+" street) for shipping address$/
     */
    public function iChooseForShippingAddress(AddressInterface $address): void
    {
        $this->addressPage->chooseDifferentShippingAddress();
        $this->addressPage->selectShippingAddressFromAddressBook($address);
    }

    /**
     * @When /^I choose ("[^"]+" street) for billing address$/
     */
    public function iChooseForBillingAddress(AddressInterface $address): void
    {
        $this->addressPage->selectBillingAddressFromAddressBook($address);
    }

    /**
     * @When /^I specify the shipping (address as "[^"]+", "[^"]+", "[^"]+", "[^"]+" for "[^"]+")$/
     * @When /^I specify the shipping (address for "[^"]+" from "[^"]+", "[^"]+", "[^"]+", "[^"]+", "[^"]+")$/
     * @When /^I change the shipping (address to "[^"]+", "[^"]+", "[^"]+", "[^"]+" for "[^"]+")$/
     */
    public function iSpecifyTheShippingAddressAs(AddressInterface $address): void
    {
        $this->addressPage->chooseDifferentShippingAddress();

        $key = sprintf(
            'shipping_address_%s_%s',
            strtolower((string) $address->getFirstName()),
            strtolower((string) $address->getLastName()),
        );
        $this->sharedStorage->set($key, $address);

        $this->addressPage->specifyShippingAddress($address);
    }

    /**
     * @When /^I (do not specify any shipping address) information$/
     */
    public function iDoNotSpecifyAnyShippingAddressInformation(): void
    {
        $this->addressPage->chooseDifferentShippingAddress();
    }

    /**
     * @When /^I specify the required shipping (address as "[^"]+", "[^"]+", "[^"]+", "[^"]+" for "[^"]+")$/
     */
    public function iSpecifyTheRequiredShippingAddressAs(AddressInterface $address): void
    {
        $key = sprintf(
            'shipping_address_%s_%s',
            strtolower((string) $address->getFirstName()),
            strtolower((string) $address->getLastName()),
        );
        $this->sharedStorage->set($key, $address);
        $this->sharedStorage->set(str_replace('shipping', 'billing', $key), $address);

        $this->addressPage->specifyShippingAddress($address);
    }

    /**
     * @When I specify shipping country province as :provinceName
     */
    public function iSpecifyShippingCountryProvinceAs(string $provinceName): void
    {
        $this->addressPage->selectShippingAddressProvince($provinceName);
    }

    /**
     * @When I specify billing country province as :provinceName
     */
    public function iSpecifyBillingCountryProvinceAs(string $provinceName): void
    {
        $this->addressPage->selectBillingAddressProvince($provinceName);
    }

    /**
     * @Given /^the visitor specify the billing (address as "([^"]+)", "([^"]+)", "([^"]+)", "([^"]+)" for "([^"]+)")$/
     * @Given /^the visitor has specified (address as "([^"]+)", "([^"]+)", "([^"]+)", "([^"]+)" for "([^"]+)")$/
     * @Given /^the customer has specified (address as "([^"]+)", "([^"]+)", "([^"]+)", "([^"]+)" for "([^"]+)")$/
     * @When /^I specify the billing (address as "([^"]+)", "([^"]+)", "([^"]+)", "([^"]+)" for "([^"]+)")$/
     * @When /^I specify the billing (address for "([^"]+)" from "([^"]+)", "([^"]+)", "([^"]+)", "([^"]+)", "([^"]+)")$/
     * @When /^I (do not specify any billing address) information$/
     */
    #[Given('/^the customer specify the billing (address as "([^"]+)", "([^"]+)", "([^"]+)", "([^"]+)" for "([^"]+)")$/')]
    #[Given('the customer specify the billing address')]
    #[Given('the visitor specify the billing address')]
    public function iSpecifyTheBillingAddressAs(?AddressInterface $address = null): void
    {
        if (!$this->addressPage->isOpen()) {
            $this->addressPage->open();
        }

        if ($address === null) {
            $address = $this->addressFactory->createDefault();
        }

        $billingKey = sprintf(
            'billing_address_%s_%s',
            strtolower((string) $address->getFirstName()),
            strtolower((string) $address->getLastName()),
        );
        $shippingKey = sprintf(
            'shipping_address_%s_%s',
            strtolower((string) $address->getFirstName()),
            strtolower((string) $address->getLastName()),
        );
        $this->sharedStorage->set($billingKey, $address);
        $this->sharedStorage->set($shippingKey, $address);

        $this->addressPage->specifyBillingAddress($address);
    }

    /**
     * @When /^I specify different billing (address as "([^"]+)", "([^"]+)", "([^"]+)", "([^"]+)" for "([^"]+)")$/
     */
    public function iSpecifyDifferentBillingAddressAs(AddressInterface $address): void
    {
        $this->addressPage->chooseDifferentBillingAddress();

        $this->iSpecifyTheBillingAddressAs($address);
    }

    /**
     * @When I specified the billing address
     * @When /^I specified the billing (address as "[^"]+", "[^"]+", "[^"]+", "[^"]+" for "[^"]+")$/
     * @When /^I define the billing (address as "[^"]+", "[^"]+", "[^"]+", "[^"]+" for "[^"]+")$/
     */
    public function iSpecifiedTheBillingAddress(?AddressInterface $address = null): void
    {
        if (null === $address) {
            $address = $this->addressFactory->createDefault();
        }

        if (!$this->addressPage->isOpen()) {
            $this->addressPage->open();
        }
        $this->addressPage->specifyBillingAddress($address);

        $billingKey = sprintf('billing_address_%s_%s', strtolower((string) $address->getFirstName()), strtolower((string) $address->getLastName()));
        $shippingKey = sprintf('shipping_address_%s_%s', strtolower((string) $address->getFirstName()), strtolower((string) $address->getLastName()));
        $this->sharedStorage->set($billingKey, $address);
        $this->sharedStorage->set($shippingKey, $address);

        $this->addressPage->nextStep();
    }

    /**
     * @When I specify the email as :email
     * @When I do not specify the email
     */
    public function iSpecifyTheEmail(?string $email = null): void
    {
        $this->addressPage->specifyEmail($email);
    }

    /**
     * @Given the visitor has specified the email as :email
     * @Given the customer has specified the email as :email
     * @When the visitor specify the email as :email
     */
    public function theVisitorSpecifyTheEmail($email = null): void
    {
        $this->addressPage->open();
        $this->addressPage->specifyEmail($email);
    }

    /**
     * @When I specify the first and last name as :fullName for billing address
     */
    public function iSpecifyTheFirstAndLastNameAsForBillingAddress(string $fullName): void
    {
        $this->addressPage->specifyBillingAddressFullName($fullName);
    }

    /**
     * @When I complete the addressing step
     * @When I try to complete the addressing step
     */
    public function iCompleteTheAddressingStep(): void
    {
        if (!$this->addressPage->isOpen()) {
            throw new UnexpectedPageException('Addressing page should be open, but it is not.');
        }

        $this->addressPage->nextStep();
    }

    /**
     * @When I go back to store
     */
    public function iGoBackToStore(): void
    {
        $this->addressPage->backToStore();
    }

    /**
     * @When /^I proceed selecting ("[^"]+" as billing country)$/
     * @When /^I proceed with selecting ("[^"]+" as billing country)$/
     * @When /^I proceed with selecting billing country$/
     */
    public function iProceedSelectingBillingCountry(
        ?CountryInterface $shippingCountry = null,
        string $localeCode = 'en_US',
        ?string $email = null,
    ): void {
        $this->addressPage->open(['_locale' => $localeCode]);
        $shippingAddress = $this->addressFactory->createDefault();
        if (null !== $shippingCountry) {
            $shippingAddress->setCountryCode($shippingCountry->getCode());
        }
        if (null !== $email) {
            $this->addressPage->specifyEmail($email);
        }
        $this->addressPage->specifyBillingAddress($shippingAddress);
        $this->addressPage->nextStep();
    }

    /**
     * @When /^I proceed as guest "([^"]*)" with ("[^"]+" as billing country)$/
     */
    public function iProceedLoggingAsGuestWithAsBillingCountry(
        string $email,
        ?CountryInterface $shippingCountry = null,
    ): void {
        $this->addressPage->open();
        $this->addressPage->specifyEmail($email);
        $shippingAddress = $this->addressFactory->createDefault();
        if (null !== $shippingCountry) {
            $shippingAddress->setCountryCode($shippingCountry->getCode());
        }

        $this->addressPage->specifyBillingAddress($shippingAddress);
        $this->addressPage->nextStep();
    }

    /**
     * @When I specify the password as :password
     */
    public function iSpecifyThePasswordAs(string $password): void
    {
        $this->addressPage->specifyPassword($password);
    }

    /**
     * @Then I should be making an order as :purchaserIdentifier
     */
    public function iShouldSeeInCheckoutHeader(string $purchaserIdentifier): void
    {
        Assert::contains($this->selectShippingPage->getPurchaserIdentifier(), $purchaserIdentifier);
    }

    /**
     * @When I sign in
     */
    public function iSignIn(): void
    {
        $this->addressPage->signIn();
    }

    /**
     * @Then I should have :countryName selected as country
     */
    public function iShouldHaveSelectedAsCountry(string $countryName): void
    {
        Assert::same($this->addressPage->getBillingAddressCountry(), $countryName);
    }

    /**
     * @Then I should have no country selected
     */
    public function iShouldHaveNoCountrySelected(): void
    {
        Assert::same($this->addressPage->getBillingAddressCountry(), 'Select');
    }

    /**
     * @Then I should be able to log in
     */
    public function iShouldBeAbleToLogIn()
    {
        Assert::true($this->addressPage->canSignIn());
    }

    /**
     * @Then the login form should no longer be accessible
     */
    public function theLoginFormShouldNoLongerBeAccessible()
    {
        Assert::false($this->addressPage->canSignIn());
    }

    /**
     * @Then I should be notified about bad credentials
     */
    public function iShouldBeNotifiedAboutBadCredentials()
    {
        Assert::true($this->addressPage->checkInvalidCredentialsValidation());
    }

    /**
     * @Then I should be notified to resubmit the addressing form
     */
    public function iShouldBeNotifiedToResubmitTheAddressingForm()
    {
        Assert::true($this->addressPage->checkFormValidationMessage('Please resubmit complete form.'), 'Unable to find "Please resubmit complete form." validation message');
    }

    /**
     * @Then I should not be notified that the form contains extra fields
     */
    public function iShouldNotBeNotifiedTheFormContainsExtraFields()
    {
        Assert::false($this->addressPage->checkFormValidationMessage('This form should not contain extra fields.'), 'Found "This form should not contains extra fields." validation message');
    }

    #[Then('I should be redirected to the addressing step')]
    #[Then('I should be on the checkout addressing step')]
    public function iShouldBeRedirectedToTheAddressingStep(): void
    {
        $this->addressPage->verify();
    }

    #[Then('I should be able to go to the shipping step again')]
    public function iShouldBeAbleToGoToTheShippingStepAgain(): void
    {
        $this->addressPage->nextStep();

        $this->selectShippingPage->verify();
    }

    /**
     * @Then I should not be able to specify province name manually for shipping address
     */
    public function iShouldNotBeAbleToSpecifyProvinceNameManuallyForShippingAddress(): void
    {
        Assert::false($this->addressPage->hasShippingAddressInput());
    }

    /**
     * @Then I should not be able to specify province name manually for billing address
     */
    public function iShouldNotBeAbleToSpecifyProvinceNameManuallyForBillingAddress()
    {
        Assert::false($this->addressPage->hasBillingAddressInput());
    }

    /**
     * @Then /^(address "[^"]+", "[^"]+", "[^"]+", "[^"]+", "[^"]+", "[^"]+") should be filled as shipping address$/
     */
    public function addressShouldBeFilledAsShippingAddress(AddressInterface $address)
    {
        $this->testHelper->waitUntilAssertionPasses(function () use ($address): void {
            Assert::true($this->addressComparator->equal($address, $this->addressPage->getPreFilledShippingAddress()));
        });
    }

    /**
     * @Then /^(address "[^"]+", "[^"]+", "[^"]+", "[^"]+", "[^"]+", "[^"]+") should be filled as billing address$/
     */
    public function addressShouldBeFilledAsBillingAddress(AddressInterface $address): void
    {
        $this->testHelper->waitUntilAssertionPasses(function () use ($address): void {
            Assert::true($this->addressComparator->equal($address, $this->addressPage->getPreFilledBillingAddress()));
        });
    }

    /**
     * @Then different shipping address should be checked
     */
    public function differentShippingAddressShouldBeChecked(): void
    {
        Assert::true($this->addressPage->isDifferentShippingAddressChecked());
    }

    /**
     * @Then different shipping address should not be checked
     */
    public function differentShippingAddressShouldNotBeChecked(): void
    {
        Assert::false($this->addressPage->isDifferentShippingAddressChecked());
    }

    /**
     * @Then shipping address should be visible
     */
    public function shippingAddressShouldBeVisible(): void
    {
        Assert::true($this->addressPage->isShippingAddressVisible());
    }

    /**
     * @Then shipping address should not be visible
     */
    public function shippingAddressShouldNotBeVisible(): void
    {
        Assert::false($this->addressPage->isShippingAddressVisible());
    }

    /**
     * @Then /^I should(?:| also) be notified that the "([^"]+)" and the "([^"]+)" in (shipping|billing) details are required$/
     */
    public function iShouldBeNotifiedThatTheAndTheInShippingDetailsAreRequired($firstElement, $secondElement, $type)
    {
        $this->assertElementValidationMessage($type, $firstElement, sprintf('Please enter %s.', $firstElement));
        $this->assertElementValidationMessage($type, $secondElement, sprintf('Please enter %s.', $secondElement));
    }

    /**
     * @Then /^I should(?:| also) be notified that the "([^"]+)" in (shipping|billing) details is required$/
     */
    public function iShouldBeNotifiedThatTheInShippingDetailsIsRequired(string $element, string $type): void
    {
        $this->assertElementValidationMessage($type, $element, sprintf('Please enter %s.', $element));
    }

    /**
     * @Then I should have only :firstCountry country available to choose from
     * @Then I should have both :firstCountry and :secondCountry countries available to choose from
     */
    public function shouldHaveCountriesToChooseFrom(string ...$countries): void
    {
        $availableBillingCountries = $this->addressPage->getAvailableBillingCountries();

        sort($countries);
        sort($availableBillingCountries);

        Assert::same($availableBillingCountries, $countries);
    }

    /**
     * @Then I should be able to update the address without unexpected alert
     */
    public function iShouldBeAbleToUpdateTheAddressWithoutUnexpectedAlert(): void
    {
        $this->addressPage->waitForFormToStopLoading();
    }

    /**
     * @Then the customer should have checkout address step completed
     * @Then the visitor should have checkout address step completed
     */
    public function theCustomerShouldHaveCheckoutAddressStepCompleted(): void
    {
        Assert::false(
            $this->addressPage->isOpen(),
            'Customer should have checkout address step completed, but it is not.',
        );
    }

    /**
     * @param string $type
     * @param string $element
     * @param string $expectedMessage
     *
     * @throws \InvalidArgumentException
     */
    private function assertElementValidationMessage($type, $element, $expectedMessage)
    {
        $element = sprintf('%s_%s', $type, str_replace(' ', '_', $element));
        Assert::true($this->addressPage->checkValidationMessageFor($element, $expectedMessage));
    }
}
