<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default AI Provider
    |--------------------------------------------------------------------------
    |
    | This value determines which AI provider will be used by default
    | throughout your application. You may change this to any of the
    | providers defined in the "providers" array below.
    |
    | Supported: "groq", "openai", "claude", "gemini"
    |
    */

    'default' => env('AI_PROVIDER', 'groq'),

    /*
    |--------------------------------------------------------------------------
    | AI Providers Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure each AI provider that your application uses.
    | Each provider can have its own settings and credentials.
    |
    */

    'providers' => [

        'groq' => [
            'api_key' => env('GROQ_API_KEY'),
            'model' => env('GROQ_MODEL', 'mixtral-8x7b-32768'),
            'temperature' => env('GROQ_TEMPERATURE', 0.7),
            'max_tokens' => env('GROQ_MAX_TOKENS', 2048),
            'base_url' => env('GROQ_BASE_URL', 'https://api.groq.com/openai/v1'),
        ],

        // Future providers can be easily added here
        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_MODEL', 'gpt-4'),
            'temperature' => env('OPENAI_TEMPERATURE', 0.7),
            'max_tokens' => env('OPENAI_MAX_TOKENS', 2048),
        ],

        'claude' => [
            'api_key' => env('CLAUDE_API_KEY'),
            'model' => env('CLAUDE_MODEL', 'claude-3-sonnet-20240229'),
            'temperature' => env('CLAUDE_TEMPERATURE', 0.7),
            'max_tokens' => env('CLAUDE_MAX_TOKENS', 2048),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Question Generation Settings
    |--------------------------------------------------------------------------
    |
    | Configuration specific to AI-powered question generation
    |
    */

    'question_generation' => [
        'default_count' => 10,
        'min_questions' => 1,
        'max_questions' => 50,
        'cache_ttl' => 3600, // 1 hour in seconds
        'retry_attempts' => 3,
    ],

];
