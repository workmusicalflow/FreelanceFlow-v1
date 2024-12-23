<?php

namespace App\Controllers;

use App\Services\AIService;  // Notre service qui gère threads/runs
use RuntimeException;
use App\Models\Mission;
use App\Services\AirtableService;
use App\Services\EmailService;

/**
 * ChatController
 * Gère la logique d'un chat conversationnel basé sur la plateforme OpenAI,
 * en exploitant la notion de threads et de runs, sans préciser le modèle ici
 * (car tout est configuré sur la plateforme OpenAI).
 */
class ChatController
{
    /**
     * Affiche la vue du chat.
     * Lance un nouveau thread si nécessaire, ou réutilise celui de la session.
     */
    public function index(): void
    {
        // On démarre la session pour stocker (ou récupérer) le thread ID
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Vérifie si un thread est déjà associé à la session
        if (!isset($_SESSION['thread_id'])) {
            try {
                $aiService = new AIService();    // On crée une instance de notre service IA
                $threadId = $aiService->createThread(); // On crée un nouveau thread
                $_SESSION['thread_id'] = $threadId;
            } catch (RuntimeException $e) {
                // Vous pouvez gérer l'erreur : logs, feedback à l'utilisateur, etc.
                // Ici on affiche juste un message simple.
                echo "Impossible de créer un thread avec l'assistant OpenAI. Erreur: " . $e->getMessage();
                return;
            }
        }

        // On inclut la vue (HTML + JS) responsable de l'UI du chat
        require __DIR__ . '/../Views/chat-view.php';
    }

    /**
     * Reçoit un message utilisateur (via AJAX ou formulaire POST), 
     * l'ajoute au thread, lance un run et attend la réponse de l'assistant.
     */
    public function sendMessage(): void
    {
        // On démarre la session pour accéder au thread_id
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $threadId = $_SESSION['thread_id'] ?? null;
        if (!$threadId) {
            $aiService = new AIService();
            $threadId = $aiService->createThread();
            $_SESSION['thread_id'] = $threadId;
        }

        // Récupération du message utilisateur depuis JSON
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        $userMessage = $data['message'] ?? '';
        $userMessage = trim($userMessage);

        if (empty($userMessage)) {
            $this->returnJson([
                'message' => "Désolé, je n'ai pas reçu de message."
            ]);
            return;
        }

        try {
            $aiService = new AIService();
            
            // Ajouter le message utilisateur au thread
            $aiService->addUserMessage($threadId, $userMessage);
            
            // Créer et exécuter un run
            $run = $aiService->createRun($threadId);
            $runId = $run['id'] ?? null;
            
            if (!$runId) {
                throw new RuntimeException("Impossible de lancer l'exécution : ID du run introuvable.");
            }

            // Attendre la fin du run
            $finalRun = $aiService->waitForRun($threadId, $runId, 30, 1);
            
            // Récupérer les messages et analyser la réponse
            $messages = $aiService->getMessages($threadId);
            $assistantMsg = $this->getLastAssistantMessage($messages);
            
            // Analyser si l'assistant a identifié une mission
            $mission = $this->extractMissionDetails($assistantMsg);

            // Retourner la réponse avec les détails de la mission si disponible
            $this->returnJson([
                'message' => $assistantMsg,
                'mission' => $mission
            ]);

        } catch (RuntimeException $e) {
            $this->returnJson([
                'message' => "Une erreur est survenue : " . $e->getMessage()
            ]);
        }
    }

    public function submitMission(): void
    {
        try {
            // Récupérer les données de la mission depuis le JSON
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            if (!isset($data['service'], $data['description'], $data['price'], $data['clientEmail'])) {
                throw new RuntimeException("Données de mission incomplètes");
            }

            // Créer une nouvelle mission
            $mission = new Mission();
            $mission->setService($data['service'])
                   ->setDescription($data['description'])
                   ->setPrice((float) $data['price'])
                   ->setClientEmail($data['clientEmail'])
                   ->setStatus('En attente');

            // Enregistrer dans Airtable
            $airtableService = new AirtableService();
            $record = $airtableService->createMission($mission);

            // Envoyer l'email de confirmation
            $emailService = new EmailService();
            $emailService->sendMissionConfirmation($mission, $record['id']);

            $this->returnJson([
                'success' => true,
                'message' => 'Mission soumise avec succès'
            ]);

        } catch (\Exception $e) {
            $this->returnJson([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Cherche le dernier message ayant pour "role" = "assistant" 
     * parmi la liste de messages du thread.
     */
    private function getLastAssistantMessage(array $messages): string
    {
        // Filtrer uniquement ceux où role = 'assistant'
        $assistantMessages = array_filter($messages, function ($msg) {
            return isset($msg['role']) && $msg['role'] === 'assistant';
        });

        // Prendre le dernier
        if (empty($assistantMessages)) {
            return "Je suis désolé, je n'ai pas pu trouver de réponse de l'assistant.";
        }

        $last = end($assistantMessages);
        return $last['content'] ?? "Pas de contenu renvoyé par l'assistant.";
    }

    private function extractMissionDetails(string $message): ?array
    {
        // Recherche de patterns dans la réponse de l'assistant
        // pour identifier un service, une description et un prix
        $patterns = [
            'service' => '/Service\s*:\s*([^\n]+)/i',
            'description' => '/Description\s*:\s*([^\n]+)/i',
            'price' => '/Prix\s*:\s*(\d+(?:\.\d{1,2})?)\s*€/i'
        ];

        $mission = [];
        foreach ($patterns as $key => $pattern) {
            if (preg_match($pattern, $message, $matches)) {
                $mission[$key] = trim($matches[1]);
            }
        }

        // Retourner null si on n'a pas tous les éléments requis
        if (count($mission) < 3) {
            return null;
        }

        // Convertir le prix en nombre
        $mission['price'] = (float) $mission['price'];

        return $mission;
    }

    /**
     * Méthode utilitaire pour renvoyer une réponse JSON et stopper l'exécution.
     */
    private function returnJson(array $data): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data);
        exit;
    }
}
