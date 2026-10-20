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

namespace Tests\BitExpert\SyliusMinimumOrderValuePlugin\Unit\Fixtures;

use BitExpert\SyliusMinimumOrderValuePlugin\Entity\MinimumOrderValueAwareInterface;
use BitExpert\SyliusMinimumOrderValuePlugin\Entity\MinimumOrderValueTrait;
use Sylius\Component\Core\Model\Channel;

/**
 * Sylius channel that opted into the minimum order value, used by the unit
 * tests as a stand-in for an application channel entity.
 */
final class MinimumOrderValueChannel extends Channel implements MinimumOrderValueAwareInterface
{
    use MinimumOrderValueTrait;

    public function __construct(?float $minimumOrderValue = null)
    {
        parent::__construct();

        $this->setMinimumOrderValue($minimumOrderValue);
    }
}
