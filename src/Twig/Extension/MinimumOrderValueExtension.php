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

namespace BitExpert\SyliusMinimumOrderValuePlugin\Twig\Extension;

use BitExpert\SyliusMinimumOrderValuePlugin\Checker\CheckResult;
use BitExpert\SyliusMinimumOrderValuePlugin\Checker\MinimumOrderValueCheckerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Order\Context\CartContextInterface;
use Sylius\Component\Order\Context\CartNotFoundException;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Exposes the minimum order value status to templates via Twig functions.
 */
final class MinimumOrderValueExtension extends AbstractExtension
{
    public function __construct(
        private readonly MinimumOrderValueCheckerInterface $checker,
        private readonly CartContextInterface $cartContext,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'minimum_order_value_status',
                fn (?OrderInterface $order = null, ?string $currencyCode = null, ?string $localeCode = null): CheckResult|null => $this->getStatus($order, $currencyCode, $localeCode),
            ),
        ];
    }

    public function getStatus(?OrderInterface $order = null, ?string $currencyCode = null, ?string $localeCode = null): ?CheckResult
    {
        $order = $order ?? $this->getCart();

        if (!$order instanceof OrderInterface) {
            return null;
        }

        $result = $this->checker->evaluate($order);

        // If currency/locale not provided, try to get from order
        if ($currencyCode === null || $localeCode === null) {
            $currencyCode = $currencyCode ?? $order->getCurrencyCode();
            $localeCode = $localeCode ?? $order->getLocaleCode();
        }

        return new CheckResult($result->allowed, $result->message, $result->minimumOrderValue, $currencyCode, $localeCode);
    }

    private function getCart(): ?OrderInterface
    {
        try {
            $cart = $this->cartContext->getCart();
        } catch (CartNotFoundException $e) {
            return null;
        }

        return $cart instanceof OrderInterface ? $cart : null;
    }
}
