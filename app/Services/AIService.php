<?php

namespace App\Services;

use App\Contracts\AIProviderInterface;
use App\Services\AI\GroqAIProvider;
use Illuminate\Support\Facades\Cache;
use Generator;

/**
 * Main AI Service
 * 
 * This service abstracts AI provider selection and provides high-level AI functionality.
 * It automatically uses the provider specified in config/ai.php
 */
class AIService
{
    protected AIProviderInterface $provider;

    public function __construct()
    {
        $this->provider = $this->createProvider();
    }

    /**
     * Generate questions using the configured AI provider
     *
     * @param array $params Question generation parameters
     * @return array Generated questions
     */
    public function generateQuestions(array $params): array
    {
        $cacheKey = $this->buildCacheKey('questions', $params);
        $cacheTtl = config('ai.question_generation.cache_ttl', 3600);

        return Cache::remember($cacheKey, $cacheTtl, function () use ($params) {
            return $this->provider->generateQuestions($params);
        });
    }

    /**
     * Chat with AI
     *
     * @param string $prompt The prompt
     * @param array $context Conversation context
     * @return string AI response
     */
    public function chat(string $prompt, array $context = []): string
    {
        return $this->provider->chat($prompt, $context);
    }

    /**
     * Stream chat responses
     *
     * @param string $prompt The prompt
     * @param array $context Conversation context
     * @return Generator Response stream
     */
    public function streamChat(string $prompt, array $context = []): Generator
    {
        return $this->provider->streamChat($prompt, $context);
    }

    /**
     * Test if AI service is configured and working
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        try {
            return $this->provider->testConnection();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get the current provider name
     *
     * @return string
     */
    public function getProviderName(): string
    {
        return config('ai.default', 'unknown');
    }

    /**
     * Create AI provider instance based on configuration
     *
     * @return AIProviderInterface
     * @throws \RuntimeException If provider is not configured or not supported
     */
    protected function createProvider(): AIProviderInterface
    {
        $providerName = config('ai.default');

        // Map provider names to their implementations
        $providers = [
            'groq' => GroqAIProvider::class,
            // Future providers can be added here:
            // 'openai' => OpenAIProvider::class,
            // 'claude' => ClaudeProvider::class,
        ];

        if (!isset($providers[$providerName])) {
            throw new \RuntimeException("AI provider '{$providerName}' is not supported. Check your AI_PROVIDER setting.");
        }

        $providerClass = $providers[$providerName];

        if (!class_exists($providerClass)) {
            throw new \RuntimeException("AI provider class '{$providerClass}' not found.");
        }

        return new $providerClass();
    }

    /**
     * Build cache key for AI responses
     *
     * @param string $prefix Cache key prefix
     * @param array $params Parameters to include in key
     * @return string
     */
    protected function buildCacheKey(string $prefix, array $params): string
    {
        return 'ai:' . $prefix . ':' . md5(json_encode($params));
    }
}
