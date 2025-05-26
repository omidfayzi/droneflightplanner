<?php
// FILE: /var/www/public/app/views/dashboard.php
// Dashboard-pagina voor het Drone Vluchtvoorbereidingssysteem

session_start();
// Laad benodigde configuratie bestanden.
// Zorg ervoor dat deze paden correct zijn in jouw projectstructuur!
require_once __DIR__ . '/../../config/config.php';
// Als 'functions.php' essentiële functies bevat (zoals fetchPropPrefTxt of algemene DB-helpers),
// moet deze ook worden geinclude. Zo niet, verwijder deze regel of pas aan.
// require_once __DIR__ . '/../../functions.php'; 

// --- API URLs configureren ---
// Verzeker dat MAIN_API_URL gedefinieerd is in config/config.php
// Dit is de basis-URL voor jouw Node.js backend
if (!defined('MAIN_API_URL')) {
    define('MAIN_API_URL', 'https://api2.droneflightplanner.nl/');
}
$mainApiBaseUrl = MAIN_API_URL;

// Endpoint voor het ophalen van vluchten van JOUW BACKEND (Node.js/Express)
$flightsApiUrl = $mainApiBaseUrl . 'flightEntries'; // Dit is nu correct om naar flightEntriesEndpoint.js te wijzen

// --- Vluchten en statistieken ophalen ---
$recentFlights = [];
$stats = [ // Standaard statistieken, worden gevuld of blijven 0
    'active_flights' => 0,
    'pending_approval' => 0,
    'total_flights' => 0
];

$ch = curl_init($flightsApiUrl); // Initialiseer cURL sessie
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true, // Retourneer de transfer als een string in plaats van direct uit te voeren
    CURLOPT_TIMEOUT => 10,         // Maximale tijd in seconden om de server respons te krijgen
    CURLOPT_HTTPHEADER => ['Accept: application/json'] // Specificeer dat we JSON verwachten
]);

$flightsResponse = curl_exec($ch); // Voer de cURL sessie uit
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Krijg de HTTP statuscode
curl_close($ch); // Sluit de cURL sessie

// Verwerk de respons
if ($httpCode === 200 && $flightsResponse !== false) {
    $flightsData = json_decode($flightsResponse, true); // Decodeer JSON respons naar een PHP array
    if (is_array($flightsData)) {
        $recentFlights = $flightsData;
        // Basale statistieken berekenen van de opgehaalde vluchten
        $stats['total_flights'] = count($recentFlights);

        // Simuleer actieve/wachtende goedkeuring als deze niet in je database worden beheerd.
        // Anders, filter hier op je status-kolom in $recentFlights.
        $stats['active_flights'] = count(array_filter($recentFlights, fn($f) => ($f['status'] ?? 'Gepland') === 'Lopend')); // Aanname: status-kolom in DB
        $stats['pending_approval'] = count(array_filter($recentFlights, fn($f) => ($f['status'] ?? 'Gepland') === 'Wachtend op goedkeuring'));
    }
} else {
    // Log eventuele fouten bij het ophalen van vluchten
    error_log("DASHBOARD_FETCH_ERROR: Fout bij ophalen vluchten: HTTP $httpCode. Respons: " . ($flightsResponse ?: 'Empty response body'));
    // Optionally, set an error message for the user in the UI if needed
    // $dashboardError = "Kan vluchten niet laden. Probeer later opnieuw.";
}

// --- Pagin-specifieke variabelen voor lay-out template ---
$headTitle = "Dashboard";
// Gebruikersnaam en organisatie uit sessie, worden meestal gezet na login
$userName = $_SESSION['user']['first_name'] ?? 'Onbekend';
$org = $_SESSION['org'] ?? '';
$gobackUrl = 0; // Geen terugknop nodig voor het dashboard
$rightAttributes = 0; // Geen SSO-knop, alleen profielicoon (aanpassen aan je header/template)

// Eventuele functieaanroep die jouw template verwacht. 
// De `fetchPropPrefTxt(1)` in jouw originele code: deze line moet je herstellen
// als die functie daadwerkelijk een output genereert die in je header/template komt.
// Anders, verwijder of commentarieer deze lijn.
// echo fetchPropPrefTxt(1); 

