<?php
// FILE: /var/www/public/app/views/dashboard.php
// Dashboard-pagina voor het Drone Vluchtvoorbereidingssysteem - MET DYNAMISCHE DATA

session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../functions.php';

// Valideren van de gebruikersstatus
if (!isset($_SESSION['user']['id'])) {
    $_SESSION['form_error'] = "U moet ingelogd zijn om het dashboard te bekijken.";
    header("Location: landing-page.php");
    exit;
}

// Sessie-gegevens
$selectedOrgId = $_SESSION['selected_organisation_id'] ?? null;
$loggedInUserId = $_SESSION['user']['id'] ?? null;

// --- API URLs configureren ---
if (!defined('MAIN_API_URL')) {
    define('MAIN_API_URL', 'https://api2.droneflightplanner.nl');
}
$mainApiBaseUrl = MAIN_API_URL;
$flightsApiUrl = $mainApiBaseUrl . '/vluchten';

// API-hulpfunctie
function callMainApi(string $url, string $method = 'GET', array $payload = []): array
{
    $ch = curl_init($url);
    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
        CURLOPT_TIMEOUT => 20,
    ];

    if ($method !== 'GET') {
        $options[CURLOPT_HTTPHEADER][] = 'Content-Type: application/json';
        $options[CURLOPT_POSTFIELDS] = json_encode($payload);
        if ($method === 'PUT') $options[CURLOPT_CUSTOMREQUEST] = 'PUT';
        if ($method === 'DELETE') $options[CURLOPT_CUSTOMREQUEST] = 'DELETE';
        if ($method === 'POST') $options[CURLOPT_POST] = true;
    }

    if (isset($_SESSION['user']['auth_token'])) {
        $options[CURLOPT_HTTPHEADER][] = 'Authorization: Bearer ' . $_SESSION['user']['auth_token'];
    }

    curl_setopt_array($ch, $options);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode >= 400) {
        $decodedError = json_decode($response, true);
        return ['error' => $decodedError['message'] ?? "API-fout ($httpCode)"];
    }

    return json_decode($response, true) ?: [];
}

// --- Vluchten ophalen via API ---
$recentFlights = [];
$apiError = null;

// Bouw API-URL met filters
$queryParams = [];
if ($selectedOrgId) {
    $queryParams[] = 'organisatieId=' . $selectedOrgId;
} else {
    $queryParams[] = 'pilootId=' . $loggedInUserId;
}

$flightsApiUrlWithFilter = $flightsApiUrl . '?' . implode('&', $queryParams);
$flightsResponse = callMainApi($flightsApiUrlWithFilter, 'GET');

if (isset($flightsResponse['error'])) {
    $apiError = $flightsResponse['error'];
    error_log("Dashboard: Fout bij ophalen vluchten: " . $apiError);
} elseif (is_array($flightsResponse)) {
    $recentFlights = $flightsResponse;
}

// Sorteer vluchten op datum (nieuwste eerst)
usort($recentFlights, function ($a, $b) {
    return strtotime($b['startDatumTijd'] ?? '') <=> strtotime($a['startDatumTijd'] ?? '');
});

// Bereken statistieken
$stats = [
    'total_flights' => count($recentFlights),
    'active_flights' => count(array_filter($recentFlights, fn($f) => ($f['status'] ?? '') === 'Lopend')),
    'pending_approval' => count(array_filter($recentFlights, fn($f) => ($f['status'] ?? '') === 'Gepland'))
];

// Dashboard content
$bodyContent = "
    <div class='h-[83.5vh] bg-gray-100 shadow-md rounded-tl-xl w-13/15'>
        <div class='p-6 overflow-y-auto max-h-[calc(90vh-200px)]'>";

if ($apiError) {
    $bodyContent .= "
        <div class='alert alert-danger mb-4' role='alert'>
            Fout bij laden vluchten: " . htmlspecialchars($apiError) . "
        </div>";
}

