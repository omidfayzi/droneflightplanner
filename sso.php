<?php
// =============== SSO MET KEYCLOAK ===============
// Login flow voor DroneFlightPlanner met OAuth2 (Authorization Code Flow)

session_start(); // nodig om 'state' en user-gegevens in te bewaren

// Composer autoloader: hierdoor werken externe libraries (dotenv + keycloak-provider)
require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

use Dotenv\Dotenv;
use Stevenmaguire\OAuth2\Client\Provider\Keycloak;

// 1) Config laden uit .env  → geen gevoelige info hardcoded
$dotenv = Dotenv::createImmutable('/var/www/env');
$dotenv->load();

// Keycloak-provider met instellingen uit .env
$provider = new Keycloak([
    'authServerUrl' => $_ENV['KEYCLOAK_AUTH_SERVER_URL'], // basis-URL van Keycloak
    'realm'         => $_ENV['KEYCLOAK_REALM'],           // realm = soort “omgeving” in Keycloak
    'clientId'      => $_ENV['KEYCLOAK_CLIENT_ID'],       // ID van onze applicatie
    'clientSecret'  => $_ENV['KEYCLOAK_CLIENT_SECRET'],   // geheim van onze applicatie
    'redirectUri'   => $_ENV['KEYCLOAK_REDIRECT_URI']     // waar Keycloak na login naar terugstuurt
]);

// 2) Als gebruiker al in $_SESSION staat → direct door naar welcome
if (isset($_SESSION['user'])) {
    header('Location: /welcome');
    exit;
}

// 3) Als Keycloak een fout geeft (bijv. user annuleert login), toon die veilig
if (isset($_GET['error'])) {
    die('Error: ' . htmlspecialchars($_GET['error']));
}

// 4) Gebruiker komt terug van Keycloak met code + state
if (isset($_GET['code']) && isset($_GET['state'])) {
    // Controleer state → bescherming tegen CSRF-aanval
    if ($_GET['state'] !== ($_SESSION['oauth2state'] ?? '')) {
        die('Invalid state');
    }

    try {
        // Wissel authorization code om voor access token
        $token = $provider->getAccessToken('authorization_code', [
            'code' => $_GET['code']
        ]);

        // Haal gebruikersinfo op met het token
        $userData = $provider->getResourceOwner($token)->toArray();

        // Zet belangrijkste gegevens in sessie → gebruiker is nu "ingelogd"
        $_SESSION['user'] = [
            'id'       => $userData['sub'] ?? 'Unknown',
            'username' => $userData['preferred_username'] ?? 'Unknown',
            'email'    => $userData['email'] ?? 'No email'
        ];

        // Stuur gebruiker naar de welcome-pagina van DroneFlightPlanner
        header('Location: /welcome');
        exit;
    } catch (Exception $e) {
        // Als ophalen token/user-data faalt
        die('Login failed: ' . htmlspecialchars($e->getMessage()));
    }
}

// 5) Eerste keer /login → nog geen code → start loginproces
// Maak login-URL bij Keycloak en onthoud unieke 'state'
$authUrl = $provider->getAuthorizationUrl([
    'scope' => 'openid profile email' // vraag id, profiel en e-mail op
]);
$_SESSION['oauth2state'] = $provider->getState();

// Stuur gebruiker door naar Keycloak login
header('Location: ' . $authUrl);
exit;
