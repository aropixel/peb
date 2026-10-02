<?php

namespace App\Twig;

use App\Component\Popin\PopinProvider;
use App\Entity\Popin;
use Twig\Attribute\AsTwigFunction;

class PopinExtension
{
    public function __construct(
        private readonly PopinProvider $popinProvider,
    ) {
    }

    #[AsTwigFunction('current_popin')]
    public function getCurrentPopin(): ?Popin
    {
        return $this->popinProvider->getCurrentPopin();
    }
}
