<?php

namespace App\Domain\Messaging\Enums;

enum ConversationType: string
{
    case Direct = 'direct';
    case Group = 'group';

    public function arabicLabel(): string
    {
        return match ($this) {
            self::Direct => 'محادثة مباشرة',
            self::Group => 'مجموعة',
        };
    }
}
