<?php
/**
 * config/config.php
 *
 * Fichier de configuration global pour FreelanceFlow.
 * - Charge les variables d'environnement (.env)
 * - Définit des constantes et une fonction getConfig() pour y accéder
 */

declare(strict_types=1);

use Dotenv\Dotenv;

/**
 * Inclusion de l'autoload généré par Composer
 * pour que nos classes (App\...) soient chargées automatiquement
 */
require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Initialisation de Dotenv
 */
$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

/**
 * Définition des principales constantes pour l’API OpenAI
 */
define('OPENAI_API_KEY',         $_ENV['OPENAI_API_KEY']         ?? '');

/**
 * Définition des constantes pour Airtable
 */
define('AIRTABLE_API_KEY',       $_ENV['AIRTABLE_API_KEY']       ?? '');
define('AIRTABLE_BASE_ID',       $_ENV['AIRTABLE_BASE_ID']       ?? '');
define('AIRTABLE_MISSIONS_TABLE', $_ENV['AIRTABLE_MISSIONS_TABLE'] ?? '');

/**
 * Paramètres d'environnement et debug
 */
define('APP_ENV',                $_ENV['APP_ENV']                ?? 'production');
define('APP_DEBUG',              filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN));

/**
 * Configuration pour l’envoi d’emails
 * (SMTP, expéditeur par défaut, etc.)
 */
define('SMTP_HOST',              trim($_ENV['SMTP_HOST'] ?? ''));
define('SMTP_PORT',              trim($_ENV['SMTP_PORT'] ?? '587'));
define('SMTP_USERNAME',          trim($_ENV['SMTP_USERNAME'] ?? ''));
define('SMTP_PASSWORD',          trim($_ENV['SMTP_PASSWORD'] ?? ''));
define('SMTP_FROM_EMAIL',        trim($_ENV['SMTP_FROM_EMAIL'] ?? ''));
define('SMTP_FROM_NAME',         trim($_ENV['SMTP_FROM_NAME'] ?? 'FreelanceFlow'));

/**
 * Vous pouvez ajouter ici d'autres variables de configuration si besoin
 */

/**
 * Petite fonction de debug (active uniquement si APP_DEBUG = true)
 */
if (!function_exists('debug')) {
    function debug(mixed $var, bool $dump = false): void
    {
        if (APP_DEBUG) {
            echo '<pre style="background: #f8f8f8; border: 1px solid #ccc; padding: 10px;">';
            $dump ? var_dump($var) : print_r($var);
            echo '</pre>';
        }
    }
}

/**
 * Retourne un tableau associatif de la config, utile si on souhaite
 * récupérer les valeurs sous forme de tableau au lieu d'utiliser directement
 * les constantes.
 */
function getConfig(): array
{
    return [
        'openai_api_key'         => OPENAI_API_KEY,
        'openai_assistant_id'    => OPENAI_ASSISTANT_ID,
        'airtable_api_key'       => AIRTABLE_API_KEY,
        'airtable_base_id'       => AIRTABLE_BASE_ID,
        'airtable_missions_table'=> AIRTABLE_MISSIONS_TABLE,
        'app_env'                => APP_ENV,
        'app_debug'              => APP_DEBUG,
        'smtp' => [
            'host'       => SMTP_HOST,
            'port'       => SMTP_PORT,
            'username'   => SMTP_USERNAME,
            'password'   => SMTP_PASSWORD,
            'from_email' => SMTP_FROM_EMAIL,
            'from_name'  => SMTP_FROM_NAME,
        ],
    ];
}
