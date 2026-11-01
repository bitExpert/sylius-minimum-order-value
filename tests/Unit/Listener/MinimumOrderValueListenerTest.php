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

namespace Tests\BitExpert\SyliusMinimumOrderValuePlugin\Unit\Listener;

use BitExpert\SyliusMinimumOrderValuePlugin\Checker\MinimumOrderValueChecker;
use BitExpert\SyliusMinimumOrderValuePlugin\Listener\MinimumOrderValueListener;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Core\Model\OrderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\WorkflowInterface;
use Tests\BitExpert\SyliusMinimumOrderValuePlugin\Unit\Fixtures\MinimumOrderValueChannel;

final class MinimumOrderValueListenerTest extends TestCase
{
    private const BLOCKER_MESSAGE = 'bitexpert_sylius_minimum_order_value.ui.minimum_order_value_not_reached';

    private MinimumOrderValueListener $listener;

    private RequestStack $requestStack;

    protected function setUp(): void
    {
        $this->requestStack = new RequestStack();
        $this->listener = new MinimumOrderValueListener(new MinimumOrderValueChecker(), $this->requestStack);
    }

    #[Test]
    public function blocksTheCompleteTransitionWhenTheItemsTotalIsBelowTheMinimum(): void
    {
        $this->pushRequest('PUT');
        $event = $this->createGuardEvent($this->createOrder(4999, new MinimumOrderValueChannel(50.0)));

        $this->listener->guardCheckoutComplete($event);

        self::assertTrue($event->isBlocked());
        $blockers = iterator_to_array($event->getTransitionBlockerList(), false);
        self::assertCount(1, $blockers);
        self::assertSame(self::BLOCKER_MESSAGE, $blockers[0]->getMessage());
    }

    #[Test]
    public function allowsTheCompleteTransitionWhenTheItemsTotalMeetsTheMinimum(): void
    {
        $this->pushRequest('PUT');
        $event = $this->createGuardEvent($this->createOrder(5000, new MinimumOrderValueChannel(50.0)));

        $this->listener->guardCheckoutComplete($event);

        self::assertFalse($event->isBlocked());
    }

    #[Test]
    public function allowsTheCompleteTransitionWhenTheChannelIsNotMinimumOrderValueAware(): void
    {
        $this->pushRequest('PUT');
        $event = $this->createGuardEvent($this->createOrder(0, new Channel()));

        $this->listener->guardCheckoutComplete($event);

        self::assertFalse($event->isBlocked());
    }

    #[Test]
    public function allowsTheCompleteTransitionWhenNoMinimumIsConfigured(): void
    {
        $this->pushRequest('PUT');
        $event = $this->createGuardEvent($this->createOrder(0, new MinimumOrderValueChannel(null)));

        $this->listener->guardCheckoutComplete($event);

        self::assertFalse($event->isBlocked());
    }

    /**
     * The workflow's "can(complete)" probe (a GET rendering the page) must not
     * be blocked, even when the cart is below the minimum - otherwise the
     * checkout resolver would redirect the customer back to the same page in an
     * endless loop.
     */
    #[Test]
    public function doesNotBlockTheProbeWhenRenderingThePage(): void
    {
        $this->pushRequest('GET');
        $event = $this->createGuardEvent($this->createOrder(4999, new MinimumOrderValueChannel(50.0)));

        $this->listener->guardCheckoutComplete($event);

        self::assertFalse($event->isBlocked());
    }

    #[Test]
    public function blocksTheCompleteTransitionWithoutARequest(): void
    {
        // No request in the stack, as in a console command or messenger worker.
        $event = $this->createGuardEvent($this->createOrder(4999, new MinimumOrderValueChannel(50.0)));

        $this->listener->guardCheckoutComplete($event);

        self::assertTrue($event->isBlocked());
    }

    #[Test]
    public function ignoresEventsOfOtherWorkflows(): void
    {
        $this->pushRequest('PUT');
        $event = $this->createGuardEvent(
            $this->createOrder(0, new MinimumOrderValueChannel(50.0)),
            workflowName: 'sylius_order_shipping',
        );

        $this->listener->guardCheckoutComplete($event);

        self::assertFalse($event->isBlocked());
    }

    #[Test]
    public function ignoresOtherTransitions(): void
    {
        $this->pushRequest('PUT');
        $event = $this->createGuardEvent(
            $this->createOrder(0, new MinimumOrderValueChannel(50.0)),
            transition: new Transition('select_shipping', 'cart', 'shipping_selected'),
        );

        $this->listener->guardCheckoutComplete($event);

        self::assertFalse($event->isBlocked());
    }

    #[Test]
    public function ignoresSubjectsThatAreNotOrders(): void
    {
        $this->pushRequest('PUT');
        $event = $this->createGuardEvent(new \stdClass());

        $this->listener->guardCheckoutComplete($event);

        self::assertFalse($event->isBlocked());
    }

    private function createGuardEvent(
        object $subject,
        string $workflowName = 'sylius_order_checkout',
        ?Transition $transition = null,
    ): GuardEvent {
        $workflow = $this->createMock(WorkflowInterface::class);
        $workflow->method('getName')->willReturn($workflowName);

        return new GuardEvent(
            $subject,
            new Marking(['cart' => 1]),
            $transition ?? new Transition('complete', 'cart', 'completed'),
            $workflow,
        );
    }

    private function createOrder(int $itemsTotal, Channel $channel): OrderInterface
    {
        $order = $this->createMock(OrderInterface::class);
        $order->method('getItemsTotal')->willReturn($itemsTotal);
        $order->method('getChannel')->willReturn($channel);

        return $order;
    }

    private function pushRequest(string $method): void
    {
        $this->requestStack->push($this->createRequest($method));
    }

    private function createRequest(string $method): Request
    {
        $request = Request::create('/en_US/checkout/complete', $method);

        if ('POST' === $method) {
            // The complete form submits a POST with a hidden _method=PUT field.
            $request->request->set('_method', 'PUT');
        }

        return $request;
    }
}
