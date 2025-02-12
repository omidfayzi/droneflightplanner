<?php
session_start();
session_unset();
session_destroy();

// Define the redirect URL after logout
//$redirectUri = 'https://app.droneperceelvoorkeuren.nl/';

// URL encode the redirect URI
//$encodedRedirectUri = urlencode($redirectUri);

// Create the Keycloak logout URL with the encoded redirect_uri
//$logoutUrl = 'https://keycloak.holdingthedrones.com/realms/HoldingTheDrones/protocol/openid-connect/logout?redirect_uri=' . $encodedRedirectUri;

// Redirect to Keycloak logout
//header('Location: ' . $logoutUrl);
header('Location: ' . 'https://app.droneperceelvoorkeuren.nl');
exit();
?>
