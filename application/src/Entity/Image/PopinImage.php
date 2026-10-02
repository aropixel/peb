<?php

namespace App\Entity\Image;

use App\Entity\Popin;
use Aropixel\AdminBundle\Entity\AttachedImage;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'popin_image')]
class PopinImage extends AttachedImage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: Popin::class, inversedBy: 'image')]
    #[ORM\JoinColumn(name: 'popin_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?Popin $popin = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPopin(): ?Popin
    {
        return $this->popin;
    }

    public function setPopin(?Popin $popin): self
    {
        $this->popin = $popin;

        return $this;
    }
}
