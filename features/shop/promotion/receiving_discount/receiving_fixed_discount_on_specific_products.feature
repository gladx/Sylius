@receiving_discount
Feature: Receiving fixed discount on specific products
    In order to buy specific product with a discount
    As a Customer
    I want to receive discount on each unit of promoted product

    Background:
        Given the store operates on a single channel in "United States"
        And the store has a product "PHP T-Shirt" priced at "$100.00"
        And the store has a product "PHP Mug" priced at "$20.00"
        And there is a promotion "T-Shirts promotion"
        And it gives "$10.00" off on a "PHP T-Shirt" product
        And I am a logged in customer

    @api @ui
    Scenario: Receiving fixed discount on a single item
        Given I added product "PHP T-Shirt" to the cart
        When I check the details of my cart
        Then its price should be decreased by "$10.00"
        And my cart total should be "$90.00"

    @api @ui
    Scenario: Not receiving fixed discount on another item
        Given I added product "PHP Mug" to the cart
        When I check the details of my cart
        Then product "PHP Mug" price should not be decreased
        And my cart total should be "$20.00"

    @api @ui
    Scenario: Receiving fixed discount on a multiple items
        Given I added 3 products "PHP T-Shirt" to the cart
        When I check the details of my cart
        Then its price should be decreased by "$30.00"
        And my cart total should be "$270.00"

    @api @ui
    Scenario: Receiving fixed discount equal to the items total of my cart
        Given there is a promotion "Christmas Sale"
        And it gives "$20.00" off on a "PHP Mug" product
        And I added 3 products "PHP Mug" to the cart
        When I check the details of my cart
        Then its price should be decreased by "$60.00"
        And my cart total should be "$0.00"

    @api @ui
    Scenario: Receiving fixed discount equal to the items total of my cart even if the discount is bigger than the items total
        Given there is a promotion "Christmas Sale"
        And it gives "$30.00" off on a "PHP Mug" product
        And I added 2 products "PHP Mug" to the cart
        When I check the details of my cart
        Then its price should be decreased by "$40.00"
        And my cart total should be "$0.00"

    @api @ui
    Scenario: Receiving fixed discount only on specified product
        Given I added product "PHP T-Shirt" to the cart
        And I added product "PHP Mug" to the cart
        When I check the details of my cart
        Then product "PHP T-Shirt" price should be decreased by "$10.00"
        And product "PHP Mug" price should not be decreased
        And my cart total should be "$110.00"

    @api @ui
    Scenario: Receiving different discounts on different items
        Given there is a promotion "Mugs promotion"
        And it gives "$2.00" off on a "PHP Mug" product
        And I added product "PHP T-Shirt" to the cart
        And I added product "PHP Mug" to the cart
        When I check the details of my cart
        Then product "PHP T-Shirt" price should be decreased by "$10.00"
        And product "PHP Mug" price should be decreased by "$2.00"
        And my cart total should be "$108.00"
