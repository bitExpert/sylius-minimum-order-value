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

use BitExpert\SyliusMinimumOrderValuePlugin\Entity\MinimumOrderValueAwareInterface;
use Sylius\Component\Core\Model\OrderInterface;

/**
 * Sylius stores monetary amounts such as order totals in the smallest currency unit (e.g. cents), while
 * the minimum order value configured on the channel is stored in major units (e.g. 49.99).
 * This checker converts the configured minimum to the smallest currency unit before comparing it with the total.
 */
final readonly class MinimumOrderValueChecker implements MinimumOrderValueCheckerInterface
{
    public function findMinimumOrderValue(OrderInterface $order): ?float
    {
        $channel = $order->getChannel();
        if (!$channel instanceof MinimumOrderValueAwareInterface) {
            return null;
        }

        $minimumOrderValue = $channel->getMinimumOrderValue();

        return (null !== $minimumOrderValue && $minimumOrderValue > 0.0) ? $minimumOrderValue : null;
    }

    public function evaluate(OrderInterface $order): CheckResult
    {
        $minimumOrderValue = $this->findMinimumOrderValue($order);

        if (null === $minimumOrderValue) {
            return new CheckResult(true);
        }

        $isBelow = $order->getTotal() < (int) round($minimumOrderValue * 100);

        if ($isBelow) {
            return new CheckResult(
                false,
                'bitexpert_sylius_minimum_order_value.ui.minimum_order_value_not_reached',
                $minimumOrderValue,
            );
        }

        return new CheckResult(true, null, $minimumOrderValue);
    }
}
