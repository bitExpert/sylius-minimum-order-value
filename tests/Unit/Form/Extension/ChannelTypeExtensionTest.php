<?php

declare(strict_types=1);

namespace Tests\BitExpert\SyliusMinimumOrderValuePlugin\Unit\Form\Extension;

use BitExpert\SyliusMinimumOrderValuePlugin\Form\Extension\ChannelTypeExtension;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ChannelBundle\Form\Type\ChannelType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Tests\BitExpert\SyliusMinimumOrderValuePlugin\Unit\Fixtures\MinimumOrderValueChannel;

final class ChannelTypeExtensionTest extends TestCase
{
    public function test_extends_the_sylius_channel_type(): void
    {
        self::assertSame([ChannelType::class], iterator_to_array(ChannelTypeExtension::getExtendedTypes(), false));
    }

    public function test_adds_the_field_when_the_channel_is_minimum_order_value_aware(): void
    {
        $listener = $this->buildFormAndCaptureListener();

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('add')
            ->with(
                'minimumOrderValue',
                NumberType::class,
                self::callback(static function (array $options): bool {
                    self::assertSame('bitexpert_sylius_minimum_order_value.ui.minimum_order_value', $options['label']);
                    self::assertFalse($options['required']);
                    self::assertSame(2, $options['scale']);
                    self::assertSame(['min' => 0, 'step' => 0.01], $options['attr']);

                    self::assertCount(2, $options['constraints']);

                    $lower = $options['constraints'][0];
                    self::assertInstanceOf(GreaterThanOrEqual::class, $lower);
                    self::assertSame(0, $lower->value);

                    $upper = $options['constraints'][1];
                    self::assertInstanceOf(LessThanOrEqual::class, $upper);
                    self::assertSame(99999999.99, $upper->value);

                    return true;
                }),
            );

        $listener(new FormEvent($form, new MinimumOrderValueChannel(50.0)));
    }

    public function test_does_not_add_the_field_for_other_data(): void
    {
        $listener = $this->buildFormAndCaptureListener();

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::never())->method('add');

        $listener(new FormEvent($form, new \stdClass()));
    }

    private function buildFormAndCaptureListener(): callable
    {
        $capturedListener = null;

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::once())
            ->method('addEventListener')
            ->with(FormEvents::PRE_SET_DATA, self::isType('callable'))
            ->willReturnCallback(static function (string $eventName, callable $listener) use (&$capturedListener, $builder): FormBuilderInterface {
                $capturedListener = $listener;

                return $builder;
            });

        (new ChannelTypeExtension())->buildForm($builder, []);

        self::assertInstanceOf(\Closure::class, $capturedListener);

        return $capturedListener;
    }
}
