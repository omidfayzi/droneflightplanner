<?php
// /var/www/public/frontend/pages/dashboard.php
// Dashboard-pagina voor het Drone Vluchtvoorbereidingssysteem

// Laad benodigde bestanden
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../functions.php';

// Haal data op van de API
$apiBaseUrl = "http://devserv01.holdingthedrones.com:4539";
$flightsUrl = "$apiBaseUrl/flights";
$statsUrl = "$apiBaseUrl/flights/stats";

// Haal vluchten op
$flightsResponse = file_get_contents($flightsUrl);
$recentFlights = $flightsResponse ? json_decode($flightsResponse, true) : [];

// Haal statistieken op
$statsResponse = file_get_contents($statsUrl);
$stats = $statsResponse ? json_decode($statsResponse, true) : [
    'active_flights' => 0,
    'pending_approval' => 0,
    'total_flights' => 0
];

// Stel pagina-specifieke variabelen in
$headTitle = "Dashboard";
$userName = $_SESSION['user']['first_name'] ?? 'Onbekend';
$org = $_SESSION['org'] ?? '';
$gobackUrl = 0; // Geen terugknop nodig
$rightAttributes = 0; // Geen SSO-knop, alleen profielicoon

// Roep functie aan (verondersteld gedefinieerd in functions.php)
echo fetchPropPrefTxt(1);

// Definieer body content met dynamische data
$bodyContent = "
    <div class='h-[83.5vh] bg-gray-100 shadow-md rounded-tl-xl w-13/15'>
        <div class='p-6 overflow-y-auto max-h-[calc(90vh-200px)]'>
            <!-- KPI Grid -->
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

            <!-- Recente Vluchten Tabel -->
            <div class='bg-white rounded-xl shadow overflow-hidden'>
                <div class='p-6 border-b border-gray-200 flex justify-between items-center'>
                    <h3 class='text-xl font-semibold text-gray-800'>Recente Operaties</h3>
                    <button class='flex items-center text-blue-600 hover:text-blue-800 transition'>
                        <i class='fa-solid fa-plus mr-2'></i> Nieuwe Vlucht
                    </button>
                </div>
                <div class='overflow-x-auto'>
                    <table class='w-full'>
                        <thead class='bg-gray-100 text-sm'>
                            <tr>
                                <th class='p-4 text-left text-gray-600'>Vlucht ID</th>
                                <th class='p-4 text-left text-gray-600'>Type</th>
                                <th class='p-4 text-left text-gray-600'>Locatie (coördinaten)</th>
                                <th class='p-4 text-left text-gray-600'>Uitgevoerd door</th>
                                <th class='p-4 text-left text-gray-600'>Status</th>
                                <th class='p-4 text-left text-gray-600'></th>
                            </tr>
                        </thead>
                        <tbody class='divide-y divide-gray-200 text-sm'>";
foreach ($recentFlights as $flight) {
    $statusClass = match ($flight['DFPSFLI_Status']) {
        'Afgerond' => 'bg-green-100 text-green-800',
        'Lopend' => 'bg-yellow-100 text-yellow-800',
        'Gepland' => 'bg-blue-100 text-blue-800',
        'Mislukt' => 'bg-red-100 text-red-800',
        default => 'bg-gray-100 text-gray-800'
    };
    // Combineer latitude en longitude voor weergave (voorlopig NULL)
    $location = ($flight['DFPSFLI_Latitude'] && $flight['DFPSFLI_Longitude'])
        ? "Lat: " . htmlspecialchars($flight['DFPSFLI_Latitude']) . ", Long: " . htmlspecialchars($flight['DFPSFLI_Longitude'])
        : "Onbekend";
    $bodyContent .= "
                                <tr class='hover:bg-gray-50 transition'>
                                    <td class='p-4 font-medium text-gray-800'>" . htmlspecialchars($flight['DFPSFLI_Id']) . "</td>
                                    <td class='p-4 text-gray-600'>" . htmlspecialchars($flight['DFPSFLI_Type']) . "</td>
                                    <td class='p-4 text-gray-600'>" . $location . "</td>
                                    <td class='p-4 text-gray-600'>" . htmlspecialchars($flight['DFPSFLI_ExecuteBy']) . "</td>
                                    <td class='p-4'>
                                        <span class='$statusClass px-3 py-1 rounded-full text-sm font-medium'>" . htmlspecialchars($flight['DFPSFLI_Status']) . "</span>
                                    </td>
                                    <td class='p-4 text-right'>
                                        <button class='text-gray-600 hover:text-gray-800 transition'>
                                            <i class='fa-solid fa-ellipsis-vertical'></i>
                                        </button>
                                    </td>
                                </tr>";
}
$bodyContent .= "
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
";

// Inclusie van header-component en template.php met relatieve paden
require_once '../components/header.php';
require_once __DIR__ . '/layouts/template.php';
