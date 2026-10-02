<?php

namespace App\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Saisie du formulaire de don, avant création du Donation.
 */
class DonationRequest
{
    public const OTHER_AMOUNT = 'other';
    public const MIN_AMOUNT = 100;
    public const MAX_AMOUNT = 1_000_000;

    /**
     * Montant prédéfini en centimes (« 2000 ») ou OTHER_AMOUNT.
     */
    #[Assert\NotBlank(message: 'Choisissez un montant.')]
    public ?string $amount = null;

    /**
     * Montant libre en centimes.
     */
    public ?int $customAmount = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    public ?string $firstName = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    public ?string $lastName = null;

    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(max: 180)]
    public ?string $email = null;

    #[Assert\Length(max: 1000)]
    public ?string $message = null;

    #[Assert\IsTrue(message: 'Vous devez accepter le traitement de vos données pour faire un don.')]
    public bool $consent = false;

    public function getAmountInCents(): int
    {
        return self::OTHER_AMOUNT === $this->amount ? (int) $this->customAmount : (int) $this->amount;
    }

    #[Assert\Callback]
    public function validateAmount(ExecutionContextInterface $context): void
    {
        if (null === $this->amount) {
            return;
        }

        $amount = $this->getAmountInCents();
        if ($amount < self::MIN_AMOUNT || $amount > self::MAX_AMOUNT) {
            $context->buildViolation(\sprintf('Le montant doit être compris entre %d et %s €.', self::MIN_AMOUNT / 100, number_format(self::MAX_AMOUNT / 100, 0, ',', ' ')))
                ->atPath(self::OTHER_AMOUNT === $this->amount ? 'customAmount' : 'amount')
                ->addViolation()
            ;
        }
    }
}
