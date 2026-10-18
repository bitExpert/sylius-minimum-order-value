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

namespace BitExpert\SyliusMinimumOrderValuePlugin\Entity;

interface MinimumOrderValueAwareInterface
{
    public function getMinimumOrderValue(): ?float;

    public function setMinimumOrderValue(?float $minimumOrderValue): void;
}
