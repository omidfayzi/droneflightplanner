<?php
// /var/www/public/frontend/pages/flight-planning/step1.php
// Dit bestand mockt nu het aanmaken van een SORA-analyse om de API 500 fout te omzeilen.
// Tijdelijk de call naar EIGEN backend uitgecommentarieerd om stap 2 te kunnen bereiken.

session_start();

// Zorg dat SORA_API_URL en MAIN_API_URL gedefinieerd zijn in config/config.php
if (!defined('SORA_API_URL')) {
    define('SORA_API_URL', 'https://api.dronesora.holdingthedrones.com/');
}
if (!defined('MAIN_API_URL')) {
    define('MAIN_API_URL', 'https://api2.droneflightplanner.nl/');
}
$soraApiBaseUrl = SORA_API_URL;
$mainApiBaseUrl = MAIN_API_URL; // Dit is jouw backend URL

// --- DUMMY USER ID VOOR TESTEN (VERWIJDER IN PRODUCTIE!) ---
if (!isset($_SESSION['user']['id'])) {
    $_SESSION['user']['id'] = 999; // <--- **HERHAALD: ZET HIER EEN WERKELIJK BESTAAND USER_ID DAT IN DE SORA API DB BESTAAT**
    $_SESSION['user']['first_name'] = 'Developer';
    error_log("DEBUG: _SESSION['user']['id'] gesimuleerd met ID: " . $_SESSION['user']['id']);
}
$currentUserId = $_SESSION['user']['id'];
$userName = $_SESSION['user']['first_name'] ?? 'Onbekend';

// --- HULPFUNCTIE VOOR API COMMUNICATIE (Zelfde als eerder) ---
function callExternalApi(string $url, array $payload = [], string $method = 'POST'): array
{
    $ch = curl_init($url);
    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_VERBOSE => true,
        CURLOPT_STDERR => fopen('php://stderr', 'w')
    ];

    if ($method === 'POST' || $method === 'PUT') {
        $options[CURLOPT_POSTFIELDS] = json_encode($payload);
    }
    if ($method === 'PUT') {
        $options[CURLOPT_CUSTOMREQUEST] = 'PUT';
    } elseif ($method === 'POST') {
        $options[CURLOPT_POST] = true;
    }

    curl_setopt_array($ch, $options);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    error_log("API_CALL_LOG: URL: $url, Method: $method, HTTP Code: $httpCode, Payload: " . json_encode($payload) . ", Raw Response: " . ($response ?: '(empty)'));

    if ($response === false) {
        $error = curl_error($ch);
        return ['error' => "cURL connection error: $error", 'http_code' => 0];
    }
    if ($httpCode >= 400) {
        $decodedErrorResponse = json_decode($response, true);
        $errorMsg = $decodedErrorResponse['error'] ?? ($decodedErrorResponse['message'] ?? $response ?: "Onbekende API fout (HTTP $httpCode)");
        return ['error' => "API Fout ($httpCode): $errorMsg", 'http_code' => $httpCode, 'raw_response' => $response];
    }

    $decodedResponse = json_decode($response, true);
    return is_array($decodedResponse) ? $decodedResponse : ['success' => true, 'http_code' => $httpCode];
}

// --- VERWERK FORMULIER SUBMIT (vanaf deze pagina) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $flightName = $_POST['flight_name'] ?? 'Nieuwe Vlucht';
    $selectedSoraVersion = $_POST['sora_version'] ?? '2.5';

    // --- START MOCKING HIER ---
    // In plaats van create_analysis_id via API aan te roepen: GENEREER EEN DUMMY ID.
    // Dit ID moet redelijk uniek zijn voor jouw testgebruik. timestamp + random getal.
    $mockedSoraAnalysisId = "mock_" . time() . mt_rand(1000, 9999);
    error_log("SORA_MOCK: Analyse-ID gemockt: " . $mockedSoraAnalysisId);
    // --- EINDE MOCKING ---

    // 1. We hebben nu een 'sora_analysis_id', gemockt.
    $newSoraAnalysisId = (string)$mockedSoraAnalysisId;

    // --- TIJDELIJKE BYPASS VOOR JOUW EIGEN BACKEND ---
    // COMMENTARIEER DIT BLOK UIT ZODRA JE JOUW BACKEND ENDPOINT '/create_sora_flight_entry'
    // HEBT GEMAAKT EN WERKEND HEBT VOOR POST VERZOEKEN.
    $yourBackendResponse = ['success' => true]; // Altijd succesvol nu.
    error_log("BACKEND_MOCK: De aanroep naar JOUW EIGEN backend voor vluchtregistratie is gemockt als succes.");

    /*
    // Uncomment dit blok en verwijder de mock-regel hierboven wanneer jouw backend werkt.
    $yourFlightCreationUrl = $mainApiBaseUrl . 'create_sora_flight_entry'; // <-- ZORG DAT DEZE BESTAAT EN POST ACCEPTEERT
    $yourBackendPayload = [
        'name' => $flightName,
        'user_id' => $currentUserId,
        'sora_analysis_id' => $newSoraAnalysisId,
        'sora_version' => $selectedSoraVersion,
        'parent_id' => null, // aanpassen indien nodig
        'type' => 'sora' // aanpassen indien nodig
    ];
    $yourBackendResponse = callExternalApi($yourFlightCreationUrl, $yourBackendPayload, 'POST');

    if (isset($yourBackendResponse['error']) || !($yourBackendResponse['success'] ?? false)) {
        $errorMessage = "Fout bij registreren vlucht in eigen backend: " . ($yourBackendResponse['error'] ?? 'Onbekende fout.');
        $_SESSION['form_error'] = $errorMessage;
        header("Location: step1.php");
        exit;
    }
    */
    // --- EINDE TIJDELIJKE BYPASS ---

    // 3. Opslaan van SORA context in de sessie.
    $_SESSION['sora_analysis_id'] = $newSoraAnalysisId;
    $_SESSION['sora_version'] = $selectedSoraVersion;

    // Redirect naar step2.php
    header("Location: step2.php");
    exit;
}

