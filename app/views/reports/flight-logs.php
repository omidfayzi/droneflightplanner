<?php
// /var/www/public/frontend/pages/flight-logs/index.php (of flight-logs.php)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../functions.php';

// --- DATA SIMULATIE VOOR VLUCHTLOGS (NEDERLANDS & CORRECTE KOLOMMEN) ---
$flightLogs = [
    [
        'Piloot' => 'Jan de Vries',
        'Drone' => 'DJI Matrice 300 RTK (M300-001)',
        'DroneFabrikant' => 'DJI',
        'DroneModel' => 'Matrice 300 RTK',
        'DroneSerienummer' => 'M300-001',
        'Batterij' => 'TB60 Set A',
        'TaakReferentie' => 'INSP-WIND-0724-A',
        'TaakType' => 'Inspectie Windturbine',
        'KlantNaam' => 'EnergieDirect Wind BV',
        'Locatie' => 'Windpark Flevopolder, Turbine #12',
        'Postcode' => '8251 PA',
        'GridReferentie' => 'RD 153200 488500',
        'Latitude' => '52.438762',
        'Longitude' => '5.673211',
        'StartTijd' => '2024-07-25 10:15',
        'EindTijd' => '2024-07-25 10:55',
        'TotaalMinuten' => 40,
        'LoS' => 'VLOS', // Visual Line of Sight
        'DagNacht' => 'Dag',
        'RolPiloot' => 'PIC (Pilot in Command)',
        'BronLog' => 'Automatisch (DJI Sync)',
        'Notities' => 'Sterke zijwind, extra voorzichtig geland.'
    ],
    [
        'Piloot' => 'Amina El Amrani',
        'Drone' => 'Autel Evo II Pro (EVO-P-005)',
        'DroneFabrikant' => 'Autel Robotics',
        'DroneModel' => 'Evo II Pro',
        'DroneSerienummer' => 'EVO-P-005',
        'Batterij' => 'EVO Accu #3',
        'TaakReferentie' => 'MAP-AGRI-0724-C',
        'TaakType' => 'Kartering Perceel',
        'KlantNaam' => 'Boerderij "De Goede Grond"',
        'Locatie' => 'Akkerbouwgebied Maasdriel',
        'Postcode' => '5331 KD',
        'GridReferentie' => 'RD 148900 419500',
        'Latitude' => '51.812345',
        'Longitude' => '5.289876',
        'StartTijd' => '2024-07-26 09:00',
        'EindTijd' => '2024-07-26 09:35',
        'TotaalMinuten' => 35,
        'LoS' => 'EVLOS', // Extended Visual Line of Sight
        'DagNacht' => 'Dag',
        'RolPiloot' => 'PIC',
        'BronLog' => 'Handmatig Ingevuld',
        'Notities' => 'Uitstekende zichtbaarheid, missie voltooid zoals gepland.'
    ],
    [
        'Piloot' => 'Jan de Vries',
        'Drone' => 'DJI Mavic 3 Thermal (M3T-002)',
        'DroneFabrikant' => 'DJI',
        'DroneModel' => 'Mavic 3 Thermal',
        'DroneSerienummer' => 'M3T-002',
        'Batterij' => 'M3T Accu #1, M3T Accu #2',
        'TaakReferentie' => 'THERM-SOLAR-0724-B',
        'TaakType' => 'Thermische Inspectie Zonnepanelen',
        'KlantNaam' => 'GroenStroom Parken',
        'Locatie' => 'Zonnepark A7, Hoorn',
        'Postcode' => '1627 LX',
        'GridReferentie' => 'RD 133200 531500',
        'Latitude' => '52.654321',
        'Longitude' => '5.056789',
        'StartTijd' => '2024-07-27 14:00',
        'EindTijd' => '2024-07-27 15:10',
        'TotaalMinuten' => 70,
        'LoS' => 'VLOS',
        'DagNacht' => 'Dag',
        'RolPiloot' => 'PIC',
        'BronLog' => 'Automatisch (DroneLogbook Sync)',
        'Notities' => 'Twee batterijwissels nodig. Enkele hotspots gedetecteerd op paneel B-07.'
    ],
];

