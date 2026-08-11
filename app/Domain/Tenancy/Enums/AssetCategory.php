<?php

namespace App\Domain\Tenancy\Enums;

enum AssetCategory: string
{
    case Laptop = 'laptop';
    case Phone = 'phone';
    case Vehicle = 'vehicle';
    case Accessory = 'accessory';
    case DocumentSeal = 'document_seal';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Laptop => 'حاسوب محمول',
            self::Phone => 'هاتف',
            self::Vehicle => 'مركبة',
            self::Accessory => 'ملحق',
            self::DocumentSeal => 'ختم / وثيقة',
            self::Other => 'أخرى',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
