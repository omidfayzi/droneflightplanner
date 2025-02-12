<?php
session_start();
require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';
use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable('/var/www/env');
$dotenv->load();

use Stevenmaguire\OAuth2\Client\Provider\Keycloak;

// Keycloak (.ENV)
$keycloakAuthServerUrl = $_ENV['KEYCLOAK_AUTH_SERVER_URL'];
$keycloakRealm = $_ENV['KEYCLOAK_REALM'];
$keycloakClientId = $_ENV['KEYCLOAK_CLIENT_ID'];
$keycloakClientSecret = $_ENV['KEYCLOAK_CLIENT_SECRET'];
$keycloakRedirectUri = $_ENV['KEYCLOAK_REDIRECT_URI'];

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Configure the Keycloak provider
$provider = new Stevenmaguire\OAuth2\Client\Provider\Keycloak([
    'authServerUrl' => $keycloakAuthServerUrl,
    'realm'         => $keycloakRealm,
    'clientId'      => $keycloakClientId,
    'clientSecret'  => $keycloakClientSecret,
    'redirectUri'   => $keycloakRedirectUri
]);

//Check if the user is already logged in
if (isset($_SESSION['user'])) {
    header('Location: ./welcome');
    exit;
}

// Check for Keycloak errors
if (isset($_GET['error'])) {
    die('Error: ' . htmlspecialchars($_GET['error']));
}

// Handle Keycloak redirect with 'code' and 'state'
if (isset($_GET['code']) && isset($_GET['state'])) {
    // Validate the state parameter
    if (empty($_SESSION['oauth2state']) || $_GET['state'] !== $_SESSION['oauth2state']) {
        unset($_SESSION['oauth2state']);
        die('Invalid state.');
    }

    try {
        // Exchange the authorization code for an access token
        $token = $provider->getAccessToken('authorization_code', [
            'code' => $_GET['code']
        ]);

        // Fetch the user's information
        $user = $provider->getResourceOwner($token);
        $userData = $user->toArray();

        // Store user details in the session
        $_SESSION['user'] = [
            'id'         => $userData['sub'] ?? 'Unknown', // Add user ID (sub)
            'first_name' => $userData['given_name'] ?? 'Unknown',
            'last_name'  => $userData['family_name'] ?? 'Unknown',
            'email'      => $userData['email'] ?? 'No email',
            'username'   => $userData['preferred_username'] ?? 'No username'
        ];
        // Redirect to the dashboard
        header('Location: ./welcome');
        exit;
    } catch (Exception $e) {
        die('Failed to get access token or user details: ' . $e->getMessage());
    }
}

// If no session and no 'code', start the login process
if (!isset($_GET['code'])) {
    $authUrl = $provider->getAuthorizationUrl([
        'scope' => 'openid profile email'
    ]);
    $_SESSION['oauth2state'] = $provider->getState();
    header('Location: ' . $authUrl);
    exit;
}
