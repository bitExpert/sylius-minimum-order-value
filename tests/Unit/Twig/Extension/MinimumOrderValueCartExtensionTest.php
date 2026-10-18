<?php

declare(strict_types=1);

namespace Tests\BitExpert\SyliusMinimumOrderValuePlugin\Unit\Twig\Extension;

use BitExpert\SyliusMinimumOrderValuePlugin\Twig\Extension\MinimumOrderValueCartExtension;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Order\Context\CartContextInterface;
use Sylius\Component\Order\Context\CartNotFoundException;
use Sylius\Component\Order\Model\OrderInterface as ComponentOrderInterface;
use Twig\TwigFunction;

final class MinimumOrderValueCartExtensionTest extends TestCase
{
    public function test_registers_the_twig_function(): void
    {
        $extension = new MinimumOrderValueCartExtension($this->createMock(CartContextInterface::class));

        $functions = $extension->getFunctions();

        self::assertCount(1, $functions);
        self::assertInstanceOf(TwigFunction::class, $functions[0]);
        self::assertSame('bitexpert_minimum_order_value_cart', $functions[0]->getName());
    }

    public function test_returns_the_cart_provided_by_the_cart_context(): void
    {
        $cart = $this->createMock(OrderInterface::class);

        $cartContext = $this->createMock(CartContextInterface::class);
        $cartContext->method('getCart')->willReturn($cart);

        $extension = new MinimumOrderValueCartExtension($cartContext);

        self::assertSame($cart, $extension->getCart());
    }

    public function test_returns_null_when_the_cart_cannot_be_found(): void
    {
        $cartContext = $this->createMock(CartContextInterface::class);
        $cartContext->method('getCart')->willThrowException(new CartNotFoundException());

        $extension = new MinimumOrderValueCartExtension($cartContext);

        self::assertNull($extension->getCart());
    }

    public function test_returns_null_when_the_cart_is_not_a_core_order(): void
    {
        $cartContext = $this->createMock(CartContextInterface::class);
        $cartContext->method('getCart')->willReturn($this->createMock(ComponentOrderInterface::class));

        $extension = new MinimumOrderValueCartExtension($cartContext);

        self::assertNull($extension->getCart());
    }
}
