@applying_taxes
Feature: Apply correct taxes for an order with a discount applied for all items in it when tax rates are included in price
    In order to pay proper amount when buying goods
    As a Visitor
    I want to have correct taxes applied to my order with a discount and tax rates are included in products prices

    Background:
        Given the store operates on a single channel in "United States"
        And default tax zone is "US"
        And the store has included in price "US VAT" tax rate of 23% for "Clothes" within the "US" zone
        And the store has included in price "Low VAT" tax rate of 10% for "Mugs" within the "US" zone
        And the store has a product "PHP T-Shirt" priced at "$10.00"
        And it belongs to "Clothes" tax category
        And the store has a product "Symfony Mug" priced at "$56.95"
        And it belongs to "Mugs" tax category
        And the store has a product "PHP Mug" priced at "$56.90"
        And it belongs to "Mugs" tax category
        And there is a promotion "Holiday promotion"
        And the promotion gives "$10.00" off if order contains a "Symfony Mug" product
        And there is a promotion "PHP promotion"
        And the promotion gives "$10.00" off if order contains a "PHP Mug" product

    @api @ui
    Scenario: Properly rounded up tax for single product
        Given I added product "Symfony Mug" to the cart
        When I check the details of my cart
        Then my cart total should be "$46.95"
        And my included in price taxes should be "$4.27"

    @api @ui
    Scenario: Properly rounded down tax for single product
        Given I added product "PHP Mug" to the cart
        When I check the details of my cart
        Then my cart total should be "$46.90"
        And my included in price taxes should be "$4.26"

    @api @ui
    Scenario: Properly rounded taxes for multiple products with different tax rate
        Given I added 2 products "PHP T-Shirt" to the cart
        And I added product "PHP Mug" to the cart
        When I check the details of my cart
        Then my cart total should be "$66.90"
        And my included in price taxes should be "$7.75"

    @api @ui
    Scenario: Properly rounded taxes for multiple products with the same tax rate
        Given I added 2 products "PHP Mug" to the cart
        And I added product "Symfony Mug" to the cart
        When I check the details of my cart
        Then my cart total should be "$150.75"
        And my included in price taxes should be "$13.70"

    @api @ui
    Scenario: Properly rounded taxes for order with multiple promotions and multiple products with different tax rate
        Given there is a promotion "Clothing promotion"
        And the promotion gives "$5.00" off if order contains a "PHP T-Shirt" product
        And I added product "Symfony Mug" to the cart
        And I added product "PHP T-Shirt" to the cart
        When I check the details of my cart
        Then my cart total should be "$51.95"
        And my included in price taxes should be "$5.47"
