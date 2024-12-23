<?php
/**
 * public/index.php
 *
 * Point d'entrée unique pour FreelanceFlow.
 * Gère le routage vers les contrôleurs appropriés.
 */

declare(strict_types=1);

// Charger la config globale (autoload + .env + définitions de constantes)
require_once __DIR__ . '/../config/config.php';

// Récupération de l'URI demandée
$uri = $_SERVER['REQUEST_URI'] ?? '/';

// Nettoyage basique de l'URI (suppression du query string, etc.)
$parsedUrl = parse_url($uri);
$path = $parsedUrl['path'] ?? '/';

/**
 * Routage basique par "switch case" 
 * Chaque route correspond à un cas.
 * On instancie alors le contrôleur adéquat.
 *
 * Note : on suppose l'existence de ChatController, FormController,
 * etc., dans app/Controllers/.
 * 
 * Vous pouvez personnaliser ce routage selon vos besoins.
 */
switch ($path) {
    case '/':
        // Page d'accueil ou redirection vers /chat, /form, etc.
        echo "Bienvenue sur FreelanceFlow!<br>";
        echo "Essayez le chat C-FORM.";
        break;

    case '/chat':
        $controller = new \App\Controllers\ChatController();
        $controller->index();
        break;
        
    case '/chat/sendMessage':
        $controller = new \App\Controllers\ChatController();
        $controller->sendMessage();
        break;

    case '/chat/submitMission':
        $controller = new \App\Controllers\ChatController();
        $controller->submitMission();
        break;

    // Ajoutez d'autres routes si nécessaire
    // case '/invoice':
    //     // Ex: un InvoiceController
    //     break;

    default:
        // Page 404
        header('HTTP/1.0 404 Not Found');
        echo "404 - Page introuvable";
        break;
}
