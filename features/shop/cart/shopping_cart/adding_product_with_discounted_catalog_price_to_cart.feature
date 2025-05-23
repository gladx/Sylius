@shopping_cart
Feature: Adding a simple product with discounted catalog price to the cart
    In order to select products with proper price
    As a Visitor
    I want to be able to add simple products with discounted catalog price to the cart

    Background:
        Given the store operates on a single channel in "United States"
        And the store has a product "Mug" priced at "$40.00"
        And the store has a product "T-Shirt" priced at "$20.00"
        And there is a catalog promotion "Winter sale" that reduces price by "25%" and applies on "T-Shirt" variant

    @api @ui
    Scenario: Adding a simple product with discounted catalog price to the cart
        Given I added product "T-Shirt" to the cart
        And I added product "Mug" to the cart
        When I check the details of my cart
        And I should see "T-Shirt" with unit price "$15.00" in my cart
        And I should see "T-Shirt" with original price "$20.00" in my cart
        And I should see "Mug" only with unit price "$40.00" in my cart

    @api @ui
    Scenario: Adding a simple product with catalog and cart promotion to the cart
        Given there is a promotion "Cheap Stuff"
        And this promotion gives "50%" off on every product when the item total is at least "$5.00"
        And I added product "T-Shirt" to the cart
        And I added product "Mug" to the cart
        When I check the details of my cart
        And I should see "T-Shirt" with discounted unit price "$7.50" in my cart
        And I should see "T-Shirt" with original price "$20.00" in my cart
        And I should see "Mug" with discounted unit price "$20.00" in my cart
