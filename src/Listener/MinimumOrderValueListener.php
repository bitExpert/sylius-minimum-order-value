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
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Workflow\Event\GuardEvent;

/**
 * Hard guard for every path that applies the checkout "complete" transition (API, custom code).
 *
 * The shop UI validates the same rule up front through
 * {@see \BitExpert\SyliusMinimumOrderValuePlugin\Form\Extension\CompleteTypeExtension},
 * which renders an inline form error instead of hitting this guard.
 *
 * Important: this guard must only block a transition that is actually being
 * performed, not the workflow's "is this transition allowed?" probe.
 * Sylius's {@see \Sylius\Bundle\CoreBundle\Checkout\CheckoutResolver} calls
 * the state machine's "can(complete)" on every request to a checkout page to
 * decide where to redirect the customer. If we blocked on that probe, the
 * customer sitting on the "complete" page (in the "payment_selected" state,
 * the only state that is allowed to run "complete") would be redirected back to
 * the same page again and again, because the state never changes while the
 * cart is below the minimum. We therefore only enforce the rule on real
 * transitions (see {@see self::isBeingPerformed()}).
 */
final readonly class MinimumOrderValueListener
{
    public function __construct(
        private MinimumOrderValueCheckerInterface $minimumOrderValueChecker,
        private RequestStack $requestStack,
    ) {
    }

    public function guardCheckoutComplete(GuardEvent $event): void
    {
        if ('sylius_order_checkout' !== $event->getWorkflowName()) {
            return;
        }

        if ('complete' !== $event->getTransition()->getName()) {
            return;
        }

        $order = $event->getSubject();
        if (!$order instanceof OrderInterface) {
            return;
        }

        if (!$this->isBeingPerformed()) {
            return;
        }

        $result = $this->minimumOrderValueChecker->evaluate($order);

        if ($result->allowed) {
            return;
        }

        $event->setBlocked(true, $result->message ?? 'The items total is below the minimum order value required for this channel.');
    }

    /**
     * The workflow dispatches this guard both for "can(complete)" (a read-only
     * probe) and for the real "apply(complete)" transition. In a browser the
     * probe always happens during a safe request (a GET that renders the page),
     * while a real transition is a state-changing request (the "PUT" that the
     * complete form submits) or a request-less invocation (a console command or
     * a messenger worker). We only block the latter.
     */
    private function isBeingPerformed(): bool
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            return true;
        }

        return !$request->isMethodSafe();
    }
}
