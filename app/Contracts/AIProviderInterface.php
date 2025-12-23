<?php

namespace App\Contracts;

use Generator;

/**
 * AI Provider Interface
 * 
 * All AI providers (Groq, OpenAI, Claude, etc.) must implement this interface.
 * This allows easy switching between providers without changing business logic.
 */
interface AIProviderInterface
{
    /**
     * Generate questions based on given parameters
     *
     * @param array $params Array containing:
     *   - topic: string (required)
     *   - subject: string (optional)
     *   - type: string (mcq|short_answer) (required)
     *   - difficulty: string (easy|medium|hard) (required)
     *   - count: int (required)
     *   - marks: int (marks per question)
     *   - context: string (optional additional context)
     *   
     * @return array Array of generated questions in format:
     *   [
     *     [
     *       'question_text' => string,
     *       'type' => string,
     *       'marks' => int,
     *       'options' => array (for MCQ only) [
     *         ['option_text' => string, 'is_correct' => bool],
     *         ...
     *       ]
     *     ],
     *     ...
     *   ]
     */
    public function generateQuestions(array $params): array;

    /**
     * Chat with AI for general prompts
     *
     * @param string $prompt The prompt to send to AI
     * @param array $context Optional context/history for conversation
     * @return string AI response
     */
    public function chat(string $prompt, array $context = []): string;

    /**
     * Stream chat responses for real-time interaction
     *
     * @param string $prompt The prompt to send to AI
     * @param array $context Optional context/history for conversation
     * @return Generator Yields response chunks as they arrive
     */
    public function streamChat(string $prompt, array $context = []): Generator;

    /**
     * Test connection to the AI provider
     *
     * @return bool True if connection is successful
     */
    public function testConnection(): bool;
}
