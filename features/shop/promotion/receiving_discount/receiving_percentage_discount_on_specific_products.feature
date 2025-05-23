@receiving_discount
Feature: Receiving percentage discount on specific products
    In order to buy specific product with a discount
    As a Customer
    I want to receive discount on each unit of promoted product

    Background:
        Given the store operates on a single channel in "United States"
        And the store has a product "PHP T-Shirt" priced at "$100.00"
        And the store has a product "PHP Mug" priced at "$20.00"
        And there is a promotion "T-Shirts promotion"
        And it gives "20%" off on a "PHP T-Shirt" product
        And I am a logged in customer

    @api @ui
    Scenario: Receiving percentage discount on a single item
        Given I added product "PHP T-Shirt" to the cart
        When I check the details of my cart
        Then its price should be decreased by "$20.00"
        And my cart total should be "$80.00"

    @api @ui
    Scenario: Receiving percentage discount on a multiple items
        Given I added 3 products "PHP T-Shirt" to the cart
        When I check the details of my cart
        Then its price should be decreased by "$60.00"
        And my cart total should be "$240.00"

    @api @ui
    Scenario: Receiving percentage discount only on specified items
        Given I added product "PHP T-Shirt" to the cart
        And I added product "PHP Mug" to the cart
        When I check the details of my cart
        Then product "PHP T-Shirt" price should be decreased by "$20.00"
        And product "PHP Mug" price should not be decreased
        And my cart total should be "$100.00"

    @api @ui
    Scenario: Receiving different discounts on different items
        Given there is a promotion "Mugs promotion"
        And it gives "50%" off on a "PHP Mug" product
        Given I added product "PHP T-Shirt" to the cart
        And I added product "PHP Mug" to the cart
        When I check the details of my cart
        Then product "PHP T-Shirt" price should be decreased by "$20.00"
        And product "PHP Mug" price should be decreased by "$10.00"
        And my cart total should be "$90.00"
