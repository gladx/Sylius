@checkout
Feature: Selecting an order payment method
    In order to pay for my order in a convenient way for me
    As a Visitor
    I want to be able to choose a payment method

    Background:
        Given the store operates on a single channel in "United States"
        And the store has a product "PHP T-Shirt" priced at "$19.99"
        And the store ships everywhere for Free
        And the store allows paying with "Bank transfer"

    @api @ui
    Scenario: Selecting a payment method
        Given this payment method is not using Payum
        And I have product "PHP T-Shirt" in the cart
        And I am at the checkout addressing step
        When I specify the email as "jon.snow@example.com"
        And I specify the billing address as "Ankh Morpork", "Frost Alley", "90210", "United States" for "Jon Snow"
        And I complete the addressing step
        And I select "Free" shipping method
        And I complete the shipping step
        And I select "Bank transfer" payment method
        And I complete the payment step
        Then I should be on the checkout complete step

    @api @ui
    Scenario: Using Payum selecting a payment method
        Given I added product "PHP T-Shirt" to the cart
        And I am at the checkout addressing step
        When I specify the email as "jon.snow@example.com"
        And I specify the billing address as "Ankh Morpork", "Frost Alley", "90210", "United States" for "Jon Snow"
        And I complete the addressing step
        And I select "Free" shipping method
        And I complete the shipping step
        And I select "Bank transfer" payment method
        And I complete the payment step
        Then I should be on the checkout complete step
