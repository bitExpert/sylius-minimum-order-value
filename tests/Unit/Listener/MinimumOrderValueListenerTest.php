<?php

declare(strict_types=1);

namespace Tests\BitExpert\SyliusMinimumOrderValuePlugin\Unit\Listener;

use BitExpert\SyliusMinimumOrderValuePlugin\Checker\MinimumOrderValueChecker;
use BitExpert\SyliusMinimumOrderValuePlugin\Listener\MinimumOrderValueListener;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Core\Model\OrderInterface;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\WorkflowInterface;
use Tests\BitExpert\SyliusMinimumOrderValuePlugin\Unit\Fixtures\MinimumOrderValueChannel;

final class MinimumOrderValueListenerTest extends TestCase
{
    private const BLOCKER_MESSAGE = 'The order total is below the minimum order value required for this channel.';

    private MinimumOrderValueListener $listener;

    protected function setUp(): void
    {
        $this->listener = new MinimumOrderValueListener(new MinimumOrderValueChecker());
    }

    public function test_blocks_the_complete_transition_when_the_total_is_below_the_minimum(): void
    {
        $event = $this->createGuardEvent($this->createOrder(4999, new MinimumOrderValueChannel(50.0)));

        $this->listener->guardCheckoutComplete($event);

        self::assertTrue($event->isBlocked());
        $blockers = iterator_to_array($event->getTransitionBlockerList(), false);
        self::assertCount(1, $blockers);
        self::assertSame(self::BLOCKER_MESSAGE, $blockers[0]->getMessage());
    }

    public function test_allows_the_complete_transition_when_the_total_meets_the_minimum(): void
    {
        $event = $this->createGuardEvent($this->createOrder(5000, new MinimumOrderValueChannel(50.0)));

        $this->listener->guardCheckoutComplete($event);

        self::assertFalse($event->isBlocked());
    }

    public function test_allows_the_complete_transition_when_the_channel_is_not_minimum_order_value_aware(): void
    {
        $event = $this->createGuardEvent($this->createOrder(0, new Channel()));

        $this->listener->guardCheckoutComplete($event);

        self::assertFalse($event->isBlocked());
    }

    public function test_allows_the_complete_transition_when_no_minimum_is_configured(): void
    {
        $event = $this->createGuardEvent($this->createOrder(0, new MinimumOrderValueChannel(null)));

        $this->listener->guardCheckoutComplete($event);

        self::assertFalse($event->isBlocked());
    }

    public function test_ignores_events_of_other_workflows(): void
    {
        $event = $this->createGuardEvent(
            $this->createOrder(0, new MinimumOrderValueChannel(50.0)),
            workflowName: 'sylius_order_shipping',
        );

        $this->listener->guardCheckoutComplete($event);

        self::assertFalse($event->isBlocked());
    }

    public function test_ignores_other_transitions(): void
    {
        $event = $this->createGuardEvent(
            $this->createOrder(0, new MinimumOrderValueChannel(50.0)),
            transition: new Transition('select_shipping', 'cart', 'shipping_selected'),
        );

        $this->listener->guardCheckoutComplete($event);

        self::assertFalse($event->isBlocked());
    }

    public function test_ignores_subjects_that_are_not_orders(): void
    {
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

    private function createOrder(int $total, Channel $channel): OrderInterface
    {
        $order = $this->createMock(OrderInterface::class);
        $order->method('getTotal')->willReturn($total);
        $order->method('getChannel')->willReturn($channel);

        return $order;
    }
}
