<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use App\Models\Mission;
use RuntimeException;

class EmailService
{
    private PHPMailer $mailer;

    public function __construct(?PHPMailer $mailer = null)
    {
        $this->mailer = $mailer ?? new PHPMailer(true);

        if (!isset($_ENV['SMTP_HOST'])) {
            throw new RuntimeException('SMTP_HOST is not set');
        }

        // Configuration SMTP
        $this->mailer->isSMTP();
        $this->mailer->Host = $_ENV['SMTP_HOST'];
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = $_ENV['SMTP_USERNAME'] ?? throw new RuntimeException('SMTP_USERNAME is not set');
        $this->mailer->Password = $_ENV['SMTP_PASSWORD'] ?? throw new RuntimeException('SMTP_PASSWORD is not set');
        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mailer->Port = $_ENV['SMTP_PORT'] ?? 587;

        // Configuration de l'expéditeur
        $this->mailer->setFrom(
            $_ENV['SMTP_FROM_EMAIL'] ?? throw new RuntimeException('SMTP_FROM_EMAIL is not set'),
            $_ENV['SMTP_FROM_NAME'] ?? 'FreelanceFlow'
        );
    }

    /**
     * Envoie un email simple via SMTP en utilisant les constantes définies
     * dans config/config.php
     */
    public function sendEmail(
        string $to, 
        string $subject, 
        string $bodyHtml
    ): bool {
        try {
            // Destinataire
            $this->mailer->addAddress($to);

            // Contenu
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $subject;
            $this->mailer->Body    = $bodyHtml;

            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            debug("Erreur Email: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Envoie un email de confirmation pour une mission
     *
     * @param Mission $mission La mission à confirmer
     * @param string $recordId L'ID de la mission dans Airtable
     * @throws RuntimeException Si l'envoi échoue
     */
    public function sendMissionConfirmation(Mission $mission, string $recordId): void
    {
        try {
            // Ajout du destinataire
            $this->mailer->addAddress($mission->getClientEmail());

            // Configuration du contenu
            $this->mailer->isHTML(true);
            $this->mailer->Subject = "Confirmation de votre mission - {$mission->getService()}";
            
            // Construction du corps du message
            $body = "<h1>Confirmation de votre mission</h1>";
            $body .= "<p>Bonjour,</p>";
            $body .= "<p>Nous vous confirmons la prise en compte de votre mission :</p>";
            $body .= "<ul>";
            $body .= "<li><strong>Service :</strong> {$mission->getService()}</li>";
            $body .= "<li><strong>Description :</strong> {$mission->getDescription()}</li>";
            $body .= "<li><strong>Prix :</strong> {$mission->getPrice()} €</li>";
            $body .= "<li><strong>Statut :</strong> {$mission->getStatus()}</li>";
            $body .= "</ul>";
            $body .= "<p>Référence de la mission : {$recordId}</p>";
            $body .= "<p>Nous vous remercions de votre confiance.</p>";
            $body .= "<p>L'équipe FreelanceFlow</p>";

            $this->mailer->Body = $body;

            // Envoi de l'email
            if (!$this->mailer->send()) {
                throw new RuntimeException("Erreur lors de l'envoi de l'email : " . $this->mailer->ErrorInfo);
            }
        } catch (Exception $e) {
            throw new RuntimeException("Erreur lors de l'envoi de l'email : " . $e->getMessage());
        }
    }
}
