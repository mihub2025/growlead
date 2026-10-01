<?php

namespace App\Services\AI;

interface AIProviderInterface
{
    public function complete(string $prompt, array $options = []): ?string;
}
