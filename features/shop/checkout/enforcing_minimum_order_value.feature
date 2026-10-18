@bitexpert_minimum_order_value_checkout
Feature: Enforcing minimum order value during checkout
    In order to ensure orders meet minimum requirements
    As a Customer
    I want to be warned if my cart total is below the minimum order value

    Background:
        Given the store has a channel "WEB"
        And the store has currency "Euro"
        And the minimum order value for channel "WEB" is "50.00"
        And a product "PHP T-Shirt" costs "25.00"
        And I am logged in
        And I have "PHP T-Shirt" in my cart

    @api
    Scenario: Cart total below minimum order value
        When I go to checkout
        Then I should see a warning that my order is below the minimum order value

    @ui
    Scenario: Cart total meets minimum order value
        Given my cart total is "50.00"
        When I go to checkout
        Then I should not see a minimum order value warning

    @ui
    Scenario: Completing order below minimum fails
        Given my cart total is "25.00"
        When I try to complete the order
        Then I should see an error that the order total is below minimum
        And I should be returned to the checkout

    @api
    Scenario: Completing order above minimum succeeds
        Given my cart total is "75.00"
        When I complete the order
        Then the order should be completed successfully