$bodyContent .= "
            <!-- KPI Grid voor Vluchtstatistieken -->
            <div class='grid grid-cols-1 md:grid-cols-3 gap-6 mb-8'>
                <div class='bg-white p-6 rounded-xl shadow hover:shadow-lg transition'>
                    <div class='flex justify-between items-center'>
                        <div>
                            <p class='text-sm text-gray-500 mb-1'>Actieve Vluchten</p>
                            <p class='text-3xl font-bold text-gray-800'>" . htmlspecialchars($stats['active_flights']) . "</p>
                        </div>
                        <div class='w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center'>
                            <i class='fa-solid fa-rocket text-blue-700'></i>
                        </div>
                    </div>
                </div>
                <div class='bg-white p-6 rounded-xl shadow hover:shadow-lg transition'>
                    <div class='flex justify-between items-center'>
                        <div>
                            <p class='text-sm text-gray-500 mb-1'>Geplande Vluchten</p>
                            <p class='text-3xl font-bold text-gray-800'>" . htmlspecialchars($stats['pending_approval']) . "</p>
                        </div>
                        <div class='w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center'>
                            <i class='fa-solid fa-clock text-yellow-700'></i>
                        </div>
                    </div>
                </div>
                <div class='bg-white p-6 rounded-xl shadow hover:shadow-lg transition'>
                    <div class='flex justify-between items-center'>
                        <div>
                            <p class='text-sm text-gray-500 mb-1'>Totaal Vluchten</p>
                            <p class='text-3xl font-bold text-gray-800'>" . htmlspecialchars($stats['total_flights']) . "</p>
                        </div>
                        <div class='w-12 h-12 bg-green-100 rounded-full flex items-center justify-center'>
                            <i class='fa-solid fa-chart-line text-green-700'></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabel met Recente Vluchten -->
            <div class='bg-white rounded-xl shadow overflow-hidden'>
                <div class='p-6 border-b border-gray-200 flex justify-between items-center'>
                    <h3 class='text-xl font-semibold text-gray-800'>Recente Operaties</h3>
                    <a href='/frontend/pages/flight-planning/step1.php' class='flex items-center text-blue-600 hover:text-blue-800 transition'>
                        <i class='fa-solid fa-plus mr-2'></i> Nieuwe Vlucht
                    </a>
                </div>
                <div class='overflow-x-auto'>
                    <table class='w-full'>
                        <thead class='bg-gray-100 text-sm'>
                            <tr>
                                <th class='p-4 text-left text-gray-600'>ID</th>
                                <th class='p-4 text-left text-gray-600'>Vluchtnaam</th>
                                <th class='p-4 text-left text-gray-600'>Type</th>
                                <th class='p-4 text-left text-gray-600'>Datum/tijd</th>
                                <th class='p-4 text-left text-gray-600'>Locatie</th>
                                <th class='p-4 text-left text-gray-600'>Piloot</th>
                                <th class='p-4 text-left text-gray-600'>Drone</th>
                                <th class='p-4 text-left text-gray-600'>Status</th>
                                <th class='p-4 text-left text-gray-600'>Acties</th>
                            </tr>
                        </thead>
                        <tbody class='divide-y divide-gray-200 text-sm'>";

if (empty($recentFlights)) {
    $bodyContent .= "<tr><td colspan='9' class='p-4 text-center text-gray-500'>Geen vluchten gevonden</td></tr>";
} else {
    foreach ($recentFlights as $flight) {
        $flightId = $flight['id'] ?? 'N/A';
        $flightName = htmlspecialchars($flight['vluchtNaam'] ?? 'Geen naam');
        $flightType = htmlspecialchars($flight['typeNaam'] ?? ($flight['vluchtTypeId'] ?? 'N/A'));
        $pilotName = htmlspecialchars($flight['pilootNaam'] ?? ($flight['pilootId'] ?? 'N/A'));
        $droneName = htmlspecialchars($flight['droneNaam'] ?? ($flight['droneId'] ?? 'N/A'));
        $location = htmlspecialchars($flight['locatie'] ?? 'Onbekend');

        // Datum/tijd formatteren
        $formattedDateTime = 'Onbekend';
        if (!empty($flight['startDatumTijd'])) {
            try {
                $date = new DateTime($flight['startDatumTijd']);
                $formattedDateTime = $date->format('d-m-Y H:i');
            } catch (Exception $e) {
                $formattedDateTime = 'Ongeldige datum';
            }
        }

        // Status styling
        $status = $flight['status'] ?? 'Onbekend';
        $statusClass = 'bg-gray-100 text-gray-800'; // Default

        switch ($status) {
            case 'Gepland':
                $statusClass = 'bg-blue-100 text-blue-800';
                break;
            case 'Lopend':
                $statusClass = 'bg-yellow-100 text-yellow-800';
                break;
            case 'Afgerond':
                $statusClass = 'bg-green-100 text-green-800';
                break;
            case 'Geannuleerd':
                $statusClass = 'bg-red-100 text-red-800';
                break;
        }

        $bodyContent .= "
            <tr class='hover:bg-gray-50 transition'>
                <td class='p-4 font-medium text-gray-800'>$flightId</td>
                <td class='p-4 text-gray-600'>$flightName</td>
                <td class='p-4 text-gray-600'>$flightType</td>
                <td class='p-4 text-gray-600'>$formattedDateTime</td>
                <td class='p-4 text-gray-600'>$location</td>
                <td class='p-4 text-gray-600'>$pilotName</td>
                <td class='p-4 text-gray-600'>$droneName</td>
                <td class='p-4'>
                    <span class='$statusClass px-3 py-1 rounded-full text-sm font-medium'>$status</span>
                </td>
                <td class='p-4 text-right'>
                    <a href='#' title='Vlucht details bekijken' class='text-gray-600 hover:text-gray-800 transition'>
                        <i class='fa-solid fa-circle-info'></i>
                    </a>
                </td>
            </tr>";
    }
}

$bodyContent .= "
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
";

// Inclusie van header-component en template.php
require_once __DIR__ . '/../components/header.php';
require_once __DIR__ . '/layouts/template.php';
