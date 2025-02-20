<?php
session_start();

require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';
use Dotenv\Dotenv;

// Laad .env-variabelen vanuit /var/www/env
$dotenv = Dotenv::createImmutable('/var/www/env');
$dotenv->load();

use Stevenmaguire\OAuth2\Client\Provider\Keycloak;

// Keycloak-configuratie (uit .env)
$keycloakAuthServerUrl = $_ENV['KEYCLOAK_AUTH_SERVER_URL'];
$keycloakRealm         = $_ENV['KEYCLOAK_REALM'];
$keycloakClientId      = $_ENV['KEYCLOAK_CLIENT_ID'];
$keycloakClientSecret  = $_ENV['KEYCLOAK_CLIENT_SECRET'];
$keycloakRedirectUri   = $_ENV['KEYCLOAK_REDIRECT_URI'];

// Zet error reporting aan voor debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Configureer de Keycloak-provider
$provider = new Keycloak([
    'authServerUrl' => $keycloakAuthServerUrl,
    'realm'         => $keycloakRealm,
    'clientId'      => $keycloakClientId,
    'clientSecret'  => $keycloakClientSecret,
    'redirectUri'   => $keycloakRedirectUri
]);

// Als de gebruiker al is ingelogd, doorsturen naar de welkomstpagina
if (isset($_SESSION['user'])) {
    header('Location: /welcome');
    exit;
}

// Controleer op Keycloak-fouten
if (isset($_GET['error'])) {
    die('Error: ' . htmlspecialchars($_GET['error']));
}

// Afhandeling van de Keycloak-redirect met 'code' en 'state'
if (isset($_GET['code']) && isset($_GET['state'])) {
    if (empty($_SESSION['oauth2state']) || $_GET['state'] !== $_SESSION['oauth2state']) {
        unset($_SESSION['oauth2state']);
        die('Invalid state.');
    }
    
    try {
        // Wissel de autorisatiecode in voor een access token
        $token = $provider->getAccessToken('authorization_code', [
            'code' => $_GET['code']
        ]);
        
        // Haal de gebruikersinformatie op
        $user = $provider->getResourceOwner($token);
        $userData = $user->toArray();
        
        // Sla gebruikersgegevens op in de sessie
        $_SESSION['user'] = [
            'id'         => $userData['sub'] ?? 'Unknown',
            'first_name' => $userData['given_name'] ?? 'Unknown',
            'last_name'  => $userData['family_name'] ?? 'Unknown',
            'email'      => $userData['email'] ?? 'No email',
            'username'   => $userData['preferred_username'] ?? 'No username'
        ];
        
        // Doorsturen naar de welkomstpagina
        header('Location: /welcome');
        exit;
    } catch (Exception $e) {
        die('Failed to get access token or user details: ' . $e->getMessage());
    }
}

// Als er geen 'code' aanwezig is, start dan het loginproces
if (!isset($_GET['code'])) {
    $authUrl = $provider->getAuthorizationUrl([
        'scope' => 'openid profile email'
    ]);
    $_SESSION['oauth2state'] = $provider->getState();
    header('Location: ' . $authUrl);
    exit;
}
?>
