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

namespace BitExpert\SyliusMinimumOrderValuePlugin\Checker;

/**
 * Result of a minimum order value check.
 *
 * @property bool        $allowed             Whether the action is permitted.
 * @property string|null $message             Optional error/hint shown to the user.
 * @property float|null  $minimumOrderValue   The configured minimum order value in major units (e.g. 49.99).
 * @property string|null $currencyCode        Currency code for formatting (e.g. 'USD', 'EUR').
 * @property string|null $localeCode          Locale code for formatting (e.g. 'en_US', 'de_DE').
 */
final class CheckResult
{
    public function __construct(
        public bool $allowed,
        public ?string $message = null,
        public ?float $minimumOrderValue = null,
        public ?string $currencyCode = null,
        public ?string $localeCode = null,
    ) {}
}
