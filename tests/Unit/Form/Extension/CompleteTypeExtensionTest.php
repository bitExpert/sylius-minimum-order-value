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

namespace Tests\BitExpert\SyliusMinimumOrderValuePlugin\Unit\Form\Extension;

use BitExpert\SyliusMinimumOrderValuePlugin\Checker\CheckResult;
use BitExpert\SyliusMinimumOrderValuePlugin\Checker\MinimumOrderValueChecker;
use BitExpert\SyliusMinimumOrderValuePlugin\Checker\MinimumOrderValueCheckerInterface;
use BitExpert\SyliusMinimumOrderValuePlugin\Form\Extension\CompleteTypeExtension;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\CoreBundle\Form\Type\Checkout\CompleteType;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Core\Model\OrderInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Tests\BitExpert\SyliusMinimumOrderValuePlugin\Unit\Fixtures\MinimumOrderValueChannel;

final class CompleteTypeExtensionTest extends TestCase
{
    private const TRANSLATION_KEY = 'bitexpert_sylius_minimum_order_value.checkout.error.minimum_order_value';

    public function test_extends_the_checkout_complete_type(): void
    {
        self::assertSame([CompleteType::class], iterator_to_array(CompleteTypeExtension::getExtendedTypes(), false));
    }

    public function test_adds_a_translated_error_when_the_total_is_below_the_minimum(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::once())
            ->method('trans')
            ->with(self::identicalTo(self::TRANSLATION_KEY), self::identicalTo([
                '%minimumOrderValue%' => '50.00',
                '%currency%' => 'EUR',
            ]))
            ->willReturn('translated error message');

        $listener = $this->buildFormAndCaptureListener($translator);

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('addError')
            ->with(self::callback(static function (FormError $error): bool {
                self::assertSame('translated error message', $error->getMessage());

                return true;
            }));

        $listener(new FormEvent($form, $this->createOrder(4999, 'EUR', new MinimumOrderValueChannel(50.0))));
    }

    public function test_does_not_add_an_error_when_the_minimum_is_met(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::never())->method('trans');

        $listener = $this->buildFormAndCaptureListener($translator);

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::never())->method('addError');

        $listener(new FormEvent($form, $this->createOrder(5000, 'EUR', new MinimumOrderValueChannel(50.0))));
    }

    public function test_adds_a_generic_error_when_a_custom_checker_rejects_without_a_channel_minimum(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::once())
            ->method('trans')
            ->with(
                self::identicalTo('bitexpert_sylius_minimum_order_value.checkout.error.minimum_order_value_generic'),
                self::identicalTo(['%currency%' => 'EUR']),
            )
            ->willReturn('translated generic error');

        $rejectingChecker = new class() implements MinimumOrderValueCheckerInterface {
            public function evaluate(OrderInterface $order): CheckResult
            {
                return new CheckResult(false);
            }
        };

        $listener = $this->buildFormAndCaptureListener($translator, $rejectingChecker);

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('addError')
            ->with(self::callback(static function (FormError $error): bool {
                self::assertSame('translated generic error', $error->getMessage());

                return true;
            }));

        // A plain channel without a minimum value; the custom checker alone decides.
        $listener(new FormEvent($form, $this->createOrder(4999, 'EUR', new Channel())));
    }

    public function test_a_custom_checker_can_allow_an_order_below_the_channel_minimum(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::never())->method('trans');

        $allowingChecker = new class() implements MinimumOrderValueCheckerInterface {
            public function evaluate(OrderInterface $order): CheckResult
            {
                return new CheckResult(true);
            }
        };

        $listener = $this->buildFormAndCaptureListener($translator, $allowingChecker);

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::never())->method('addError');

        $listener(new FormEvent($form, $this->createOrder(4999, 'EUR', new MinimumOrderValueChannel(50.0))));
    }

    public function test_does_not_add_an_error_when_the_channel_has_no_restriction(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::never())->method('trans');

        $listener = $this->buildFormAndCaptureListener($translator);

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::never())->method('addError');

        $listener(new FormEvent($form, $this->createOrder(0, 'EUR', new MinimumOrderValueChannel(null))));
    }

    public function test_does_not_add_an_error_for_other_data(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::never())->method('trans');

        $listener = $this->buildFormAndCaptureListener($translator);

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::never())->method('addError');

        $listener(new FormEvent($form, new \stdClass()));
    }

    private function buildFormAndCaptureListener(
        TranslatorInterface $translator,
        ?MinimumOrderValueCheckerInterface $checker = null,
    ): callable {
        $capturedListener = null;

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::once())
            ->method('addEventListener')
            ->with(FormEvents::POST_SUBMIT, self::isType('callable'))
            ->willReturnCallback(static function (string $eventName, callable $listener) use (&$capturedListener, $builder): FormBuilderInterface {
                $capturedListener = $listener;

                return $builder;
            });

        (new CompleteTypeExtension($checker ?? new MinimumOrderValueChecker(), $translator))->buildForm($builder, []);

        self::assertInstanceOf(\Closure::class, $capturedListener);

        return $capturedListener;
    }

    private function createOrder(int $total, string $currencyCode, Channel $channel): OrderInterface
    {
        $order = $this->createMock(OrderInterface::class);
        $order->method('getTotal')->willReturn($total);
        $order->method('getCurrencyCode')->willReturn($currencyCode);
        $order->method('getChannel')->willReturn($channel);

        return $order;
    }
}
