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

namespace Tests\BitExpert\SyliusMinimumOrderValuePlugin\Unit\Twig\Extension;

use BitExpert\SyliusMinimumOrderValuePlugin\Checker\CheckResult;
use BitExpert\SyliusMinimumOrderValuePlugin\Checker\MinimumOrderValueCheckerInterface;
use BitExpert\SyliusMinimumOrderValuePlugin\Twig\Extension\MinimumOrderValueExtension;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Order\Context\CartContextInterface;
use Twig\TwigFunction;

final class MinimumOrderValueExtensionTest extends TestCase
{
    #[Test]
    public function registersTheTwigFunction(): void
    {
        $extension = new MinimumOrderValueExtension(
            $this->createMock(MinimumOrderValueCheckerInterface::class),
            $this->createMock(CartContextInterface::class)
        );

        $functions = $extension->getFunctions();

        self::assertCount(1, $functions);
        self::assertInstanceOf(TwigFunction::class, $functions[0]);
        self::assertSame('minimum_order_value_status', $functions[0]->getName());
    }

    #[Test]
    public function returnsCheckResult(): void
    {
        $checker = $this->createMock(MinimumOrderValueCheckerInterface::class);
        $checker->method('evaluate')->willReturn(new CheckResult(true, null, 50.0));

        $order = $this->createMock(OrderInterface::class);
        $order->method('getCurrencyCode')->willReturn('USD');
        $order->method('getLocaleCode')->willReturn('en_US');

        $extension = new MinimumOrderValueExtension(
            $checker,
            $this->createMock(CartContextInterface::class)
        );

        $result = $extension->getStatus($order);

        self::assertInstanceOf(CheckResult::class, $result);
        self::assertTrue($result->allowed);
        self::assertSame(50.0, $result->minimumOrderValue);
        self::assertSame('USD', $result->currencyCode);
        self::assertSame('en_US', $result->localeCode);
    }

    #[Test]
    public function returnsCheckResultWithMessage(): void
    {
        $checker = $this->createMock(MinimumOrderValueCheckerInterface::class);
        $checker->method('evaluate')->willReturn(new CheckResult(
            false,
            'bitexpert_sylius_minimum_order_value.ui.minimum_order_value_not_reached',
            50.0
        ));

        $order = $this->createMock(OrderInterface::class);
        $order->method('getCurrencyCode')->willReturn('USD');
        $order->method('getLocaleCode')->willReturn('en_US');

        $extension = new MinimumOrderValueExtension(
            $checker,
            $this->createMock(CartContextInterface::class)
        );

        $result = $extension->getStatus($order);

        self::assertFalse($result->allowed);
        self::assertSame('bitexpert_sylius_minimum_order_value.ui.minimum_order_value_not_reached', $result->message);
        self::assertSame(50.0, $result->minimumOrderValue);
        self::assertSame('USD', $result->currencyCode);
        self::assertSame('en_US', $result->localeCode);
    }

    #[Test]
    public function returnsNullWhenNoOrderAndNoCart(): void
    {
        $cartContext = $this->createMock(CartContextInterface::class);
        $cartContext->method('getCart')->willThrowException(new \Sylius\Component\Order\Context\CartNotFoundException());

        $extension = new MinimumOrderValueExtension(
            $this->createMock(MinimumOrderValueCheckerInterface::class),
            $cartContext
        );

        self::assertNull($extension->getStatus());
    }
}
