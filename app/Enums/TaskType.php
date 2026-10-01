<?php

namespace App\Enums;

enum TaskType: string
{
    case Call = 'call';
    case Whatsapp = 'whatsapp';
    case Email = 'email';
    case FollowUp = 'follow_up';
    case Meeting = 'meeting';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Call => 'Call',
            self::Whatsapp => 'WhatsApp',
            self::Email => 'Email',
            self::FollowUp => 'Follow-up',
            self::Meeting => 'Meeting',
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
