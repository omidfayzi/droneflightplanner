<?php
// Definieer de basis-URL van je API
define('API_BASE_URL', 'http://api2.droneflightplanner.nl/api2');

// Functie voor GET-verzoeken
function apiGet($endpoint) {
    $url = API_BASE_URL . $endpoint;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true); // Zet JSON om naar een PHP-array
}

// Functie voor POST-verzoeken
function apiPost($endpoint, $data) {
    $url = API_BASE_URL . $endpoint;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true); // Zet JSON om naar een PHP-array
}