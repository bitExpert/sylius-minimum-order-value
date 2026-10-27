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

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use BitExpert\SyliusMinimumOrderValuePlugin\Checker\MinimumOrderValueChecker;
use BitExpert\SyliusMinimumOrderValuePlugin\Checker\MinimumOrderValueCheckerInterface;
use BitExpert\SyliusMinimumOrderValuePlugin\Form\Extension\ChannelTypeExtension;
use BitExpert\SyliusMinimumOrderValuePlugin\Form\Extension\CompleteTypeExtension;
use BitExpert\SyliusMinimumOrderValuePlugin\Listener\MinimumOrderValueListener;
use BitExpert\SyliusMinimumOrderValuePlugin\Twig\Extension\MinimumOrderValueExtension;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set(MinimumOrderValueChecker::class);

    $services->alias(MinimumOrderValueCheckerInterface::class, MinimumOrderValueChecker::class);

    $services->set(ChannelTypeExtension::class)
        ->tag('form.type_extension');

    $services->set(CompleteTypeExtension::class)
        ->args([
            service(MinimumOrderValueCheckerInterface::class),
            service('translator'),
        ])
        ->tag('form.type_extension');

    $services->set(MinimumOrderValueListener::class)
        ->args([service(MinimumOrderValueCheckerInterface::class)])
        ->tag('kernel.event_listener', ['event' => 'workflow.guard', 'method' => 'guardCheckoutComplete']);

    $services->set(MinimumOrderValueExtension::class)
        ->args([
            service(MinimumOrderValueCheckerInterface::class),
            service('sylius.context.cart'),
        ])
        ->tag('twig.extension');
};
