<?php
/**
 * In deze file komen je herbruikbare functies.
 * Plaats deze in: /backend/functions/functions.php
 */

// Alleen session_start() als er nog geen sessie actief is
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * login()
 * Functie om te controleren of bepaalde session-variabelen gezet zijn.
 */
function login() {
    // Check for session state and handle accordingly
    if (isset($_GET["state"]) && !isset($_SESSION["state"])) {
        $_SESSION["state"] = $_GET["state"];
    }
    
    if (isset($_GET["session_state"]) && !isset($_SESSION["session_state"])) {
        $_SESSION["session_state"] = $_GET["session_state"];
    }
    
    if (isset($_GET["code"]) && !isset($_SESSION["code"])) {
        $_SESSION["code"] = $_GET["code"];
    }
    
    if (!isset($_SESSION["oauth2state"])) {
        echo "<script> location.href='/index.php'; </script>";
        exit("exit");
    }
}

/**
 * fetchPropPrefTxt()
 * Haalt tekst op via een externe API en kijkt naar de gekozen taal.
 */
function fetchPropPrefTxt($id) {
    $url = "https://api2.droneflightplanner.nl/get-txt-with-id/$id"; 

    // Ophalen van data via file_get_contents
    $response = file_get_contents($url);

    if ($response === false) {
        die('Error occurred while fetching data.');
    }

    $data = json_decode($response, true);
    if (!isset($data['users']) || empty($data['users'])) {
        die('No data found.');
    }

    $user = $data['users'][0]; // Altijd één rij volgens jouw API

    // Bepaal de taal via cookie of standaard op Engels
    $language_id = $_COOKIE['language_id'] ?? "PropPrefTxt_En";

    return $user[$language_id];
}
