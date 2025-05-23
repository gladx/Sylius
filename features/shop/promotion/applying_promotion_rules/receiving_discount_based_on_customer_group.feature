@applying_promotion_rules
Feature: Receiving discount based on customer group
    In order to apply discount only for selected customer group
    As a Visitor
    I want to have a discount applied when I belong to a specific customer group

    Background:
        Given the store operates on a single channel in "United States"
        And the store has a product "PHP T-Shirt" priced at "$80.00"
        And the store has a customer group "Wholesale"
        And there is a promotion "Wholesale promotion"
        And the promotion gives "10%" off the order for customers from "Wholesale" group

    @api @ui
    Scenario: Not logged in customer should not receive discount
        Given I added product "PHP T-Shirt" to the cart
        When I check the details of my cart
        Then my cart total should be "$80.00"
        And there should be no discount applied

    @api @ui
    Scenario: Receiving discounts when belonging to a specific customer group
        Given there is a customer account "wholesale@sylius.com"
        And the customer belongs to group "Wholesale"
        And I am logged in as "wholesale@sylius.com"
        And I added product "PHP T-Shirt" to the cart
        When I check the details of my cart
        Then my cart total should be "$72.00"
        And my discount should be "-$8.00"

    @api @ui
    Scenario: Not receiving discount when belonging to a different customer group that specified in the promotion
        Given the store has a customer group "Retail"
        And there is a customer account "retail@sylius.com"
        And the customer belongs to group "Retail"
        And I am logged in as "retail@sylius.com"
        And I added product "PHP T-Shirt" to the cart
        When I check the details of my cart
        Then my cart total should be "$80.00"
        And there should be no discount applied
