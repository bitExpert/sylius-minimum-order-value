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

use BitExpert\SyliusMinimumOrderValuePlugin\Form\Extension\ChannelTypeExtension;
use PHPUnit\Framework\Attributes\Test;
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
    #[Test]
    public function extendsTheSyliusChannelType(): void
    {
        self::assertSame([ChannelType::class], iterator_to_array(ChannelTypeExtension::getExtendedTypes(), false));
    }

    #[Test]
    public function addsTheFieldWhenTheChannelIsMinimumOrderValueAware(): void
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

                    /** @var array<GreaterThanOrEqual|LessThanOrEqual> $constraints */
                    $constraints = $options['constraints'];
                    self::assertCount(2, $constraints);

                    /** @var GreaterThanOrEqual $lower */
                    $lower = $constraints[0];
                    self::assertInstanceOf(GreaterThanOrEqual::class, $lower);
                    self::assertSame(0, $lower->value);

                    /** @var LessThanOrEqual $upper */
                    $upper = $constraints[1];
                    self::assertInstanceOf(LessThanOrEqual::class, $upper);
                    self::assertSame(99999999.99, $upper->value);

                    return true;
                }),
            );

        $listener(new FormEvent($form, new MinimumOrderValueChannel(50.0)));
    }

    #[Test]
    public function doesNotAddTheFieldForOtherData(): void
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
