<?php

namespace App\Entity;

use App\Entity\Image\PopinImage;
use App\Repository\PopinRepository;
use Aropixel\AdminBundle\Entity\Publishable;
use Aropixel\AdminBundle\Entity\PublishableTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: PopinRepository::class)]
#[ORM\Table(name: 'popin')]
class Popin implements Publishable
{
    use PublishableTrait;
    use TimestampableEntity;

    public const COOKIE_PREFIX = '_peb_popin_';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $content = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $link = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $linkLabel = null;

    #[ORM\OneToOne(targetEntity: PopinImage::class, mappedBy: 'popin', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private ?PopinImage $image = null;

    #[ORM\Column(length: 20)]
    private string $status = Publishable::STATUS_OFFLINE;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $publishAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $publishUntil = null;

    /**
     * Affichée sur tout le site, sinon uniquement sur les URLs de $urls.
     */
    #[ORM\Column]
    private bool $displayAll = true;

    /**
     * @var list<string>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $urls = null;

    /**
     * Ignore le cookie : la popin s'affiche à chaque chargement de page.
     */
    #[ORM\Column]
    private bool $forceDisplay = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function setLink(?string $link): self
    {
        $this->link = $link;

        return $this;
    }

    public function getLinkLabel(): ?string
    {
        return $this->linkLabel;
    }

    public function setLinkLabel(?string $linkLabel): self
    {
        $this->linkLabel = $linkLabel;

        return $this;
    }

    public function getImage(): ?PopinImage
    {
        return $this->image;
    }

    public function setImage(?PopinImage $image): self
    {
        if (null === $image || null === $image->getImage()) {
            $this->image = null;
        } else {
            $this->image = $image;
            $this->image->setPopin($this);
        }

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getPublishAt(): ?\DateTimeInterface
    {
        return $this->publishAt;
    }

    public function setPublishAt(?\DateTimeInterface $publishAt): self
    {
        $this->publishAt = $publishAt;

        return $this;
    }

    public function getPublishUntil(): ?\DateTimeInterface
    {
        return $this->publishUntil;
    }

    public function setPublishUntil(?\DateTimeInterface $publishUntil): self
    {
        $this->publishUntil = $publishUntil;

        return $this;
    }

    public function isDisplayAll(): bool
    {
        return $this->displayAll;
    }

    public function setDisplayAll(bool $displayAll): self
    {
        $this->displayAll = $displayAll;

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getUrls(): array
    {
        return $this->urls ?? [];
    }

    /**
     * @param list<string>|null $urls
     */
    public function setUrls(?array $urls): self
    {
        $this->urls = $urls ? array_values(array_filter(array_map('trim', $urls))) : null;

        return $this;
    }

    public function isForceDisplay(): bool
    {
        return $this->forceDisplay;
    }

    public function setForceDisplay(bool $forceDisplay): self
    {
        $this->forceDisplay = $forceDisplay;

        return $this;
    }

    public function getCookieName(): string
    {
        return self::COOKIE_PREFIX . $this->id;
    }

    /**
     * Change à chaque modification de la popin : une popin mise à jour est réaffichée
     * aux visiteurs qui l'avaient déjà vue.
     */
    public function getCookieValue(): string
    {
        $date = $this->getUpdatedAt() ?? $this->getCreatedAt();

        return md5($this->id . ($date?->format('U') ?? ''));
    }

    #[Assert\Callback]
    public function validateContentOrImage(ExecutionContextInterface $context): void
    {
        if (!$this->content && !$this->image) {
            $context->buildViolation('Renseignez un contenu texte ou une image.')
                ->atPath('content')
                ->addViolation()
            ;
        }

        if (!$this->displayAll && [] === $this->getUrls()) {
            $context->buildViolation('Ajoutez au moins une URL, ou affichez la popin sur tout le site.')
                ->atPath('displayAll')
                ->addViolation()
            ;
        }
    }
}
