<?php

namespace App\Entity;

use App\Enum\DonationStatus;
use App\Repository\DonationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * Un don effectué via Stripe Checkout. Le statut n'évolue que par les webhooks Stripe.
 */
#[ORM\Entity(repositoryClass: DonationRepository::class)]
#[ORM\Table(name: 'donation')]
#[ORM\Index(columns: ['status'])]
class Donation
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Référence courte communiquée au donateur, pour le SAV.
     */
    #[ORM\Column(length: 20, unique: true)]
    private string $reference;

    /**
     * Montant en centimes.
     */
    #[ORM\Column]
    private int $amount;

    #[ORM\Column(length: 3)]
    private string $currency = 'eur';

    #[ORM\Column(length: 100)]
    private string $firstName;

    #[ORM\Column(length: 100)]
    private string $lastName;

    #[ORM\Column(length: 180)]
    private string $email;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $message = null;

    #[ORM\Column(length: 20, enumType: DonationStatus::class)]
    private DonationStatus $status = DonationStatus::Pending;

    #[ORM\Column(length: 255, unique: true, nullable: true)]
    private ?string $stripeCheckoutSessionId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripePaymentIntentId = null;

    /**
     * Moyen de paiement utilisé (card, paypal, etc.).
     */
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $paymentMethod = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $paidAt = null;

    /**
     * Montant remboursé en centimes (un remboursement peut être partiel).
     */
    #[ORM\Column]
    private int $amountRefunded = 0;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $refundedAt = null;

    public function __construct(int $amount, string $firstName, string $lastName, string $email, ?string $message = null)
    {
        $this->reference = self::generateReference();
        $this->amount = $amount;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->email = $email;
        $this->message = $message;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function getStatus(): DonationStatus
    {
        return $this->status;
    }

    public function getStripeCheckoutSessionId(): ?string
    {
        return $this->stripeCheckoutSessionId;
    }

    public function setStripeCheckoutSessionId(string $stripeCheckoutSessionId): self
    {
        $this->stripeCheckoutSessionId = $stripeCheckoutSessionId;

        return $this;
    }

    public function getStripePaymentIntentId(): ?string
    {
        return $this->stripePaymentIntentId;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function getPaidAt(): ?\DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function getAmountRefunded(): int
    {
        return $this->amountRefunded;
    }

    public function getRefundedAt(): ?\DateTimeImmutable
    {
        return $this->refundedAt;
    }

    public function isPaid(): bool
    {
        return DonationStatus::Paid === $this->status;
    }

    public function markPaid(?string $paymentIntentId, ?string $paymentMethod): void
    {
        $this->status = DonationStatus::Paid;
        $this->stripePaymentIntentId = $paymentIntentId;
        $this->paymentMethod = $paymentMethod;
        $this->paidAt = new \DateTimeImmutable();
    }

    public function markFailed(): void
    {
        $this->status = DonationStatus::Failed;
    }

    public function markExpired(): void
    {
        $this->status = DonationStatus::Expired;
    }

    /**
     * Un remboursement total passe le don en « Remboursé » ; un partiel le laisse « Payé ».
     */
    public function registerRefund(int $amountRefunded): void
    {
        $this->amountRefunded = $amountRefunded;
        $this->refundedAt = new \DateTimeImmutable();

        if ($amountRefunded >= $this->amount) {
            $this->status = DonationStatus::Refunded;
        }
    }

    private static function generateReference(): string
    {
        // Sans 0/O ni 1/I pour éviter les confusions au téléphone.
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $reference = '';
        for ($i = 0; $i < 6; ++$i) {
            $reference .= $alphabet[random_int(0, \strlen($alphabet) - 1)];
        }

        return 'DON-' . $reference;
    }
}
