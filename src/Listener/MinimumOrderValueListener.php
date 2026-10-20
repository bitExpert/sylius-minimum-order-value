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

namespace BitExpert\SyliusMinimumOrderValuePlugin\Listener;

use BitExpert\SyliusMinimumOrderValuePlugin\Checker\MinimumOrderValueCheckerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\Transition;

/**
 * Hard guard for every path that applies the checkout "complete" transition (API, custom code).
 *
 * The shop UI validates the same rule up front through
 * {@see \BitExpert\SyliusMinimumOrderValuePlugin\Form\Extension\CompleteTypeExtension},
 * which renders an inline form error instead of hitting this guard.
 */
final readonly class MinimumOrderValueListener
{
    public function __construct(
        private MinimumOrderValueCheckerInterface $minimumOrderValueChecker,
    ) {
    }

    public function guardCheckoutComplete(GuardEvent $event): void
    {
        if ('sylius_order_checkout' !== $event->getWorkflowName()) {
            return;
        }

        if (!$event->getTransition() instanceof Transition || 'complete' !== $event->getTransition()->getName()) {
            return;
        }

        $order = $event->getSubject();
        if (!$order instanceof OrderInterface) {
            return;
        }

        $result = $this->minimumOrderValueChecker->evaluate($order);
        
        if ($result->allowed) {
            return;
        }

        $event->setBlocked(true, $result->message ?? 'The order total is below the minimum order value required for this channel.');
    }
}
