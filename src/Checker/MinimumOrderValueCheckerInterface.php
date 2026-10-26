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

namespace BitExpert\SyliusMinimumOrderValuePlugin\Checker;

use Sylius\Component\Core\Model\OrderInterface;

/**
 * Contract for evaluating whether an order reaches the configured minimum order value.
 *
 * The default implementation ({@see MinimumOrderValueChecker}) compares the order's items total
 * (smallest currency unit, e.g. cents, excluding shipping and order-level discounts) with the
 * minimum configured on the channel (major units, e.g. 49.99). If your business rules differ -
 * for example you need to include shipping or evaluate per product category - replace the
 * service with your own implementation:
 *
 * ```yaml
 * services:
 *     BitExpert\SyliusMinimumOrderValuePlugin\Checker\MinimumOrderValueCheckerInterface:
 *         class: App\Checker\CustomMinimumOrderValueChecker
 * ```
 */
interface MinimumOrderValueCheckerInterface
{
    /**
     * Evaluate the given order and return a result with permission status and optional message.
     *
     * @param OrderInterface $order The order to evaluate.
     */
    public function evaluate(OrderInterface $order): CheckResult;
}
