<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIProvider implements AIProviderInterface
{
    public function complete(string $prompt, array $options = []): ?string
    {
        $key = config('services.ai.key');
        if (! $key) {
            return null;
        }

        try {
            $response = Http::withToken($key)->timeout(20)->post('https://api.openai.com/v1/chat/completions', [
                'model' => $options['model'] ?? config('services.ai.model', 'gpt-4o-mini'),
                'messages' => [
                    ['role' => 'system', 'content' => $options['system'] ?? 'You are GrowLead CRM assistant.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

            return $response->json('choices.0.message.content');
        } catch (\Throwable $e) {
            Log::warning('AI provider failed: '.$e->getMessage());

            return null;
        }
    }
}
