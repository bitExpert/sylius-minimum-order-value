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

namespace BitExpert\SyliusMinimumOrderValuePlugin\Form\Extension;

use BitExpert\SyliusMinimumOrderValuePlugin\Checker\MinimumOrderValueCheckerInterface;
use BitExpert\SyliusMinimumOrderValuePlugin\Entity\MinimumOrderValueAwareInterface;
use Sylius\Bundle\CoreBundle\Form\Type\Checkout\CompleteType;
use Sylius\Component\Core\Model\OrderInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Rejects the checkout completion form when the order total is below the
 * minimum order value configured for the channel, so the customer stays on the
 * checkout page and sees an inline error instead of a failed state machine
 * transition.
 */
final class CompleteTypeExtension extends AbstractTypeExtension
{
    public function __construct(
        private readonly MinimumOrderValueCheckerInterface $minimumOrderValueChecker,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event): void {
            $order = $event->getData();
            if (!$order instanceof OrderInterface) {
                return;
            }

            if (!$this->minimumOrderValueChecker->isBelowMinimumOrderValue($order)) {
                return;
            }

            $event->getForm()->addError(new FormError($this->createErrorMessage($order)));
        });
    }

    /**
     * @return iterable<class-string>
     */
    public static function getExtendedTypes(): iterable
    {
        yield CompleteType::class;
    }

    /**
     * The configured minimum is read from the channel for display purposes only;
     * whether the order is rejected is decided exclusively by the checker.
     * A custom checker that does not derive its limit from the channel falls back
     * to the message without an amount.
     */
    private function createErrorMessage(OrderInterface $order): string
    {
        $minimumOrderValue = $this->resolveConfiguredMinimum($order);

        if (null === $minimumOrderValue) {
            return $this->translator->trans(
                'bitexpert_sylius_minimum_order_value.checkout.error.minimum_order_value_generic',
                ['%currency%' => (string) $order->getCurrencyCode()],
            );
        }

        return $this->translator->trans(
            'bitexpert_sylius_minimum_order_value.checkout.error.minimum_order_value',
            [
                '%minimumOrderValue%' => number_format($minimumOrderValue, 2, '.', ''),
                '%currency%' => (string) $order->getCurrencyCode(),
            ],
        );
    }

    private function resolveConfiguredMinimum(OrderInterface $order): ?float
    {
        $channel = $order->getChannel();
        if (!$channel instanceof MinimumOrderValueAwareInterface) {
            return null;
        }

        $minimumOrderValue = $channel->getMinimumOrderValue();

        return (null !== $minimumOrderValue && $minimumOrderValue > 0.0) ? $minimumOrderValue : null;
    }
}
