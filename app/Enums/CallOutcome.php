<?php

namespace App\Enums;

enum CallOutcome: string
{
    case Connected = 'connected';
    case NoAnswer = 'no_answer';
    case Interested = 'interested';
    case CallbackRequested = 'callback_requested';
    case Qualified = 'qualified';
    case NotInterested = 'not_interested';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Connected => 'Connected',
            self::NoAnswer => 'No Answer',
            self::Interested => 'Interested',
            self::CallbackRequested => 'Call Back Requested',
            self::Qualified => 'Qualified',
            self::NotInterested => 'Not Interested',
            self::Other => 'Other',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $outcome) => [
            'value' => $outcome->value,
            'label' => $outcome->label(),
        ], self::cases());
    }
}
