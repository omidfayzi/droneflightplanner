<?php
// /var/www/public/app/views/landing-page.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../functions.php';

// Roep de login-functie aan (moet $_SESSION['user']['id'] instellen)
login();

// --- API URL's van $_ENV ---
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
$userOrgBearerToken = $_ENV['USER_ORG_DATABASE_BEARER_TOKEN'];


// --- GECENTRALISEERDE API HULPFUNCTIE ---
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
$functions = [];

if (isset($_SESSION['user']['id'])) {
    if (!empty($userOrgOrganisatiesApiUrl) && !empty($userOrgBearerToken)) {
        $organisationsData = callUserOrgApi($userOrgOrganisatiesApiUrl, $userOrgBearerToken);
        if (!isset($organisationsData['error'])) {
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

    if (!empty($userOrgFunctiesApiUrl) && !empty($userOrgBearerToken)) {
        $functionsData = callUserOrgApi($userOrgFunctiesApiUrl, $userOrgBearerToken);
        if (!isset($functionsData['error'])) {
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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    $inputJSON = file_get_contents('php://input');
    $requestData = json_decode($inputJSON, true);

    if (isset($requestData['action']) && $requestData['action'] === 'save_selection') {
        $selectedOrgId = $requestData['organisation_id'] ?? null;
        $selectedFunctionId = $requestData['function_id'] ?? null;

        if ($selectedOrgId === null || $selectedFunctionId === null || $selectedOrgId === '' || $selectedFunctionId === '') {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Geen organisatie of functie geselecteerd.']);
            exit;
        }

        $_SESSION['selected_organisation_id'] = ($selectedOrgId === '0') ? null : (int)$selectedOrgId;
        $_SESSION['selected_function_id'] = (int)$selectedFunctionId;

        error_log("Sessie opgeslagen: Org ID: " . ($_SESSION['selected_organisation_id'] ?? 'N.V.T.') . ", Functie ID: " . $_SESSION['selected_function_id']);

        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success']);
        exit;
    }
}


// --- Template variabelen instellen ---
$showHeader = 1;
$headTitle = fetchPropPrefTxt(46);
$currentUserName = $_SESSION['user']['first_name'] ?? 'Onbekend';
$gobackUrl = 1;
$rightAttributes = 0;

// Genereer de URL voor organisatieRegistratie.php dynamisch
// __DIR__ is de map van landing-page.php (bijv. /var/www/public/app/views/)
// Dus ../organisatieRegistratie.php zal waarschijnlijk NIET werken, tenzij het EEN map omhoog is.
// Maar als je op het WEBPAD (https://app2.droneflightplanner.nl)
// '/app/views/landing-page.php' benaderd, dan moet het worden:
// '/app/views/organisatieRegistratie.php'

// De relatieve URL op het web:
$baseWebPathForviews = '/app/views/'; // Pas dit aan als je bestanden op een ander relatief pad staan
$organisatieRegistratieUrl = $baseWebPathForviews . 'organisatieRegistratie.php';


$backgroundImageUrl = '/app/assets/images/background.jpg';

$bodyContent = "
    <!-- Modal op voorgrond -->
    <div class='fixed inset-0 z-50 flex items-center justify-center p-4'>
        <!-- Achtergrondafbeelding -->
    <div class='fixed inset-0 z-0'>
        <img src='{$backgroundImageUrl}' alt='Background Image' class='w-full h-full object-cover bg-img' />
    </div>
        <div class='relative w-full max-w-5xl bg-white rounded-2xl shadow-xl overflow-hidden z-50'>
            <!-- Gradient header -->
            <div class='h-2 bg-gradient-to-r from-blue-600 to-green-500'></div>
            
            <div class='p-10'>
                <div class='text-center mb-12'>
                    <h1 class='text-4xl font-bold text-gray-900 mb-4'>Selecteer uw Rol</h1>
                    <p class='text-lg text-gray-600'>Kies hoe u wilt werken in het Drone Flight Planner systeem</p>
                </div>
                
                <div class='grid grid-cols-1 md:grid-cols-2 gap-12'>
                    <!-- Bestaande Organisatie Sectie -->
                    <div class='bg-gray-50 p-8 rounded-xl border border-gray-200'>
                        <div class='flex items-center mb-6'>
                            <div class='bg-blue-100 p-3 rounded-full mr-3'>
                                <i class='fas fa-building text-blue-600 text-xl'></i>
                            </div>
                            <h2 class='text-2xl font-semibold text-gray-800'>Bestaande Organisatie</h2>
                        </div>
                        
                        <p class='text-gray-600 mb-6'>Log in bij een organisatie die reeds geregistreerd is</p>
                        
                        <div class='space-y-6'>
                            <div>
                                <label class='block text-gray-700 font-medium mb-2 text-lg'>Organisatie</label>
                                <div class='relative'>
                                    <select id='orgSelect' class='w-full p-4 border border-gray-300 rounded-xl bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg'>
                                        <option value=''>Selecteer organisatie</option>
                                        <option value='0'>" . htmlspecialchars($currentUserName) . " (Individueel Account)</option>";

foreach ($organisations as $org) {
    $bodyContent .= "<option value='" . htmlspecialchars($org['id']) . "'>" . htmlspecialchars($org['name']) . "</option>";
}

$bodyContent .= "
                                    </select>
                                    <div class='pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700'>
                                        <i class='fas fa-chevron-down'></i>
                                    </div>
                                </div>
                            </div>
                            
                            <div>
                                <label class='block text-gray-700 font-medium mb-2 text-lg'>Functie</label>
                                <div class='relative'>
                                    <select id='functionSelect' class='w-full p-4 border border-gray-300 rounded-xl bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg'>
                                        <option value='' disabled selected>Selecteer uw functie</option>";

foreach ($functions as $func) {
    $bodyContent .= "<option value='" . htmlspecialchars($func['id']) . "'>" . htmlspecialchars($func['name']) . "</option>";
}

$bodyContent .= "
                                    </select>
                                    <div class='pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700'>
                                        <i class='fas fa-chevron-down'></i>
                                    </div>
                                </div>
                            </div>
                            
                            <button
                                type='button'
                                onclick='confirmSelection()'
                                class='w-full py-4 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-medium text-lg transition-colors flex items-center justify-center gap-3'
                            >
                                <i class='fas fa-check-circle'></i> Bevestigen
                            </button>
                        </div>
                    </div>
                    
                    <!-- Nieuwe Organisatie Sectie -->
                    <div class='bg-gray-50 p-8 rounded-xl border border-gray-200'>
                        <div class='flex items-center mb-6'>
                            <div class='bg-green-100 p-3 rounded-full mr-3'>
                                <i class='fas fa-plus-circle text-green-600 text-xl'></i>
                            </div>
                            <h2 class='text-2xl font-semibold text-gray-800'>Nieuwe Organisatie</h2>
                        </div>
                        
                        <p class='text-gray-600 mb-6'>Registreer een compleet nieuwe organisatie in het systeem</p>
                        
                        <ul class='space-y-4 mb-8'>
                            <li class='flex items-start'>
                                <i class='fas fa-check-circle text-green-500 mt-1.5 mr-3 text-xl'></i>
                                <span class='text-lg'>Voeg uw bedrijf of organisatie toe</span>
                            </li>
                            <li class='flex items-start'>
                                <i class='fas fa-check-circle text-green-500 mt-1.5 mr-3 text-xl'></i>
                                <span class='text-lg'>Beheer meerdere dronevluchtprojecten</span>
                            </li>
                            <li class='flex items-start'>
                                <i class='fas fa-check-circle text-green-500 mt-1.5 mr-3 text-xl'></i>
                                <span class='text-lg'>Nodig teamleden uit</span>
                            </li>
                        </ul>
                        
                        <button
                            type='button'
                            onclick='window.location.href=\"{$organisatieRegistratieUrl}\"'
                            class='w-full py-4 bg-green-600 hover:bg-green-700 text-white rounded-xl font-medium text-lg transition-colors flex items-center justify-center gap-3'
                        >
                            <i class='fas fa-plus'></i> Nieuwe Organisatie
                        </button>
                    </div>
                </div>
                
                <div class='mt-12 pt-6 border-t border-gray-200 text-center text-gray-500 text-lg'>
                    <p>U kunt altijd van organisatie wisselen via uw profielinstellingen</p>
                </div>
            </div>
        </div>
    </div>
    
    <style>
        body {
            font-family: 'Montserrat', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            position: relative;
            postion: relative;
        }

        .bg-img {
            filter: blur(5px); 
            brightness(0.7);
            margin-right: 300px;
            postion: absolute;
        }
        
        @media (max-width: 768px) {
            .fixed.inset-0 {
                padding: 1rem;
            }
            .relative.w-full {
                max-width: 100%;
            }
        }
    </style>
";

require_once __DIR__ . '/layouts/template.php';
?>

<script>
    /**
     * confirmSelection()
     * Wordt aangeroepen wanneer de gebruiker op 'Bevestigen' klikt in de 'bestaande organisatie' sectie.
     * Slaat de geselecteerde organisatie-ID en de functie-ID
     * op in de sessie via een AJAX POST en stuurt door naar het dashboard.
     */
    function confirmSelection() {
        const orgSelect = document.getElementById("orgSelect");
        const selectedOrgId = orgSelect.value;

        const functionSelect = document.getElementById("functionSelect");
        const selectedFunctionId = functionSelect.value;

        // Validatie: check of de waardes niet leeg zijn of de disabled "Selecteer..." opties
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
                    action: 'save_selection', // De actie om PHP de sessie te laten updaten
                    organisation_id: selectedOrgId,
                    function_id: selectedFunctionId
                })
            })
            .then(response => {
                if (!response.headers.get('Content-Type')?.includes('application/json')) {
                    throw new Error('Server gaf geen JSON response terug (HTTP status: ' + response.status + ')');
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
                alert("Er is een netwerkfout opgetreden bij het opslaan van uw selectie, of ongeldige response: " + error.message);
            });
    }
</script>