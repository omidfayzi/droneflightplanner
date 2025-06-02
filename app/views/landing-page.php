<?php
// /var/www/public/frontend/pages/landing-page.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../functions.php'; // Zorg dat fetchPropPrefTxt() en login() hierin zitten.

// Roep de login-functie aan (moet $_SESSION['user']['id'] instellen)
login();

// --- API URL's van $_ENV (geladen via Dotenv in config.php) ---
// Let op: API_BASE_URL hieronder moet de basis zijn voor functies/organisaties API's
// Voorbeeld: http://devserv01.holdingthedrones.com:4539
// En specifieke paden naar functies en organisaties, zoals getoond in je curl outputs
if (!defined('USER_ORG_API_BASE_URL')) {
    define('USER_ORG_API_BASE_URL', $_ENV['USER_ORG_DATABASE_BASE_URL'] ?? 'http://devserv01.holdingthedrones.com:4539');
}
if (!defined('USER_ORG_FUNCTIES_ENDPOINT')) {
    define('USER_ORG_FUNCTIES_ENDPOINT', USER_ORG_API_BASE_URL . '/functies');
}
if (!defined('USER_ORG_ORGANISATIES_ENDPOINT')) {
    define('USER_ORG_ORGANISATIES_ENDPOINT', USER_ORG_API_BASE_URL . '/organisaties');
}
if (!defined('USER_ORG_BEARER_TOKEN')) {
    define('USER_ORG_BEARER_TOKEN', $_ENV['USER_ORG_DATABASE_BEARER_TOKEN'] ?? 'JOUW_TOKEN_HIER');
}


$userOrgApiBaseUrl = USER_ORG_API_BASE_URL;
$userOrgFunctiesApiUrl = USER_ORG_FUNCTIES_ENDPOINT;
$userOrgOrganisatiesApiUrl = USER_ORG_ORGANISATIES_ENDPOINT;
$userOrgBearerToken = USER_ORG_BEARER_TOKEN;

// --- GECENTRALISEERDE API HULPFUNCTIE (met BEARER TOKEN) ---
function callUserOrgApi(string $url, string $token): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Authorization: Bearer ' . $token
        ],
        CURLOPT_TIMEOUT => 10,
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200 || $resp === false) {
        $error = curl_error($ch) ?: "HTTP Code $code";
        error_log("User/Org API Error ($url): $error - Response: " . ($resp ?: '(empty)'));
        return ['error' => $error];
    }
    $json = json_decode($resp, true);
    return is_array($json) ? $json : ['error' => "Invalid JSON response from User/Org API."];
}


// --- DATA OPHALEN BIJ HET LADEN VAN DE PAGINA (voor dropdowns) ---
$organisations = [];
$functions = []; // De lijst van functies (PIC, Magazijnmedewerker etc.)

// Alleen API calls doen als de gebruiker ingelogd is
if (isset($_SESSION['user']['id'])) {
    // 1. Organisaties ophalen
    if (!empty($userOrgOrganisatiesApiUrl) && !empty($userOrgBearerToken)) {
        $organisationsData = callUserOrgApi($userOrgOrganisatiesApiUrl, $userOrgBearerToken);
        if (!isset($organisationsData['error'])) {
            // Pas key names aan op de werkelijke respons (organisatieId, organisatienaam)
            $organisations = array_map(function ($org) {
                return [
                    'id' => $org['organisatieId'],
                    'name' => $org['organisatienaam']
                ];
            }, $organisationsData);
        } else {
            error_log("Fout bij ophalen organisaties: " . $organisationsData['error']);
        }
    }

    // 2. Functies ophalen (PIC, Magazijnmedewerker etc.)
    if (!empty($userOrgFunctiesApiUrl) && !empty($userOrgBearerToken)) {
        $functionsData = callUserOrgApi($userOrgFunctiesApiUrl, $userOrgBearerToken);
        if (!isset($functionsData['error'])) {
            // Pas key names aan op de werkelijke respons (functieId, functieNaam)
            $functions = array_map(function ($func) {
                return [
                    'id' => $func['functieId'],
                    'name' => $func['functieNaam']
                ];
            }, $functionsData);
        } else {
            error_log("Fout bij ophalen functies: " . $functionsData['error']);
        }
    }
}


