<?php

namespace App\Twig;

use Twig\Attribute\AsTwigFilter;

class MoneyExtension
{
    /**
     * 2050 → « 20,50 € ».
     */
    #[AsTwigFilter('cents')]
    public function formatCents(int $cents): string
    {
        return number_format($cents / 100, 2, ',', ' ') . ' €';
    }
}
