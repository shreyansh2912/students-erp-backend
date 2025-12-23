<?php

namespace App\Services\AI;

use App\Contracts\AIProviderInterface;
use Generator;
use Illuminate\Support\Facades\Log;
use LucianoTonet\GroqPHP\Groq;

/**
 * Groq AI Provider Implementation
 * 
 * Implements the AIProviderInterface using Groq's API
 */
class GroqAIProvider implements AIProviderInterface
{
    protected Groq $client;
    protected array $config;

    public function __construct()
    {
        $this->config = config('ai.providers.groq');
        
        $this->client = new Groq(
            apiKey: $this->config['api_key']
        );
    }

    /**
     * Generate questions based on given parameters
     */
    public function generateQuestions(array $params): array
    {
        $prompt = $this->buildQuestionPrompt($params);
        
        try {
            $response = $this->client->chat()->completions()->create([
                'model' => $this->config['model'],
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an expert educator who creates high-quality examination questions. Always respond with valid JSON only, no additional text.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => $this->config['temperature'],
                'max_tokens' => $this->config['max_tokens'],
            ]);

            $content = $response['choices'][0]['message']['content'] ?? '';
            
            return $this->parseQuestionsResponse($content, $params['type']);
            
        } catch (\Exception $e) {
            Log::error('Groq question generation failed', [
                'error' => $e->getMessage(),
                'params' => $params
            ]);
            
            throw new \RuntimeException('Failed to generate questions: ' . $e->getMessage());
        }
    }

    /**
     * Chat with AI for general prompts
     */
    public function chat(string $prompt, array $context = []): string
    {
        try {
            $messages = $this->buildMessages($prompt, $context);
            
            $response = $this->client->chat()->completions()->create([
                'model' => $this->config['model'],
                'messages' => $messages,
                'temperature' => $this->config['temperature'],
                'max_tokens' => $this->config['max_tokens'],
            ]);

            return $response['choices'][0]['message']['content'] ?? '';
            
        } catch (\Exception $e) {
            Log::error('Groq chat failed', [
                'error' => $e->getMessage(),
                'prompt' => $prompt
            ]);
            
            throw new \RuntimeException('Chat failed: ' . $e->getMessage());
        }
    }

    /**
     * Stream chat responses for real-time interaction
     */
    public function streamChat(string $prompt, array $context = []): Generator
    {
        try {
            $messages = $this->buildMessages($prompt, $context);
            
            $stream = $this->client->chat()->completions()->create([
                'model' => $this->config['model'],
                'messages' => $messages,
                'temperature' => $this->config['temperature'],
                'max_tokens' => $this->config['max_tokens'],
                'stream' => true,
            ]);

            foreach ($stream as $chunk) {
                $content = $chunk['choices'][0]['delta']['content'] ?? '';
                if (!empty($content)) {
                    yield $content;
                }
            }
            
        } catch (\Exception $e) {
            Log::error('Groq stream chat failed', [
                'error' => $e->getMessage(),
                'prompt' => $prompt
            ]);
            
            throw new \RuntimeException('Stream chat failed: ' . $e->getMessage());
        }
    }

    /**
     * Test connection to Groq API
     */
    public function testConnection(): bool
    {
        try {
            $response = $this->client->chat()->completions()->create([
                'model' => $this->config['model'],
                'messages' => [
                    ['role' => 'user', 'content' => 'Hello']
                ],
                'max_tokens' => 10,
            ]);

            return isset($response['choices'][0]['message']['content']);
            
        } catch (\Exception $e) {
            Log::error('Groq connection test failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Build prompt for question generation
     */
    protected function buildQuestionPrompt(array $params): string
    {
        $type = $params['type'];
        $topic = $params['topic'];
        $difficulty = $params['difficulty'];
        $count = $params['count'];
        $marks = $params['marks'] ?? 1;
        $subject = $params['subject'] ?? '';
        $context = $params['context'] ?? '';

        $typeDescription = $type === 'mcq' 
            ? 'multiple choice questions with 4 options each (mark the correct option)'
            : 'short answer questions';

        $prompt = "Generate {$count} {$difficulty} level {$typeDescription} about '{$topic}'";
        
        if ($subject) {
            $prompt .= " for {$subject}";
        }
        
        if ($context) {
            $prompt .= ". Additional context: {$context}";
        }

        $prompt .= "\n\nEach question should be worth {$marks} marks.";
        
        if ($type === 'mcq') {
            $prompt .= "\n\nRespond ONLY with a valid JSON array in this exact format:\n";
            $prompt .= <<<JSON
[
  {
    "question_text": "Question text here?",
    "marks": {$marks},
    "options": [
      {"option_text": "Option A", "is_correct": false},
      {"option_text": "Option B", "is_correct": true},
      {"option_text": "Option C", "is_correct": false},
      {"option_text": "Option D", "is_correct": false}
    ]
  }
]
JSON;
        } else {
            $prompt .= "\n\nRespond ONLY with a valid JSON array in this exact format:\n";
            $prompt .= <<<JSON
[
  {
    "question_text": "Question text here?",
    "marks": {$marks}
  }
]
JSON;
        }

        return $prompt;
    }

    /**
     * Parse AI response into structured question data
     */
    protected function parseQuestionsResponse(string $content, string $type): array
    {
        // Remove markdown code blocks if present
        $content = preg_replace('/```json\s*/', '', $content);
        $content = preg_replace('/```\s*/', '', $content);
        $content = trim($content);

        try {
            $questions = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            
            if (!is_array($questions)) {
                throw new \RuntimeException('Response is not a valid array');
            }

            // Validate and normalize questions
            return array_map(function ($q) use ($type) {
                if (!isset($q['question_text'])) {
                    throw new \RuntimeException('Missing question_text field');
                }

                $normalized = [
                    'question_text' => $q['question_text'],
                    'type' => $type,
                    'marks' => $q['marks'] ?? 1,
                ];

                if ($type === 'mcq' && isset($q['options'])) {
                    $normalized['options'] = $q['options'];
                }

                return $normalized;
            }, $questions);
            
        } catch (\JsonException $e) {
            Log::error('Failed to parse AI response', [
                'content' => $content,
                'error' => $e->getMessage()
            ]);
            
            throw new \RuntimeException('AI returned invalid JSON response');
        }
    }

    /**
     * Build messages array for chat
     */
    protected function buildMessages(string $prompt, array $context): array
    {
        $messages = [];
        
        // Add context messages if provided
        foreach ($context as $msg) {
            if (isset($msg['role']) && isset($msg['content'])) {
                $messages[] = $msg;
            }
        }
        
        // Add current prompt
        $messages[] = [
            'role' => 'user',
            'content' => $prompt
        ];
        
        return $messages;
    }
}
