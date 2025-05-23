@checkout
Feature: Order addressing validation
    In order to avoid making mistakes when addressing an order
    As a Visitor
    I want to be prevented from adding it without specifying required fields

    Background:
        Given the store operates on a single channel in "United States"
        And the store ships everywhere for Free
        And the store has a product "PHP T-Shirt" priced at "$19.99"

    @api @ui @javascript
    Scenario: Address an order without name, city and street
        Given I added product "PHP T-Shirt" to the cart
        And I am at the checkout addressing step
        When I do not specify any shipping address information
        And I try to complete the addressing step
        Then I should be notified that the "first name" and the "last name" in shipping details are required
        And I should also be notified that the "city" and the "street" in shipping details are required
        And I should also be notified that the "postcode" in shipping details is required

    @api @ui @javascript
    Scenario: Address an order's billing address without name, city and street
        Given I added product "PHP T-Shirt" to the cart
        And I am at the checkout addressing step
        When I specify the shipping address as "Ankh Morpork", "Frost Alley", "90210", "United States" for "Jon Snow"
        And I do not specify any billing address information
        And I try to complete the addressing step
        Then I should be notified that the "first name" and the "last name" in billing details are required
        And I should also be notified that the "city" and the "street" in billing details are required
        And I should also be notified that the "postcode" in billing details is required
