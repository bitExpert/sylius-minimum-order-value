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

use Doctrine\ORM\Mapping as ORM;

trait MinimumOrderValueTrait
{
    #[ORM\Column(name: 'minimum_order_value', type: 'decimal', precision: 10, scale: 2, nullable: true)]
    protected ?string $minimumOrderValue = null;

    public function getMinimumOrderValue(): ?float
    {
        return null !== $this->minimumOrderValue ? (float) $this->minimumOrderValue : null;
    }

    public function setMinimumOrderValue(?float $minimumOrderValue): void
    {
        $this->minimumOrderValue = null !== $minimumOrderValue ? number_format($minimumOrderValue, 2, '.', '') : null;
    }
}
