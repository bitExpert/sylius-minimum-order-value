<?php

/*
 * This file is part of the Sylius Minimum Order Value package.
 *
 * (c) bitExpert AG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Tests\BitExpert\SyliusMinimumOrderValuePlugin\Unit\Entity;

use BitExpert\SyliusMinimumOrderValuePlugin\Entity\MinimumOrderValueAwareInterface;
use BitExpert\SyliusMinimumOrderValuePlugin\Entity\MinimumOrderValueTrait;
use PHPUnit\Framework\TestCase;

final class MinimumOrderValueTraitTest extends TestCase
{
    public function test_returns_null_when_no_value_has_been_configured(): void
    {
        $channel = new ChannelWithMinimumOrderValue();

        self::assertNull($channel->getMinimumOrderValue());
    }

    public function test_sets_and_gets_the_minimum_order_value(): void
    {
        $channel = new ChannelWithMinimumOrderValue();
        $channel->setMinimumOrderValue(1234.56);

        self::assertSame(1234.56, $channel->getMinimumOrderValue());
    }

    public function test_stores_the_value_as_a_decimal_string_with_two_decimal_places(): void
    {
        $channel = new ChannelWithMinimumOrderValue();
        $channel->setMinimumOrderValue(50.5);

        $property = new \ReflectionProperty($channel, 'minimumOrderValue');

        self::assertSame('50.50', $property->getValue($channel));
    }

    public function test_clears_the_value_when_null_is_set(): void
    {
        $channel = new ChannelWithMinimumOrderValue();
        $channel->setMinimumOrderValue(50.0);
        $channel->setMinimumOrderValue(null);

        self::assertNull($channel->getMinimumOrderValue());
        self::assertNull((new \ReflectionProperty($channel, 'minimumOrderValue'))->getValue($channel));
    }
}

final class ChannelWithMinimumOrderValue implements MinimumOrderValueAwareInterface
{
    use MinimumOrderValueTrait;
}
