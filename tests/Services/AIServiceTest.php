<?php

namespace Tests\Services;

use PHPUnit\Framework\TestCase;
use App\Services\AIService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Middleware;
use RuntimeException;
use ReflectionClass;

class AIServiceTest extends TestCase
{
    private AIService $service;
    private array $container;
    private MockHandler $mock;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock des variables d'environnement
        $_ENV['OPENAI_API_KEY'] = 'fake_key';
        $_ENV['OPENAI_ASSISTANT_ID'] = 'asst_123';

        // Configuration du mock HTTP
        $this->container = [];
        $history = Middleware::history($this->container);
        
        $this->mock = new MockHandler();
        $handlerStack = HandlerStack::create($this->mock);
        $handlerStack->push($history);
        
        $client = new Client(['handler' => $handlerStack]);
        
        // Création du service et injection du client via réflexion
        $this->service = new AIService();
        $reflection = new ReflectionClass($this->service);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($this->service, $client);
    }

    public function testCreateThreadSuccess(): void
    {
        // Prépare la réponse mockée
        $this->mock->append(new Response(200, [], json_encode([
            'id' => 'thread_123',
            'object' => 'thread',
            'created_at' => time()
        ])));

        $threadId = $this->service->createThread();

        $this->assertEquals('thread_123', $threadId);
        
        $request = $this->container[0]['request'];
        $this->assertEquals('POST', $request->getMethod());
        $this->assertStringContainsString('/threads', $request->getUri()->getPath());
    }

    public function testAddUserMessageSuccess(): void
    {
        // Prépare la réponse mockée
        $this->mock->append(new Response(200, [], json_encode([
            'id' => 'msg_123',
            'object' => 'thread.message',
            'created_at' => time(),
            'thread_id' => 'thread_123',
            'role' => 'user',
            'content' => [
                ['text' => ['value' => 'Test message']]
            ]
        ])));

        $result = $this->service->addUserMessage('thread_123', 'Test message');

        $this->assertArrayHasKey('id', $result);
        $this->assertEquals('msg_123', $result['id']);
        
        $request = $this->container[0]['request'];
        $this->assertEquals('POST', $request->getMethod());
        $this->assertStringContainsString('/threads/thread_123/messages', $request->getUri()->getPath());
    }

    public function testCreateRunSuccess(): void
    {
        // Prépare la réponse mockée
        $this->mock->append(new Response(200, [], json_encode([
            'id' => 'run_123',
            'object' => 'thread.run',
            'created_at' => time(),
            'thread_id' => 'thread_123',
            'assistant_id' => 'asst_123',
            'status' => 'queued'
        ])));

        $result = $this->service->createRun('thread_123');

        $this->assertArrayHasKey('id', $result);
        $this->assertEquals('run_123', $result['id']);
        $this->assertEquals('queued', $result['status']);
        
        $request = $this->container[0]['request'];
        $this->assertEquals('POST', $request->getMethod());
        $this->assertStringContainsString('/threads/thread_123/runs', $request->getUri()->getPath());
    }

    public function testWaitForRunSuccess(): void
    {
        // Simule une séquence de statuts
        $this->mock->append(
            new Response(200, [], json_encode([
                'id' => 'run_123',
                'status' => 'queued'
            ])),
            new Response(200, [], json_encode([
                'id' => 'run_123',
                'status' => 'in_progress'
            ])),
            new Response(200, [], json_encode([
                'id' => 'run_123',
                'status' => 'completed'
            ]))
        );

        $result = $this->service->waitForRun('thread_123', 'run_123', 3, 0);

        $this->assertEquals('completed', $result['status']);
    }

    public function testGetMessagesSuccess(): void
    {
        // Prépare la réponse mockée
        $this->mock->append(new Response(200, [], json_encode([
            'data' => [
                [
                    'id' => 'msg_123',
                    'role' => 'assistant',
                    'content' => [
                        ['text' => ['value' => 'Test response']]
                    ]
                ],
                [
                    'id' => 'msg_124',
                    'role' => 'user',
                    'content' => [
                        ['text' => ['value' => 'Test message']]
                    ]
                ]
            ]
        ])));

        $messages = $this->service->getMessages('thread_123');

        $this->assertIsArray($messages);
        $this->assertCount(2, $messages);
        $this->assertEquals('assistant', $messages[0]['role']);
        
        $request = $this->container[0]['request'];
        $this->assertEquals('GET', $request->getMethod());
        $this->assertStringContainsString('/threads/thread_123/messages', $request->getUri()->getPath());
    }
}
