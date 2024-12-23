<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use App\Utils\RetryHandler;
use RuntimeException;

/**
 * AIService
 * Implémente la logique des assistants OpenAI via le mécanisme
 * de threads, messages et runs (exécutions).
 */
class AIService
{
    private Client $client;
    private string $apiKey;
    private ?string $assistantId;  // ID de l'assistant (ex: asst_XXXXXX)
    private ?RetryHandler $retryHandler; // Gère les tentatives de retry

    /**
     * Le constructeur récupère l'API Key et l'Assistant ID depuis
     * la config (ou .env), et instancie un client Guzzle.
     *
     * @param RetryHandler|null $retryHandler Un gestionnaire de retry optionnel
     */
    public function __construct(?RetryHandler $retryHandler = null)
    {
        $this->apiKey = $_ENV['OPENAI_API_KEY'] ?? throw new RuntimeException('OPENAI_API_KEY is not set');
        $this->assistantId = $_ENV['OPENAI_ASSISTANT_ID'] ?? null;
        $this->retryHandler = $retryHandler;

        $this->client = new Client([
            'base_uri' => 'https://api.openai.com/v1/',
            'headers'  => [
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type'  => 'application/json',
            ],
        ]);
    }

    /**
     * Exécute une requête avec gestion du retry
     */
    private function executeWithRetry(string $method, string $endpoint, array $options = []): array
    {
        try {
            $response = $this->client->request($method, $endpoint, $options);
            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            if ($this->retryHandler) {
                return $this->retryHandler->handleRetry(function() use ($method, $endpoint, $options) {
                    $response = $this->client->request($method, $endpoint, $options);
                    return json_decode($response->getBody()->getContents(), true);
                });
            }
            throw new RuntimeException('Erreur lors de la requête OpenAI: ' . $e->getMessage());
        }
    }

    /**
     * Crée un nouveau thread de conversation dans OpenAI.
     * @return string L'ID du thread créé.
     */
    public function createThread(): string
    {
        $response = $this->executeWithRetry('POST', '/threads');
        return $response['id'];
    }

    /**
     * Ajoute un message utilisateur au thread
     * 
     * @param string $threadId ID du thread
     * @param string $content Contenu du message
     * @return array Les données du message créé
     */
    public function addUserMessage(string $threadId, string $content): array
    {
        return $this->executeWithRetry('POST', "/threads/{$threadId}/messages", [
            'json' => [
                'role' => 'user',
                'content' => $content
            ]
        ]);
    }

    /**
     * Crée un run pour le thread avec l'assistant
     * 
     * @param string $threadId ID du thread
     * @return array Les données du run créé
     */
    public function createRun(string $threadId): array
    {
        if (!$this->assistantId) {
            throw new RuntimeException('Assistant ID is not set');
        }

        return $this->executeWithRetry('POST', "/threads/{$threadId}/runs", [
            'json' => [
                'assistant_id' => $this->assistantId
            ]
        ]);
    }

    /**
     * Attend que le run soit terminé
     * 
     * @param string $threadId ID du thread
     * @param string $runId ID du run
     * @param int $maxAttempts Nombre maximum de tentatives
     * @param int $delaySeconds Délai entre les tentatives
     * @return array Les données du run
     */
    public function waitForRun(string $threadId, string $runId, int $maxAttempts = 10, int $delaySeconds = 1): array
    {
        $attempts = 0;
        while ($attempts < $maxAttempts) {
            $run = $this->executeWithRetry('GET', "/threads/{$threadId}/runs/{$runId}");
            
            if (in_array($run['status'], ['completed', 'failed', 'cancelled', 'expired'])) {
                return $run;
            }

            $attempts++;
            if ($attempts < $maxAttempts) {
                sleep($delaySeconds);
            }
        }

        throw new RuntimeException('Le run n\'a pas été complété dans le temps imparti');
    }

    /**
     * Récupère les messages d'un thread
     * 
     * @param string $threadId ID du thread
     * @return array Liste des messages
     */
    public function getMessages(string $threadId): array
    {
        $response = $this->executeWithRetry('GET', "/threads/{$threadId}/messages");
        return $response['data'];
    }
}
