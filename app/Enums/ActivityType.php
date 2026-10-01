<?php

namespace App\Enums;

enum ActivityType: string
{
    case Call = 'call';
    case Whatsapp = 'whatsapp';
    case Email = 'email';
    case Meeting = 'meeting';
    case Note = 'note';
    case FollowUp = 'follow_up';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Call => 'Call',
            self::Whatsapp => 'WhatsApp',
            self::Email => 'Email',
            self::Meeting => 'Meeting',
            self::Note => 'Note',
            self::FollowUp => 'Follow-up',
            self::Other => 'Other',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $type) => [
            'value' => $type->value,
            'label' => $type->label(),
        ], self::cases());
    }
}
