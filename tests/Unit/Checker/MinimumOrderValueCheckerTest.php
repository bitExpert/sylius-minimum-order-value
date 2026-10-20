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

namespace Tests\BitExpert\SyliusMinimumOrderValuePlugin\Unit\Checker;

use BitExpert\SyliusMinimumOrderValuePlugin\Checker\CheckResult;
use BitExpert\SyliusMinimumOrderValuePlugin\Checker\MinimumOrderValueChecker;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Core\Model\OrderInterface;
use Tests\BitExpert\SyliusMinimumOrderValuePlugin\Unit\Fixtures\MinimumOrderValueChannel;

final class MinimumOrderValueCheckerTest extends TestCase
{
    private MinimumOrderValueChecker $checker;

    protected function setUp(): void
    {
        $this->checker = new MinimumOrderValueChecker();
    }

    #[Test]
    public function returnsNoRestrictionWhenTheChannelIsNotMinimumOrderValueAware(): void
    {
        $order = $this->createOrder(4999, new Channel());

        self::assertNull($this->checker->findMinimumOrderValue($order));
        $result = $this->checker->evaluate($order);
        self::assertInstanceOf(CheckResult::class, $result);
        self::assertTrue($result->allowed);
        self::assertNull($result->message);
        self::assertNull($result->minimumOrderValue);
    }

    #[Test]
    public function returnsNoRestrictionWhenNoMinimumOrderValueIsConfigured(): void
    {
        $order = $this->createOrder(4999, new MinimumOrderValueChannel(null));

        self::assertNull($this->checker->findMinimumOrderValue($order));
        $result = $this->checker->evaluate($order);
        self::assertTrue($result->allowed);
        self::assertNull($result->message);
        self::assertNull($result->minimumOrderValue);
    }

    #[Test]
    public function treatsZeroAsNoRestriction(): void
    {
        $order = $this->createOrder(1, new MinimumOrderValueChannel(0.0));

        self::assertNull($this->checker->findMinimumOrderValue($order));
        $result = $this->checker->evaluate($order);
        self::assertTrue($result->allowed);
        self::assertNull($result->message);
        self::assertNull($result->minimumOrderValue);
    }

    #[Test]
    public function treatsANegativeValueAsNoRestriction(): void
    {
        $order = $this->createOrder(1, new MinimumOrderValueChannel(-10.0));

        self::assertNull($this->checker->findMinimumOrderValue($order));
        $result = $this->checker->evaluate($order);
        self::assertTrue($result->allowed);
        self::assertNull($result->message);
        self::assertNull($result->minimumOrderValue);
    }

    #[Test]
    public function detectsATotalBelowTheMinimumOneCentBelowTheThreshold(): void
    {
        // 49.99 stored as 4999 in the smallest currency unit vs a 50.00 minimum.
        $order = $this->createOrder(4999, new MinimumOrderValueChannel(50.0));

        $result = $this->checker->evaluate($order);
        self::assertFalse($result->allowed);
        self::assertNotNull($result->message);
        self::assertEquals('bitexpert_sylius_minimum_order_value.ui.minimum_order_value_not_reached', $result->message);
        self::assertSame(50.0, $result->minimumOrderValue);
    }

    #[Test]
    public function allowsATotalMeetingTheMinimumExactly(): void
    {
        $order = $this->createOrder(5000, new MinimumOrderValueChannel(50.0));

        $result = $this->checker->evaluate($order);
        self::assertTrue($result->allowed);
        self::assertNull($result->message);
        self::assertSame(50.0, $result->minimumOrderValue);
    }

    #[Test]
    public function allowsATotalAboveTheMinimum(): void
    {
        $order = $this->createOrder(7250, new MinimumOrderValueChannel(50.0));

        $result = $this->checker->evaluate($order);
        self::assertTrue($result->allowed);
        self::assertNull($result->message);
        self::assertSame(50.0, $result->minimumOrderValue);
    }

    #[Test]
    public function comparesWithFullCentPrecision(): void
    {
        $below = $this->createOrder(1998, new MinimumOrderValueChannel(19.99));
        $atThreshold = $this->createOrder(1999, new MinimumOrderValueChannel(19.99));

        $resultBelow = $this->checker->evaluate($below);
        $resultAtThreshold = $this->checker->evaluate($atThreshold);

        self::assertFalse($resultBelow->allowed);
        self::assertTrue($resultAtThreshold->allowed);
        self::assertSame(19.99, $resultBelow->minimumOrderValue);
        self::assertSame(19.99, $resultAtThreshold->minimumOrderValue);
    }

    private function createOrder(int $total, Channel $channel): OrderInterface
    {
        $order = $this->createMock(OrderInterface::class);
        $order->method('getTotal')->willReturn($total);
        $order->method('getChannel')->willReturn($channel);

        return $order;
    }
}
