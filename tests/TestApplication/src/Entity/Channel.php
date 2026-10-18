<?php

declare(strict_types=1);

namespace Tests\BitExpert\SyliusMinimumOrderValuePlugin\Entity;

use BitExpert\SyliusMinimumOrderValuePlugin\Entity\MinimumOrderValueAwareInterface;
use BitExpert\SyliusMinimumOrderValuePlugin\Entity\MinimumOrderValueTrait;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Core\Model\Channel as BaseChannel;

#[ORM\Entity]
#[ORM\Table(name: 'sylius_channel')]
class Channel extends BaseChannel implements MinimumOrderValueAwareInterface
{
    use MinimumOrderValueTrait;
}
