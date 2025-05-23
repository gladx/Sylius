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
use Doctrine\Persistence\ObjectManager;
use Sylius\Behat\Context\Ui\Admin\Helper\SecurePasswordTrait;
use Sylius\Behat\Service\SharedStorageInterface;
use Sylius\Component\Addressing\Model\CountryInterface;
use Sylius\Component\Core\Model\AddressInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\ShopUserInterface;
use Sylius\Component\Core\Repository\CustomerRepositoryInterface;
use Sylius\Component\Customer\Model\CustomerGroupInterface;
use Sylius\Resource\Factory\FactoryInterface;

final class CustomerContext implements Context
{
    use SecurePasswordTrait;

    public function __construct(
        private SharedStorageInterface $sharedStorage,
        private CustomerRepositoryInterface $customerRepository,
        private ObjectManager $customerManager,
        private FactoryInterface $customerFactory,
        private FactoryInterface $userFactory,
        private FactoryInterface $addressFactory,
    ) {
    }

    /**
     * @Given the store has customer :name with email :email
     */
    public function theStoreHasCustomerWithNameAndEmail($name, $email)
    {
        $partsOfName = explode(' ', $name);
        $customer = $this->createCustomer($email, $partsOfName[0], $partsOfName[1]);
        $this->customerRepository->add($customer);

        $this->sharedStorage->set('customer', $customer);
    }

    /**
     * @Given the store (also )has customer :email
     */
    public function theStoreHasCustomer($email)
    {
        $customer = $this->createCustomer($email);

        $this->customerRepository->add($customer);
    }

    /**
     * @Given the store has customer :email with first name :firstName
     */
    public function theStoreHasCustomerWithFirstName($email, $firstName)
    {
        $customer = $this->createCustomer($email, $firstName);

        $this->customerRepository->add($customer);
    }

    /**
     * @Given the store has customer :email with name :fullName since :since
     * @Given the store has customer :email with name :fullName and phone number :phoneNumber since :since
     */
    public function theStoreHasCustomerWithNameAndRegistrationDate($email, $fullName, $since, $phoneNumber = null)
    {
        $names = explode(' ', $fullName);
        $customer = $this->createCustomer($email, $names[0], $names[1], new \DateTime($since), $phoneNumber);

        $this->customerRepository->add($customer);
    }

    /**
     * @Given there is disabled customer account :email with password :password
     */
    public function thereIsDisabledCustomerAccountWithPassword($email, $password)
    {
        $customer = $this->createCustomerWithUserAccount($email, $password, false);
        $this->customerRepository->add($customer);
    }

    /**
     * @Given there is a customer account :email
     * @Given there is a customer account :email identified by :password
     * @Given there is enabled customer account :email with password :password
     */
    public function theStoreHasEnabledCustomerAccountWithPassword($email, $password = 'sylius')
    {
        $customer = $this->createCustomerWithUserAccount($email, $password, true);
        $this->customerRepository->add($customer);
    }

    /**
     * @Given there is a customer :name identified by an email :email and a password :password
     * @Given there is a customer :name with an email :email and a password :password
     */
    public function theStoreHasCustomerAccountWithEmailAndPassword(string $name, string $email, string $password): void
    {
        $this->createCustomerWithFullNameEmailAndPassword($name, $email, $password);
    }

    /**
     * @Given there is a customer :name with an email :email
     * @Given there is also a customer :name with an email :email
     */
    public function theStoreHasCustomerAccountWithEmailAndName(string $name, string $email): void
    {
        $this->createCustomerWithFullNameEmailAndPassword($name, $email, 'sylius');
    }

    /**
     * @Given /^(the customer) subscribed to the newsletter$/
     */
    public function theCustomerSubscribedToTheNewsletter(CustomerInterface $customer)
    {
        $customer->setSubscribedToNewsletter(true);

        $this->customerManager->flush();
    }

    /**
     * @Given /^(this customer) verified their email$/
     */
    public function theCustomerVerifiedTheirEmail(CustomerInterface $customer)
    {
        $customer->getUser()->setVerifiedAt(new \DateTime());

        $this->customerManager->flush();
    }

    /**
     * @Given /^(the customer) belongs to (group "([^"]+)")$/
     * @Given /^(this customer) belongs to (group "([^"]+)")$/
     */
    public function theCustomerBelongsToGroup(CustomerInterface $customer, CustomerGroupInterface $customerGroup)
    {
        $customer->setGroup($customerGroup);

        $this->customerManager->flush();
    }

    /**
     * @Given there is user :email with :country as shipping country
     */
    public function thereIsUserIdentifiedByWithAsShippingCountry($email, CountryInterface $country)
    {
        $customer = $this->createCustomerWithUserAccount($email, 'password123', true, 'John', 'Doe');

        /** @var AddressInterface $address */
        $address = $this->addressFactory->createNew();
        $address->setCountryCode($country->getCode());
        $address->setCity('Berlin');
        $address->setFirstName($customer->getFirstName());
        $address->setLastName($customer->getLastName());
        $address->setStreet('street');
        $address->setPostcode('123');
        $customer->setDefaultAddress($address);

        $this->customerRepository->add($customer);
    }

    /**
     * @param string $email
     * @param string|null $firstName
     * @param string|null $lastName
     * @param string|null $phoneNumber
     *
     * @return CustomerInterface
     */
    private function createCustomer(
        $email,
        $firstName = null,
        $lastName = null,
        ?\DateTimeInterface $createdAt = null,
        $phoneNumber = null,
    ) {
        /** @var CustomerInterface $customer */
        $customer = $this->customerFactory->createNew();

        $customer->setFirstName($firstName);
        $customer->setLastName($lastName);
        $customer->setEmail($email);
        $customer->setPhoneNumber($phoneNumber);
        if (null !== $createdAt) {
            $customer->setCreatedAt($createdAt);
        }

        $this->sharedStorage->set('customer', $customer);

        return $customer;
    }

    /**
     * @param string $email
     * @param string $password
     * @param bool $enabled
     * @param string|null $firstName
     * @param string|null $lastName
     * @param string|null $role
     *
     * @return CustomerInterface
     */
    private function createCustomerWithUserAccount(
        $email,
        $password,
        $enabled = true,
        $firstName = null,
        $lastName = null,
        $role = null,
    ) {
        /** @var ShopUserInterface $user */
        $user = $this->userFactory->createNew();

        /** @var CustomerInterface $customer */
        $customer = $this->customerFactory->createNew();

        $customer->setFirstName($firstName);
        $customer->setLastName($lastName);
        $customer->setEmail($email);
        $customer->setPhoneNumber('123456789');
        $customer->setGender('m');

        $user->setUsername($email);
        $user->setPlainPassword($this->replaceWithSecurePassword($password));
        $user->setEnabled($enabled);
        if (null !== $role) {
            $user->addRole($role);
        }

        $customer->setUser($user);

        $this->sharedStorage->set('customer', $customer);

        return $customer;
    }

    private function createCustomerWithFullNameEmailAndPassword(string $name, string $email, string $password): void
    {
        $names = explode(' ', $name);
        $firstName = $names[0];
        $lastName = count($names) > 1 ? $names[1] : null;

        $customer = $this->createCustomerWithUserAccount($email, $password, true, $firstName, $lastName);
        $this->customerRepository->add($customer);
    }
}
