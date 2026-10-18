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

use BitExpert\SyliusMinimumOrderValuePlugin\Entity\MinimumOrderValueAwareInterface;
use Sylius\Bundle\ChannelBundle\Form\Type\ChannelType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;

final class ChannelTypeExtension extends AbstractTypeExtension
{
    /**
     * Upper bound of the decimal(10,2) column the trait maps the value to.
     */
    private const MAXIMUM = 99999999.99;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $channel = $event->getData();
            if (!$channel instanceof MinimumOrderValueAwareInterface) {
                return;
            }

            $event->getForm()->add('minimumOrderValue', NumberType::class, [
                'label' => 'bitexpert_sylius_minimum_order_value.ui.minimum_order_value',
                'required' => false,
                'scale' => 2,
                'attr' => [
                    'min' => 0,
                    'step' => 0.01,
                ],
                // attr.min/step are browser hints only; enforce the bounds server-side so a
                // negative value cannot silently disable the restriction and a value wider
                // than decimal(10,2) cannot blow up the database on flush.
                'constraints' => [
                    new GreaterThanOrEqual(0),
                    new LessThanOrEqual(self::MAXIMUM),
                ],
            ]);
        });
    }

    /**
     * @return iterable<class-string>
     */
    public static function getExtendedTypes(): iterable
    {
        yield ChannelType::class;
    }
}