// --- AFHANDELING VAN AJAX POST REQUESTS (om selectie in sessie op te slaan) ---
// JavaScript stuurt een POST met action: 'save_selection'
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    $inputJSON = file_get_contents('php://input');
    $requestData = json_decode($inputJSON, true);

    if (isset($requestData['action']) && $requestData['action'] === 'save_selection') {
        $selectedOrgId = $requestData['organisation_id'] ?? null;
        $selectedFunctionId = $requestData['function_id'] ?? null; // Nu is dit een functie ID (PIC, etc.)

        if ($selectedOrgId === null || $selectedFunctionId === null || $selectedOrgId === '' || $selectedFunctionId === '') {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Geen organisatie of functie geselecteerd.']);
            exit;
        }

        // Sessie variabelen instellen
        // Een '0' betekent 'Individueel', dus geen organisatie ID.
        $_SESSION['selected_organisation_id'] = ($selectedOrgId === '0') ? null : (int)$selectedOrgId;
        // 'selected_function_id' is het ID van de gekozen functie (bijv. 1 voor PIC)
        $_SESSION['selected_function_id'] = (int)$selectedFunctionId;

        // $_SESSION['user_id'] is het ingelogde user_id. Dat blijft zo.
        // We koppelen NIET het user_id aan de 'functieId' omdat 'functie' hier een roltype is, niet een specifiek persoon.

        error_log("Sessie opgeslagen: Org ID: " . ($_SESSION['selected_organisation_id'] ?? 'N.V.T.') . ", Functie ID: " . $_SESSION['selected_function_id']);

        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success']);
        exit;
    }
}


// --- Template variabelen instellen (zoals jij het al had) ---
$showHeader = 1;
$headTitle = fetchPropPrefTxt(46);
$currentUserName = $_SESSION['user']['first_name'] ?? 'Onbekend';
$gobackUrl = 1;
$rightAttributes = 0;


$bodyContent = "
    <div class='fixed inset-0 bg-black bg-opacity-90 flex items-center justify-center z-50'>
        <div class='w-[90%] bg-white rounded-xl p-5 max-w-md'>
            <h1 class='pb-2'>Selecteer organisatie en functie</h1>

            <label for='orgSelect' class='block text-gray-700 text-sm font-bold mb-1'>Organisatie:</label>
            <select id='orgSelect' class='rounded-xl w-full mb-3' style='padding: 10px; background-color: #D9D9D9;'>
                <option value=''>Selecteer organisatie</option>
                <option value='0'>" . htmlspecialchars($currentUserName) . " (Individueel Account)</option>";
// Vul organisaties in
foreach ($organisations as $org) {
    $bodyContent .= "<option value='" . htmlspecialchars($org['id']) . "'>" . htmlspecialchars($org['name']) . "</option>";
}
$bodyContent .= "
            </select>

            <label for='functionSelect' class='block text-gray-700 text-sm font-bold mb-1'>Kies Functie:</label>
            <select id='functionSelect' class='rounded-xl w-full mb-2' style='padding: 10px; background-color: #D9D9D9;'>
                <option value='' disabled selected>Selecteer uw functie</option>";

// Vul de functies in
foreach ($functions as $func) {
    $bodyContent .= "<option value='" . htmlspecialchars($func['id']) . "'>" . htmlspecialchars($func['name']) . "</option>";
}
$bodyContent .= "
            </select>

            <input
                type='button'
                value='Bevestigen'
                onclick='confirmSelection()'
                class='text-white bg-blue-500 hover:bg-blue-700 rounded-xl w-full'
                style='padding: 10px; cursor: pointer;'
            >
        </div>
    </div>
";

require_once __DIR__ . '/layouts/template.php';
?>

<script>
    /**
     * confirmSelection()
     * Wordt aangeroepen wanneer de gebruiker op 'Bevestigen' klikt.
     * Slaat de geselecteerde organisatie-ID en de functie-ID
     * op in de sessie via een AJAX POST en stuurt door naar het dashboard.
     */
    function confirmSelection() {
        const orgSelect = document.getElementById("orgSelect");
        const selectedOrgId = orgSelect.value; // "0" voor individueel, anders organisatie ID

        const functionSelect = document.getElementById("functionSelect"); // Nu is dit #functionSelect
        const selectedFunctionId = functionSelect.value; // Dit is het ID van de geselecteerde functie (PIC, etc.)

        if (selectedOrgId === "" || selectedFunctionId === "" || orgSelect.selectedIndex === 0 || functionSelect.selectedIndex === 0) {
            alert("Selecteer alstublieft zowel een organisatie als een functie.");
            return;
        }

        // Stuur AJAX POST om de PHP sessie bij te werken
        fetch(window.location.href, { // Stuur naar dit PHP script zelf
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    action: 'save_selection',
                    organisation_id: selectedOrgId,
                    function_id: selectedFunctionId
                })
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => {
                        throw new Error(err.message || 'Server error');
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    console.log("Sessie opgeslagen. Doorsturen naar dashboard...");
                    window.location.href = "dashboard.php";
                } else {
                    alert("Fout bij opslaan selectie: " + (data.message || "Onbekend"));
                }
            })
            .catch(error => {
                console.error('Fout bij AJAX opslaan selectie:', error);
                alert("Er is een netwerkfout opgetreden bij het opslaan van uw selectie: " + error.message);
            });
    }
</script>