@checkout
Feature: Restricting list of countries available for addressing
    In order to make choosing countries easier
    As a Customer
    I want to have only available countries listed

    Background:
        Given the store operates on a single channel in "United States"
        And the store operates in "Poland"
        And the store has a product "PHP T-Shirt" priced at "$19.99"
        And the store ships everywhere for Free
        And I am a logged in customer

    @no-api @ui
    Scenario: Having only countries available for current channel listed
        Given this channel operates in the "United States" country
        And I added product "PHP T-Shirt" to the cart
        When I go to the checkout addressing step
        Then I should have only "United States" country available to choose from

    @no-api @ui
    Scenario: Having all the countries listed if channel does not define available ones
        Given this channel does not define operating countries
        And I added product "PHP T-Shirt" to the cart
        When I go to the checkout addressing step
        Then I should have both "United States" and "Poland" countries available to choose from
