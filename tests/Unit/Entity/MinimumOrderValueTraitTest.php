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
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MinimumOrderValueTraitTest extends TestCase
{
    #[Test]
    public function returnsNullWhenNoValueHasBeenConfigured(): void
    {
        $channel = new ChannelWithMinimumOrderValue();

        self::assertNull($channel->getMinimumOrderValue());
    }

    #[Test]
    public function setsAndGetsTheMinimumOrderValue(): void
    {
        $channel = new ChannelWithMinimumOrderValue();
        $channel->setMinimumOrderValue(1234.56);

        self::assertSame(1234.56, $channel->getMinimumOrderValue());
    }

    #[Test]
    public function storesTheValueAsADecimalStringWithTwoDecimalPlaces(): void
    {
        $channel = new ChannelWithMinimumOrderValue();
        $channel->setMinimumOrderValue(50.5);

        $property = new \ReflectionProperty($channel, 'minimumOrderValue');

        self::assertSame('50.50', $property->getValue($channel));
    }

    #[Test]
    public function clearsTheValueWhenNullIsSet(): void
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
