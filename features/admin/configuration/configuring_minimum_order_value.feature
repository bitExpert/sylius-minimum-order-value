@bitexpert_minimum_order_value
Feature: Configuring minimum order value per channel
    In order to enforce minimum order values
    As an Administrator
    I want to configure minimum order values for different channels

    Background:
        Given the store has a channel "WEB"
        And I am logged in as an administrator

    @api
    Scenario: Configuring minimum order value for a channel
        When I want to edit the "WEB" channel
        And I set the minimum order value to "50.00"
        And I save my configuration
        Then I should be notified that the channel has been updated
        And the minimum order value for channel "WEB" should be "50.00"

    @api
    Scenario: Viewing minimum order value configuration
        Given the minimum order value for channel "WEB" is "25.00"
        When I want to edit the "WEB" channel
        Then I should see the minimum order value is "25.00"

    @ui
    Scenario: Removing minimum order value
        Given the minimum order value for channel "WEB" is "25.00"
        When I want to edit the "WEB" channel
        And I clear the minimum order value
        And I save my configuration
        Then I should be notified that the channel has been updated