// Bereken totale vliegtijd
$totalFlightMinutes = 0;
foreach ($flightLogs as $log) {
    $totalFlightMinutes += (int)($log['TotaalMinuten'] ?? 0);
}
$totalFlightHours = floor($totalFlightMinutes / 60);
$remainingMinutes = $totalFlightMinutes % 60;
$totalFlightTimeString = sprintf("%d uur %02d minuten", $totalFlightHours, $remainingMinutes);
// --- EINDE DATA SIMULATIE ---

$showHeader = 1;
$userName = $_SESSION['user']['first_name'] ?? 'Onbekend';
$headTitle = "Vlucht Logs";
$gobackUrl = 0;
$rightAttributes = 0;

$bodyContent = "
    <div class='h-full bg-gray-100 shadow-md rounded-tl-xl w-full flex flex-col'>
        <div class='p-6 bg-white flex justify-between items-center border-b border-gray-200 flex-shrink-0'>
            <div class='flex space-x-6 text-sm font-medium'>
                <a href='flight-logs.php' class='text-gray-900 border-b-2 border-black pb-2'>Vlucht Logs</a>
                <a href='incidents.php' class='text-gray-600 hover:text-gray-900'>Incidenten</a>
            </div>
            <button class='bg-black text-white px-4 py-2 rounded-lg hover:bg-gray-800 transition-colors text-sm'>
                <i class='fa-solid fa-plus mr-2'></i>Nieuwe Vluchtplanning
            </button>
        </div>

        <div class='p-6 overflow-y-auto flex-grow'>
            <div class='bg-white rounded-lg shadow overflow-hidden'>
                <div class='p-6 border-b border-gray-200 flex justify-between items-center'>
                    <h2 class='text-xl font-semibold text-gray-800'>Overzicht Vlucht Logs</h2>
                    <div>
                        <input type='text' placeholder='Zoek vlucht...' class='px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-blue-500'>
                    </div>
                </div>
                <div class='overflow-x-auto'>
                    <table class='w-full'>
                        <thead class='bg-gray-50 text-xs uppercase text-gray-700'>
                            <tr>
                                <th class='px-4 py-3 text-left'>Piloot</th>
                                <th class='px-4 py-3 text-left'>Drone</th>
                                <th class='px-4 py-3 text-left'>Drone Fabrikant</th>
                                <th class='px-4 py-3 text-left'>Drone Model</th>
                                <th class='px-4 py-3 text-left'>Drone S/N</th>
                                <th class='px-4 py-3 text-left'>Batterij</th>
                                <th class='px-4 py-3 text-left'>Taak Ref.</th>
                                <th class='px-4 py-3 text-left'>Taak Type</th>
                                <th class='px-4 py-3 text-left'>Klantnaam</th>
                                <th class='px-4 py-3 text-left'>Locatie</th>
                                <th class='px-4 py-3 text-left'>Postcode</th>
                                <th class='px-4 py-3 text-left'>Grid Ref.</th>
                                <th class='px-4 py-3 text-left'>Lat.</th>
                                <th class='px-4 py-3 text-left'>Lng.</th>
                                <th class='px-4 py-3 text-left'>Start</th>
                                <th class='px-4 py-3 text-left'>Eind</th>
                                <th class='px-4 py-3 text-center'>Tot. Min.</th>
                                <th class='px-4 py-3 text-center'>LoS</th>
                                <th class='px-4 py-3 text-center'>Dag/Nacht</th>
                                <th class='px-4 py-3 text-left'>Rol</th>
                                <th class='px-4 py-3 text-left'>Bron</th>
                                <th class='px-4 py-3 text-left'>Notities</th>
                                <th class='px-4 py-3 text-left'>Acties</th>
                            </tr>
                        </thead>
                        <tbody class='divide-y divide-gray-200 text-sm'>";

