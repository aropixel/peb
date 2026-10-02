<?php

namespace App\Enum;

enum DonationStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Expired = 'expired';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Paid => 'Payé',
            self::Failed => 'Échoué',
            self::Expired => 'Abandonné',
            self::Refunded => 'Remboursé',
        };
    }

    /**
     * Variante de badge Bootstrap utilisée en admin.
     */
    public function badge(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Paid => 'success',
            self::Failed => 'danger',
            self::Expired => 'secondary',
            self::Refunded => 'info',
        };
    }
}
