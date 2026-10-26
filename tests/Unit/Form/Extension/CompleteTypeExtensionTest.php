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
use PHPUnit\Framework\Attributes\Test;
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

    #[Test]
    public function extendsTheCheckoutCompleteType(): void
    {
        self::assertSame([CompleteType::class], iterator_to_array(CompleteTypeExtension::getExtendedTypes(), false));
    }

    #[Test]
    public function addsATranslatedErrorWhenTheTotalIsBelowTheMinimum(): void
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

    #[Test]
    public function doesNotAddAnErrorWhenTheMinimumIsMet(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::never())->method('trans');

        $listener = $this->buildFormAndCaptureListener($translator);

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::never())->method('addError');

        $listener(new FormEvent($form, $this->createOrder(5000, 'EUR', new MinimumOrderValueChannel(50.0))));
    }

    #[Test]
    public function addsAGenericErrorWhenACustomCheckerRejectsWithoutAChannelMinimum(): void
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

    #[Test]
    public function aCustomCheckerCanAllowAnOrderBelowTheChannelMinimum(): void
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

    #[Test]
    public function doesNotAddAnErrorWhenTheChannelHasNoRestriction(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::never())->method('trans');

        $listener = $this->buildFormAndCaptureListener($translator);

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::never())->method('addError');

        $listener(new FormEvent($form, $this->createOrder(0, 'EUR', new MinimumOrderValueChannel(null))));
    }

    #[Test]
    public function doesNotAddAnErrorForOtherData(): void
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
        $order->method('getItemsTotal')->willReturn($total);
        $order->method('getCurrencyCode')->willReturn($currencyCode);
        $order->method('getChannel')->willReturn($channel);

        return $order;
    }
}
