<?php

namespace App\Controllers;

use App\Services\AirtableService;
use App\Models\Mission;

class FormController
{
    /**
     * Affiche la vue du formulaire manuel
     */
    public function index(): void
    {
        require __DIR__ . '/../Views/form-view.php';
    }

    /**
     * Traite la soumission du formulaire
     */
    public function submit(): void
    {
        // Exemple de récupération des champs du formulaire
        $service     = $_POST['service']     ?? '';
        $description = $_POST['description'] ?? '';
        $price       = $_POST['price']       ?? 0;
        $clientName  = $_POST['client']      ?? '';
        $clientEmail = $_POST['email']       ?? '';

        // On crée une instance de Mission
        $mission = new Mission(
            $service,
            $description,
            floatval($price),
            $clientName,
            $clientEmail,
            'En attente',     // Status
            'non payée'       // Invoice Status
        );

        // On sauvegarde la mission via AirtableService
        $airtable = new AirtableService();
        $recordId = $airtable->createMission($mission);

        if ($recordId) {
            // Redirection ou message de confirmation
            echo "Mission créée avec succès ! ID dans Airtable : $recordId";
        } else {
            echo "Erreur lors de la création de la mission dans Airtable.";
        }
    }
}
