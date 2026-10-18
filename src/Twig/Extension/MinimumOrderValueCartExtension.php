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

use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Order\Context\CartContextInterface;
use Sylius\Component\Order\Context\CartNotFoundException;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Exposes the current cart to the shop templates.
 *
 * Templates rendered inside a Sylius cart LiveComponent must not rely on
 * `hookable_metadata.context.cart`. The cart only reaches that context through
 * the parent hook chain, and on every LiveComponent re-render
 * `HookableLiveComponentTrait::hydrateHookableMetadata()` rebuilds the metadata
 * with an empty context, so everything inherited from the page-level Twig
 * context is dropped. Resolving the cart from the cart context instead keeps the
 * template working both on the initial page load and on every re-render.
 */
final class MinimumOrderValueCartExtension extends AbstractExtension
{
    public function __construct(private readonly CartContextInterface $cartContext)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('bitexpert_minimum_order_value_cart', [$this, 'getCart']),
        ];
    }

    /**
     * Retrieves the current cart from the cart context.
     *
     * @return OrderInterface|null The cart if found, null otherwise
     */
    public function getCart(): ?OrderInterface
    {
        try {
            $cart = $this->cartContext->getCart();
        } catch (CartNotFoundException $e) {
            return null;
        }

        return $cart instanceof OrderInterface ? $cart : null;
    }
}
