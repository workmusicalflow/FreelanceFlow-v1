<?php

namespace Tests\Services;

use PHPUnit\Framework\TestCase;
use App\Services\AirtableService;
use App\Models\Mission;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Middleware;
use RuntimeException;
use ReflectionClass;

class AirtableServiceTest extends TestCase
{
    private AirtableService $service;
    private array $container;
    private MockHandler $mock;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock des variables d'environnement
        $_ENV['AIRTABLE_API_KEY'] = 'fake_key';
        $_ENV['AIRTABLE_BASE_ID'] = 'fake_base';
        $_ENV['AIRTABLE_MISSIONS_TABLE'] = 'Missions';

        // Configuration du mock HTTP
        $this->container = [];
        $history = Middleware::history($this->container);
        
        $this->mock = new MockHandler();
        $handlerStack = HandlerStack::create($this->mock);
        $handlerStack->push($history);
        
        $client = new Client(['handler' => $handlerStack]);
        
        // Création du service et injection du client via réflexion
        $this->service = new AirtableService();
        $reflection = new ReflectionClass($this->service);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($this->service, $client);
    }

    public function testCreateMissionSuccess(): void
    {
        // Prépare la réponse mockée
        $this->mock->append(new Response(200, [], json_encode([
            'records' => [
                [
                    'id' => 'rec123',
                    'fields' => [
                        'Service' => 'Développement Web',
                        'Description' => 'Création site vitrine',
                        'Price' => 1500,
                        'Status' => 'En attente',
                        'Client_Email' => 'client@example.com',
                        'Invoice_Status' => 'Non facturé'
                    ]
                ]
            ]
        ])));

        // Crée une mission test
        $mission = new Mission(
            'Développement Web',
            'Création site vitrine',
            1500.0,
            'John Doe',
            'client@example.com'
        );

        // Teste la création
        $result = $this->service->createMission($mission);

        // Vérifie le résultat
        $this->assertArrayHasKey('id', $result);
        $this->assertEquals('rec123', $result['id']);
        $this->assertArrayHasKey('fields', $result);
        
        // Vérifie la requête envoyée
        $request = $this->container[0]['request'];
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('fake_base/Missions', $request->getUri()->getPath());
    }

    public function testCreateMissionFailure(): void
    {
        // Simule une erreur API
        $this->mock->append(new Response(400, [], json_encode([
            'error' => 'Invalid request'
        ])));

        $mission = new Mission(
            'Test Service',
            'Test Description',
            100.0,
            'Test Client',
            'test@test.com'
        );

        $this->expectException(RuntimeException::class);
        $this->service->createMission($mission);
    }

    public function testGetMissionSuccess(): void
    {
        // Prépare la réponse mockée
        $this->mock->append(new Response(200, [], json_encode([
            'id' => 'rec123',
            'fields' => [
                'Service' => 'Développement Web',
                'Description' => 'Création site vitrine',
                'Price' => 1500,
                'Status' => 'En attente',
                'Client_Email' => 'client@example.com',
                'Invoice_Status' => 'Non facturé'
            ]
        ])));

        $result = $this->service->getMission('rec123');

        $this->assertArrayHasKey('fields', $result);
        $this->assertEquals('Développement Web', $result['fields']['Service']);
        
        $request = $this->container[0]['request'];
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('fake_base/Missions/rec123', $request->getUri()->getPath());
    }

    public function testUpdateMissionSuccess(): void
    {
        // Prépare la réponse mockée
        $this->mock->append(new Response(200, [], json_encode([
            'id' => 'rec123',
            'fields' => [
                'Status' => 'En cours',
                'Invoice_Status' => 'Facturé'
            ]
        ])));

        $fields = [
            'Status' => 'En cours',
            'Invoice_Status' => 'Facturé'
        ];

        $result = $this->service->updateMission('rec123', $fields);

        $this->assertArrayHasKey('fields', $result);
        $this->assertEquals('En cours', $result['fields']['Status']);
        
        $request = $this->container[0]['request'];
        $this->assertEquals('PATCH', $request->getMethod());
        $this->assertEquals('fake_base/Missions/rec123', $request->getUri()->getPath());
    }
}
