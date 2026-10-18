<?php

declare(strict_types=1);

namespace Tests\BitExpert\SyliusMinimumOrderValuePlugin\Unit\Checker;

use BitExpert\SyliusMinimumOrderValuePlugin\Checker\MinimumOrderValueChecker;
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

    public function test_returns_no_restriction_when_the_channel_is_not_minimum_order_value_aware(): void
    {
        $order = $this->createOrder(4999, new Channel());

        self::assertNull($this->checker->findMinimumOrderValue($order));
        self::assertFalse($this->checker->isBelowMinimumOrderValue($order));
    }

    public function test_returns_no_restriction_when_no_minimum_order_value_is_configured(): void
    {
        $order = $this->createOrder(4999, new MinimumOrderValueChannel(null));

        self::assertNull($this->checker->findMinimumOrderValue($order));
        self::assertFalse($this->checker->isBelowMinimumOrderValue($order));
    }

    public function test_treats_zero_as_no_restriction(): void
    {
        $order = $this->createOrder(1, new MinimumOrderValueChannel(0.0));

        self::assertNull($this->checker->findMinimumOrderValue($order));
        self::assertFalse($this->checker->isBelowMinimumOrderValue($order));
    }

    public function test_treats_a_negative_value_as_no_restriction(): void
    {
        $order = $this->createOrder(1, new MinimumOrderValueChannel(-10.0));

        self::assertNull($this->checker->findMinimumOrderValue($order));
        self::assertFalse($this->checker->isBelowMinimumOrderValue($order));
    }

    public function test_detects_a_total_below_the_minimum_one_cent_below_the_threshold(): void
    {
        // 49.99 stored as 4999 in the smallest currency unit vs a 50.00 minimum.
        $order = $this->createOrder(4999, new MinimumOrderValueChannel(50.0));

        self::assertTrue($this->checker->isBelowMinimumOrderValue($order));
    }

    public function test_allows_a_total_meeting_the_minimum_exactly(): void
    {
        $order = $this->createOrder(5000, new MinimumOrderValueChannel(50.0));

        self::assertFalse($this->checker->isBelowMinimumOrderValue($order));
    }

    public function test_allows_a_total_above_the_minimum(): void
    {
        $order = $this->createOrder(7250, new MinimumOrderValueChannel(50.0));

        self::assertFalse($this->checker->isBelowMinimumOrderValue($order));
    }

    public function test_compares_with_full_cent_precision(): void
    {
        $below = $this->createOrder(1998, new MinimumOrderValueChannel(19.99));
        $atThreshold = $this->createOrder(1999, new MinimumOrderValueChannel(19.99));

        self::assertTrue($this->checker->isBelowMinimumOrderValue($below));
        self::assertFalse($this->checker->isBelowMinimumOrderValue($atThreshold));
    }

    private function createOrder(int $total, Channel $channel): OrderInterface
    {
        $order = $this->createMock(OrderInterface::class);
        $order->method('getTotal')->willReturn($total);
        $order->method('getChannel')->willReturn($channel);

        return $order;
    }
}
