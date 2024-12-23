<?php

namespace Tests\Services;

use PHPUnit\Framework\TestCase;
use App\Services\EmailService;
use App\Models\Mission;
use PHPMailer\PHPMailer\PHPMailer;
use RuntimeException;

class EmailServiceTest extends TestCase
{
    private EmailService $service;
    private PHPMailer $mailerMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock des variables d'environnement SMTP
        $_ENV['SMTP_HOST'] = 'smtp.example.com';
        $_ENV['SMTP_PORT'] = '587';
        $_ENV['SMTP_USERNAME'] = 'test@example.com';
        $_ENV['SMTP_PASSWORD'] = 'password';
        $_ENV['SMTP_FROM_EMAIL'] = 'noreply@example.com';
        $_ENV['SMTP_FROM_NAME'] = 'FreelanceFlow';

        // Crée un mock de PHPMailer avec les méthodes nécessaires
        $this->mailerMock = $this->getMockBuilder(PHPMailer::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addAddress', 'send'])
            ->getMock();
        
        // Injection de la dépendance mockée
        $this->service = new EmailService($this->mailerMock);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->mailerMock);
        unset($this->service);
    }

    public function testSendMissionConfirmationSuccess(): void
    {
        // Prépare la mission test
        $mission = new Mission(
            'Développement Web',
            'Création site vitrine',
            1500.0,
            'John Doe',
            'client@example.com'
        );

        // Configure les attentes du mock
        $this->mailerMock->expects($this->once())
            ->method('addAddress')
            ->with('client@example.com')
            ->willReturn(true);

        $this->mailerMock->expects($this->once())
            ->method('send')
            ->willReturn(true);

        // Vérifie que l'envoi se passe bien
        $this->service->sendMissionConfirmation($mission, 'rec123');
    }

    public function testSendMissionConfirmationFailure(): void
    {
        // Prépare la mission test
        $mission = new Mission(
            'Développement Web',
            'Création site vitrine',
            1500.0,
            'John Doe',
            'client@example.com'
        );

        // Configure le mock pour simuler une erreur
        $this->mailerMock->expects($this->once())
            ->method('addAddress')
            ->with('client@example.com')
            ->willReturn(true);

        $this->mailerMock->expects($this->once())
            ->method('send')
            ->willReturn(false);

        // Vérifie que l'exception est bien levée
        $this->expectException(RuntimeException::class);
        $this->service->sendMissionConfirmation($mission, 'rec123');
    }

    public function testEmailContentContainsRequiredInfo(): void
    {
        // Prépare la mission test
        $mission = new Mission(
            'Développement Web',
            'Création site vitrine',
            1500.0,
            'John Doe',
            'client@example.com'
        );

        // Configure les attentes du mock
        $this->mailerMock->expects($this->once())
            ->method('addAddress')
            ->with('client@example.com')
            ->willReturn(true);

        $this->mailerMock->expects($this->once())
            ->method('send')
            ->willReturn(true);

        // Vérifie que l'envoi se passe bien
        $this->service->sendMissionConfirmation($mission, 'rec123');
    }
}