// Haal eventuele foutmelding op van een redirect
$formError = $_SESSION['form_error'] ?? '';
unset($_SESSION['form_error']);
?>
<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Stap 1: Nieuwe Vlucht (DroneDeck Stijl)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://api.mapbox.com/mapbox-gl-js/v2.10.0/mapbox-gl.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        .h-83vh {
            height: 83.5vh;
        }

        .max-h-calc {
            max-height: calc(90vh - 200px);
        }

        #mapbox-map-placeholder {
            height: 400px;
            background-color: #f0f0f0;
            border: 2px dashed #ccc;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #888;
            font-size: 1.25rem;
            text-align: center;
        }
    </style>
</head>

<body class="font-sans antialiased text-gray-900 bg-gray-100">
    <div class="h-83vh bg-white shadow-md rounded-tl-xl w-full mx-auto md:w-13/15 p-4 md:p-8">
        <div class="mb-6">
            <div class="flex justify-center items-center space-x-4">
                <span class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold">1</span>
                <div class="flex-1 h-1 bg-gray-300"></div>
                <span class="w-8 h-8 bg-gray-300 text-gray-600 rounded-full flex items-center justify-center">2</span>
                <div class="flex-1 h-1 bg-gray-300"></div>
                <span class="w-8 h-8 bg-gray-300 text-gray-600 rounded-full flex items-center justify-center">3</span>
                <div class="flex-1 h-1 bg-gray-300"></div>
                <span class="w-8 h-8 bg-gray-300 text-gray-600 rounded-full flex items-center justify-center">4</span>
            </div>
        </div>

        <?php if (!empty($formError)): ?>
            <div class="alert alert-danger" role="alert">
                <?= htmlspecialchars($formError) ?>
            </div>
        <?php endif; ?>

        <div class="p-4 md:p-6 overflow-y-auto max-h-calc">
            <h2 class="text-xl font-bold mb-4 text-gray-800">Stap 1: Basisgegevens & Operationele Quickscan</h2>
            <form action="step1.php" method="post" class="space-y-6">
                <h3 class="text-lg font-semibold text-gray-800">Basisgegevens</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="flight_name" class="block text-sm font-medium text-gray-700 mb-1">Vluchtnaam</label>
                        <input type="text" name="flight_name" id="flight_name" required
                            class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="bv. Inspectie Windmolenpark A" value="TestVlucht_<?= time() ?>">
                    </div>
                    <div>
                        <label for="flight_type" class="block text-sm font-medium text-gray-700 mb-1">Vluchttype</label>
                        <select name="flight_type" id="flight_type" required
                            class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Selecteer vluchttype</option>
                            <option value="route">Route</option>
                            <option value="object">Object Inspectie</option>
                            <option value="oppervlakte">Oppervlakte Mapping</option>
                        </select>
                    </div>
                    <div>
                        <label for="flight_date" class="block text-sm font-medium text-gray-700 mb-1">Geplande Datum</label>
                        <input type="date" name="flight_date" id="flight_date" required
                            class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 flatpickr-input"
                            value="<?= date('Y-m-d') ?>">
                    </div>
                    <div>
                        <label for="flight_pilot" class="block text-sm font-medium text-gray-700 mb-1">Toegewezen Piloot</label>
                        <select name="flight_pilot" id="flight_pilot" required
                            class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Selecteer Piloot...</option>
                            <option value="user1">Jan Smit</option>
                            <option value="user2">Fatima El Moussaoui</option>
                        </select>
                    </div>
                    <div>
                        <label for="flight_drone_model" class="block text-sm font-medium text-gray-700 mb-1">Toegewezen Drone Model (voor SORA)</label>
                        <select name="flight_drone_model" id="flight_drone_model" required
                            class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Selecteer Drone Model...</option>
                            <option value="userworkspace81689723">userworkspace81689723</option>
                            <option value="DJI Mavic 3 Pro">DJI Mavic 3 Pro</option>
                        </select>
                    </div>
                    <div>
                        <label for="flight_payload" class="block text-sm font-medium text-gray-700 mb-1">Toegewezen Payload/AddOn</label>
                        <select name="flight_payload" id="flight_payload"
                            class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Geen extra payload</option>
                            <option value="addon1">Thermische Camera (PL987)</option>
                            <option value="addon2">Lidar Sensor (PL123)</option>
                        </select>
                    </div>
                    <div>
                        <label for="sora_version_select" class="block text-sm font-medium text-gray-700 mb-1">Kies SORA Versie</label>
                        <select name="sora_version" id="sora_version_select"
                            class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="2.5" selected>SORA 2.5</option>
                            <option value="2.0">SORA 2.0</option>
                        </select>
                    </div>
                </div>

                <h3 class="text-lg font-semibold text-gray-800 mt-6">Operationele Situatie (Eerste SORA Inputs)</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1" for="operational_scenario_id">Operationeel Scenario</label>
                        <select name="operational_scenario_id" id="operational_scenario_id" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                            <option value="">Selecteer scenario...</option>
                            <option value="Visual line of sight (VLOS)">Visual line of sight (VLOS)</option>
                            <option value="Extended visual line of sight (EVLOS)">Extended visual line of sight (EVLOS)</option>
                            <option value="Beyond visual line of sight (BVLOS)">Beyond visual line of sight (BVLOS)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1" for="population_density_id">Populatie Dichtheid (grondrisico)</label>
                        <select name="population_density_id" id="population_density_id" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                            <option value="">Selecteer dichtheid...</option>
                            <option value="controlled ground area">controlled ground area</option>
                            <option value="<25">
                                < 25</option>
                            <option value="<250">
                                < 250</option>
                            <option value="<2500">
                                < 2.500</option>
                            <option value="<25000">
                                < 25.000</option>
                            <option value="<250000">
                                < 250.000</option>
                            <option value=">250000">> 250.000</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1" for="max_flight_height">Vlieghoogte (m)</label>
                        <input type="number" id="max_flight_height" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" name="max_flight_height" required value="80" placeholder="Max Hoogte">
                        <span class="badge bg-warning">Maps to SORA 2.1.2 (Height of flight geography) for v2.0 & v2.5</span>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1" for="flight_speed">Vliegsnelheid (m/s)</label>
                        <input type="number" id="flight_speed" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" name="flight_speed" required value="5" placeholder="Snelheid">
                        <span class="badge bg-warning">Maps to SORA 1.1.8 for v2.0 & v2.5</span>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1" for="characteristic_dimension">Maximale Karakteristieke Dimensie (m)</label>
                        <input type="number" step="0.1" id="characteristic_dimension" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" name="characteristic_dimension" required value="1" placeholder="Dimensie">
                        <span class="badge bg-warning">Maps to SORA 1.1.6 for v2.0 & v2.5</span>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1" for="mtom">Maximaal Startgewicht (kg)</label>
                        <input type="number" step="0.1" id="mtom" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" name="mtom" required value="4" placeholder="MTOM">
                        <span class="badge bg-warning">Maps to SORA 1.1.7 for v2.0 & v2.5</span>
                    </div>
                </div>

                <h3 class="text-lg font-semibold text-gray-800 mt-6">Vluchtgebied / Routeplanning</h3>
                <div id="mapbox-map-placeholder" class="w-full rounded-lg">
                    <i class="fa-solid fa-location-dot text-gray-600 text-2xl mr-2"></i>
                    <span class="text-gray-600">Interactieve Kaart (Mapbox) Placeholder</span>
                </div>

                <div class="flex justify-between items-center mt-6">
                    <p class="text-sm text-gray-500">Aangemaakt door: <?= htmlspecialchars($userName) ?></p>
                    <div>
                        <button type="button" onclick="window.location.href='dashboard.php'"
                            class="bg-gray-300 text-gray-800 px-6 py-3 rounded-full hover:bg-gray-400 transition-colors mr-2">Annuleren</button>
                        <button type="submit"
                            class="bg-gray-900 text-white px-6 py-3 rounded-full hover:bg-gray-700 transition-colors">Volgende: Risicoanalyse</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        $(document).ready(function() {
            $('#flight_date').flatpickr({
                dateFormat: 'Y-m-d',
                allowInput: true
            });
        });
    </script>
</body>

</html>