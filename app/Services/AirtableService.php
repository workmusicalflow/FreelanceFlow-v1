<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use App\Models\Mission;
use RuntimeException;

/**
 * AirtableService
 * Gère les interactions avec la base Airtable pour le stockage des missions.
 */
class AirtableService
{
    private Client $client;
    private string $baseId;
    private string $tableName;
    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = $_ENV['AIRTABLE_API_KEY'] ?? throw new RuntimeException('AIRTABLE_API_KEY is not set');
        $this->baseId = $_ENV['AIRTABLE_BASE_ID'] ?? throw new RuntimeException('AIRTABLE_BASE_ID is not set');
        $this->tableName = $_ENV['AIRTABLE_MISSIONS_TABLE'] ?? throw new RuntimeException('AIRTABLE_MISSIONS_TABLE is not set');

        $this->client = new Client([
            'base_uri' => 'https://api.airtable.com/v0/',
            'headers'  => [
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type'  => 'application/json',
            ],
        ]);
    }

    /**
     * Crée une nouvelle mission dans Airtable
     * 
     * @param Mission $mission La mission à créer
     * @return array Les données de la mission créée, incluant l'ID Airtable
     * @throws RuntimeException Si une erreur survient
     */
    public function createMission(Mission $mission): array
    {
        try {
            $response = $this->client->post($this->baseId . '/' . $this->tableName, [
                'json' => [
                    'records' => [
                        [
                            'fields' => [
                                'Service' => $mission->getService(),
                                'Description' => $mission->getDescription(),
                                'Price' => $mission->getPrice(),
                                'Status' => $mission->getStatus(),
                                'Client_Email' => $mission->getClientEmail(),
                                'Invoice_Status' => $mission->getInvoiceStatus()
                            ]
                        ]
                    ]
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            return $data['records'][0];
        } catch (GuzzleException $e) {
            throw new RuntimeException('Erreur lors de la création de la mission: ' . $e->getMessage());
        }
    }

    /**
     * Met à jour une mission existante dans Airtable
     * 
     * @param string $recordId L'ID de la mission à mettre à jour
     * @param array $fields Les champs à mettre à jour
     * @return array Les données de la mission mise à jour
     * @throws RuntimeException Si une erreur survient
     */
    public function updateMission(string $recordId, array $fields): array
    {
        try {
            $response = $this->client->patch($this->baseId . '/' . $this->tableName . '/' . $recordId, [
                'json' => [
                    'fields' => $fields
                ]
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            throw new RuntimeException('Erreur lors de la mise à jour de la mission: ' . $e->getMessage());
        }
    }

    /**
     * Récupère une mission par son ID
     * 
     * @param string $recordId L'ID de la mission à récupérer
     * @return array Les données de la mission
     * @throws RuntimeException Si une erreur survient
     */
    public function getMission(string $recordId): array
    {
        try {
            $response = $this->client->get($this->baseId . '/' . $this->tableName . '/' . $recordId);
            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            throw new RuntimeException('Erreur lors de la récupération de la mission: ' . $e->getMessage());
        }
    }
}
