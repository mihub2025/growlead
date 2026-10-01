<?php

namespace App\Services\AI;

class NullAIProvider implements AIProviderInterface
{
    public function complete(string $prompt, array $options = []): ?string
    {
        return null;
    }
}