foreach ($flightLogs as $log) {
    $bodyContent .= "
                            <tr class='hover:bg-gray-50 transition'>
                                <td class='px-4 py-3 whitespace-nowrap'>" . htmlspecialchars($log['Piloot'] ?? 'N/A') . "</td>
                                <td class='px-4 py-3 whitespace-nowrap'>" . htmlspecialchars($log['Drone'] ?? 'N/A') . "</td>
                                <td class='px-4 py-3 whitespace-nowrap'>" . htmlspecialchars($log['DroneFabrikant'] ?? 'N/A') . "</td>
                                <td class='px-4 py-3 whitespace-nowrap'>" . htmlspecialchars($log['DroneModel'] ?? 'N/A') . "</td>
                                <td class='px-4 py-3 whitespace-nowrap'>" . htmlspecialchars($log['DroneSerienummer'] ?? 'N/A') . "</td>
                                <td class='px-4 py-3 whitespace-nowrap'>" . htmlspecialchars($log['Batterij'] ?? 'N/A') . "</td>
                                <td class='px-4 py-3 whitespace-nowrap'>" . htmlspecialchars($log['TaakReferentie'] ?? 'N/A') . "</td>
                                <td class='px-4 py-3 whitespace-nowrap'>" . htmlspecialchars($log['TaakType'] ?? 'N/A') . "</td>
                                <td class='px-4 py-3 whitespace-nowrap'>" . htmlspecialchars($log['KlantNaam'] ?? 'N/A') . "</td>
                                <td class='px-4 py-3'>" . htmlspecialchars($log['Locatie'] ?? 'N/A') . "</td> <!-- No whitespace-nowrap for longer text -->
                                <td class='px-4 py-3 whitespace-nowrap'>" . htmlspecialchars($log['Postcode'] ?? 'N/A') . "</td>
                                <td class='px-4 py-3 whitespace-nowrap'>" . htmlspecialchars($log['GridReferentie'] ?? 'N/A') . "</td>
                                <td class='px-4 py-3 whitespace-nowrap'>" . htmlspecialchars($log['Latitude'] ?? 'N/A') . "</td>
                                <td class='px-4 py-3 whitespace-nowrap'>" . htmlspecialchars($log['Longitude'] ?? 'N/A') . "</td>
                                <td class='px-4 py-3 whitespace-nowrap'>" . htmlspecialchars($log['StartTijd'] ?? 'N/A') . "</td>
                                <td class='px-4 py-3 whitespace-nowrap'>" . htmlspecialchars($log['EindTijd'] ?? 'N/A') . "</td>
                                <td class='px-4 py-3 whitespace-nowrap text-center'>" . htmlspecialchars($log['TotaalMinuten'] ?? 'N/A') . "</td>
                                <td class='px-4 py-3 whitespace-nowrap text-center'>" . htmlspecialchars($log['LoS'] ?? 'N/A') . "</td>
                                <td class='px-4 py-3 whitespace-nowrap text-center'>" . htmlspecialchars($log['DagNacht'] ?? 'N/A') . "</td>
                                <td class='px-4 py-3 whitespace-nowrap'>" . htmlspecialchars($log['RolPiloot'] ?? 'N/A') . "</td>
                                <td class='px-4 py-3 whitespace-nowrap'>" . htmlspecialchars($log['BronLog'] ?? 'N/A') . "</td>
                                <td class='px-4 py-3'>" . htmlspecialchars($log['Notities'] ?? 'N/A') . "</td>
                                <td class='px-4 py-3 whitespace-nowrap'>
                                    <a href='#' class='text-blue-600 hover:text-blue-800 mr-2' title='Details'><i class='fa-solid fa-eye'></i></a>
                                    <a href='#' class='text-gray-600 hover:text-gray-800' title='Bewerk'><i class='fa-solid fa-pencil'></i></a>
                                </td>
                            </tr>";
}

if (empty($flightLogs)) {
    $bodyContent .= "<tr><td colspan='23' class='text-center text-gray-500 py-10'>Geen vluchtlogs gevonden.</td></tr>";
}

$bodyContent .= "
                        </tbody>
                    </table>
                </div>
                <div class='p-4 border-t border-gray-200 flex justify-between items-center text-sm'>
                    <span>Totale vliegtijd: <strong>" . $totalFlightTimeString . "</strong></span>
                    <!-- Paginering (optioneel) -->
                </div>
            </div>
        </div>
    </div>
";

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../layouts/template.php';