// --- Definitie van de BODY CONTENT voor de template.php ---
$bodyContent = "
    <div class='h-[83.5vh] bg-gray-100 shadow-md rounded-tl-xl w-13/15'>
        <div class='p-6 overflow-y-auto max-h-[calc(90vh-200px)]'>
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
                            <p class='text-sm text-gray-500 mb-1'>Wachtend op Goedkeuring</p>
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
                    <!-- Link naar Nieuwe Vlucht planning (Stap 1) -->
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
                                <th class='p-4 text-left text-gray-600'>Datum</th>
                                <th class='p-4 text-left text-gray-600'>Piloot</th>
                                <th class='p-4 text-left text-gray-600'>SORA ID</th>
                                <th class='p-4 text-left text-gray-600'>SORA Versie</th>
                                <th class='p-4 text-left text-gray-600'>Status</th>
                                <th class='p-4 text-left text-gray-600'>Acties</th>
                            </tr>
                        </thead>
                        <tbody class='divide-y divide-gray-200 text-sm'>";

if (empty($recentFlights)) {
    $bodyContent .= "
        <tr><td colspan='9' class='p-4 text-center text-gray-500'>Geen vluchten gevonden in de database. Start een nieuwe vlucht!</td></tr>
    ";
} else {
    foreach ($recentFlights as $flight) {
        // Formatteer de flight_date van de database (komt als ISO8601 string van Node.js)
        $flightDateObj = new DateTime($flight['flight_date']);
        $formattedFlightDate = $flightDateObj->format('d-m-Y'); // Formatteer naar Nederlandse datumweergave

        // Simuleer een status. In een echt systeem komt dit uit een 'status' kolom in je DB.
        $displayStatus = "Gepland"; // Standaard status als er geen echte status kolom is

        $statusClass = match ($displayStatus) { // Tailwind classes voor statusbadges
            'Afgerond' => 'bg-green-100 text-green-800',
            'Lopend' => 'bg-yellow-100 text-yellow-800',
            'Gepland' => 'bg-blue-100 text-blue-800',
            'Mislukt' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800'
        };

        // Kolommen direct uit de $flight array (komen overeen met je DB-schema en Node.js output)
        $flightId = htmlspecialchars($flight['id'] ?? 'N/A');
        $flightName = htmlspecialchars($flight['flight_name'] ?? 'N/A');
        $flightType = htmlspecialchars($flight['flight_type'] ?? 'N/A');
        $flightPilot = htmlspecialchars($flight['flight_pilot'] ?? 'N/A');
        $soraAnalysisId = htmlspecialchars($flight['sora_analysis_id'] ?? 'N/A');
        $soraVersion = htmlspecialchars($flight['sora_version'] ?? 'N/A');
        // $location = "Lat: " . htmlspecialchars($flight['latitude'] ?? 'N/A') . ", Lon: " . htmlspecialchars($flight['longitude'] ?? 'N/A'); // Als je latitude/longitude kolommen hebt
        $location = "N/A"; // Als er geen specifieke locatie kolom is

        $bodyContent .= "
                                <tr class='hover:bg-gray-50 transition'>
                                    <td class='p-4 font-medium text-gray-800'>" . $flightId . "</td>
                                    <td class='p-4 text-gray-600'>" . $flightName . "</td>
                                    <td class='p-4 text-gray-600'>" . $flightType . "</td>
                                    <td class='p-4 text-gray-600'>" . $formattedFlightDate . "</td>
                                    <td class='p-4 text-gray-600'>" . $flightPilot . "</td>
                                    <td class='p-4 text-gray-600'>" . $soraAnalysisId . "</td>
                                    <td class='p-4 text-gray-600'>" . $soraVersion . "</td>
                                    <td class='p-4'>
                                        <span class='$statusClass px-3 py-1 rounded-full text-sm font-medium'>" . htmlspecialchars($displayStatus) . "</span>
                                    </td>
                                    <td class='p-4 text-right'>
                                        <a href='/frontend/pages/flight-planning/step1.php?edit_flight_id=" . $flightId . "' title='Bewerk vlucht' class='text-gray-600 hover:text-gray-800 transition'>
                                            <i class='fa-solid fa-pencil'></i>
                                        </a>
                                        <button class='text-gray-600 hover:text-gray-800 transition ml-2'>
                                            <i class='fa-solid fa-ellipsis-vertical'></i>
                                        </button>
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

// Inclusie van header-component en template.php. 
// Deze paden zijn relatief vanaf de locatie van dashboard.php: /var/www/public/app/views/
require_once __DIR__ . '/../components/header.php';
require_once __DIR__ . '/layouts/template.php';
