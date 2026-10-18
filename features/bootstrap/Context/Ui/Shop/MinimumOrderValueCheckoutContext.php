<?php

declare(strict_types=1);

namespace BitExpert\SyliusMinimumOrderValuePlugin\Behat\Context\Ui\Shop;

use Behat\Behat\Context\Context;
use Sylius\Behat\Context\Ui\Shop\ManagingCartContext;
use Sylius\Behat\Page\Shop\CartPageInterface;

final class MinimumOrderValueCheckoutContext implements Context
{
    private CartPageInterface $cartPage;

    public function __construct(CartPageInterface $cartPage)
    {
        $this->cartPage = $cartPage;
    }

    /**
     * @Given the minimum order value for this channel is :amount
     */
    public function theMinimumOrderValueForThisChannelIs(string $amount): void
    {
        // This would typically set up the minimum order value in the database
    }

    /**
     * @Given my cart total is :amount
     */
    public function myCartTotalIs(string $amount): void
    {
        // This would typically add items to the cart to reach the specified amount
    }

    /**
     * @Then I should see a warning that my order is below the minimum order value
     */
    public function iShouldSeeAWarningThatMyOrderIsBelowTheMinimumOrderValue(): void
    {
        $warningText = $this->cartPage->getWarningMessage();
        
        assert($warningText !== '', 'No warning message found');
        assert(str_contains($warningText, 'minimum order value'), 'Warning does not mention minimum order value');
    }

    /**
     * @Then I should not see a minimum order value warning
     */
    public function iShouldNotSeeAMinimumOrderValueWarning(): void
    {
        $warningText = $this->cartPage->getWarningMessage();
        
        assert($warningText === '', 'Unexpected warning message found');
    }

    /**
     * @Given I have configured minimum order value to :amount for this channel
     */
    public function iHaveConfiguredMinimumOrderValueToForThisChannel(string $amount): void
    {
        // This would typically set up the minimum order value in the database
    }
}
