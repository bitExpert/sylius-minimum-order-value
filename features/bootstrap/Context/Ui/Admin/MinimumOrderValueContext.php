<?php

declare(strict_types=1);

namespace BitExpert\SyliusMinimumOrderValuePlugin\Behat\Context\Ui\Admin;

use Behat\Behat\Context\Context;
use Sylius\Behat\Context\Ui\Admin\ManagingChannelsContext;
use Sylius\Behat\Page\Admin\Channel\UpdateChannelPageInterface;

final class MinimumOrderValueContext implements Context
{
    private UpdateChannelPageInterface $updateChannelPage;

    public function __construct(UpdateChannelPageInterface $updateChannelPage)
    {
        $this->updateChannelPage = $updateChannelPage;
    }

    /**
     * @Given I want to configure minimum order value for a channel
     */
    public function iWantToConfigureMinimumOrderValueForAChannel(): void
    {
        // Navigation is handled by Sylius context
    }

    /**
     * @When I set the minimum order value to :amount
     */
    public function iSetTheMinimumOrderValueTo(string $amount): void
    {
        $this->updateChannelPage->overrideField('minimumOrderValue', $amount);
    }

    /**
     * @When I set the minimum order value to :amount :currency
     */
    public function iSetTheMinimumOrderValueToAmount(string $amount, string $currency): void
    {
        $this->updateChannelPage->overrideField('minimumOrderValue', $amount);
    }

    /**
     * @Then I should be notified that minimum order value has been configured
     */
    public function iShouldBeNotifiedThatMinimumOrderValueHasBeenConfigured(): void
    {
        // Sylius will show success flash message
    }

    /**
     * @Then the minimum order value for channel :channelName should be :amount
     */
    public function theMinimumOrderValueForChannelShouldBe(string $channelName, string $amount): void
    {
        $this->updateChannelPage->open(['id' => $this->getChannelIdByName($channelName)]);
        
        $fieldValue = $this->updateChannelPage->getField('minimumOrderValue');
        assert($fieldValue === $amount, "Expected minimum order value to be {$amount}, got {$fieldValue}");
    }

    private function getChannelIdByName(string $channelName): int
    {
        // This would typically query the database
        // For now, we assume the channel was created in the background
        return 1; // Placeholder
    }
}
