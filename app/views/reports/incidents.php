<?php
// /var/www/public/frontend/pages/incidents/index.php (of incidents.php)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../functions.php';

// --- DATA SIMULATIE VOOR INCIDENTEN (NEDERLANDS & CORRECTE KOLOMMEN) ---
$incidents = [
    [
        'Id' => 1,
        'DatumAanmaak' => '2024-07-20 15:45',
        'DatumUpdate' => '2024-07-21 09:30',
        'IncidentReferentie' => 'INC-FL2024-003-01',
        'TaakReferentie' => 'FL2024-003',
        'GebeurdOp' => '2024-07-20 15:38',
        'GemeldOp' => '2024-07-20 16:00',
        'BetrokkenPersoon' => 'N.v.t.', // Kan ook piloot zijn
        'Drone' => 'DJI Mavic 3 Thermal (M3T-002)',
        'Melder' => 'Jan de Vries',
        'EmailMelder' => 'jan.devries@example.com',
        'EmailBetrokkenPersoon' => 'N.v.t.',
        'KlasseType' => 'Technisch - Communicatie', // Bv. Technisch, Operationeel, Omgeving
        'Details' => 'Kortstondig verlies van C2 link tijdens inspectievlucht. Drone initieerde RTH protocol zoals verwacht.',
        'LetselSchade' => 'Geen letsel of schade aan drone of objecten.',
        'GenomenActie' => 'Vlucht beëindigd na RTH. C2 link gecontroleerd en gereset. Telemetrie data geanalyseerd, geen duidelijke oorzaak gevonden voor signaalverlies. Vluchtgebied gecontroleerd op mogelijke interferentiebronnen.',
        'Notities' => 'Aanbevolen: extra check op C2 apparatuur voor volgende vlucht. Overweeg andere frequentieband indien mogelijk in gebied.',
        'ECCAIRSRef' => 'ECRS-2024-XYZ123' // Indien van toepassing
    ],
    [
        'Id' => 2,
        'DatumAanmaak' => '2024-07-15 10:25',
        'DatumUpdate' => '2024-07-15 11:00',
        'IncidentReferentie' => 'INC-FL2024-001-01',
        'TaakReferentie' => 'FL2024-001',
        'GebeurdOp' => '2024-07-15 10:22',
        'GemeldOp' => '2024-07-15 10:23',
        'BetrokkenPersoon' => 'N.v.t.',
        'Drone' => 'DJI Matrice 300 RTK (M300-001)',
        'Melder' => 'Jan de Vries',
        'EmailMelder' => 'jan.devries@example.com',
        'EmailBetrokkenPersoon' => 'N.v.t.',
        'KlasseType' => 'Omgeving - Weer',
        'Details' => 'Onverwachte sterke windvlaag zorgde voor tijdelijke instabiliteit van de drone. Piloot kon controle behouden.',
        'LetselSchade' => 'Geen.',
        'GenomenActie' => 'Vlucht voortgezet na stabilisatie, windsnelheid continu gemonitord.',
        'Notities' => 'Wind was voorspeld binnen marges, maar lokale vlaag was sterker.',
        'ECCAIRSRef' => ''
    ],
];
// --- EINDE DATA SIMULATIE ---

$showHeader = 1;
$userName = $_SESSION['user']['first_name'] ?? 'Onbekend';
$headTitle = "Incidenten Log";
$gobackUrl = 0;
$rightAttributes = 0;

$bodyContent = "
    <div class='h-full bg-gray-100 shadow-md rounded-tl-xl w-full flex flex-col'>
        <div class='p-6 bg-white flex justify-between items-center border-b border-gray-200 flex-shrink-0'>
            <div class='flex space-x-6 text-sm font-medium'>
                <a href='flight-logs.php' class='text-gray-600 hover:text-gray-900'>Vlucht Logs</a>
                <a href='incidents.php' class='text-gray-900 border-b-2 border-black pb-2'>Incidenten</a>
            </div>
            <button class='bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors text-sm'>
                 <i class='fa-solid fa-triangle-exclamation mr-2'></i>Nieuw Incident Melden
            </button>
        </div>

        <div class='p-6 overflow-y-auto flex-grow'>
            <div class='bg-white rounded-lg shadow overflow-hidden'>
                <div class='p-6 border-b border-gray-200 flex justify-between items-center'>
                    <h2 class='text-xl font-semibold text-gray-800'>Overzicht Incidenten</h2>
                     <div>
                        <input type='text' placeholder='Zoek incident...' class='px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-blue-500'>
                    </div>
                </div>
                <div class='overflow-x-auto'>
                    <table class='w-full'>
                        <thead class='bg-gray-50 text-xs uppercase text-gray-700'>
                            <tr>
                                <th class='px-4 py-3 text-left'>ID</th>
                                <th class='px-4 py-3 text-left'>Incident Ref.</th>
                                <th class='px-4 py-3 text-left'>Vlucht Ref.</th>
                                <th class='px-4 py-3 text-left'>Datum Gebeurtenis</th>
                                <th class='px-4 py-3 text-left'>Type/Klasse</th>
                                <th class='px-4 py-3 text-left'>Ernst</th>
                                <th class='px-4 py-3 text-left'>Details (kort)</th>
                                <th class='px-4 py-3 text-left'>Gemeld Door</th>
                                <th class='px-4 py-3 text-center'>Status</th>
                                <th class='px-4 py-3 text-left'>Acties</th>
                            </tr>
                        </thead>
                        <tbody class='divide-y divide-gray-200 text-sm'>";

foreach ($incidents as $incident) {
    $severityClass = match (strtolower($incident['Ernst'] ?? 'onbekend')) {
        'minor' => 'bg-yellow-100 text-yellow-800',
        'moderate' => 'bg-orange-100 text-orange-800',
        'major' => 'bg-red-100 text-red-700',
        'critical' => 'bg-red-200 text-red-900 font-bold',
        default => 'bg-gray-100 text-gray-800'
    };
    $statusClass = match (strtolower($incident['Status'] ?? 'onbekend')) {
        'afgehandeld' => 'bg-green-100 text-green-800',
        'open' => 'bg-blue-100 text-blue-800',
        'in onderzoek' => 'bg-purple-100 text-purple-800',
        default => 'bg-gray-100 text-gray-800'
    };
    $shortDetails = strlen($incident['Details'] ?? '') > 50 ? substr($incident['Details'] ?? '', 0, 50) . "..." : ($incident['Details'] ?? 'N/A');


    $bodyContent .= "
                            <tr class='hover:bg-gray-50 transition'>
                                <td class='px-4 py-3 whitespace-nowrap'>" . htmlspecialchars($incident['Id'] ?? 'N/A') . "</td>
                                <td class='px-4 py-3 whitespace-nowrap font-medium'>" . htmlspecialchars($incident['IncidentReferentie'] ?? 'N/A') . "</td>
                                <td class='px-4 py-3 whitespace-nowrap text-blue-600 hover:underline'><a href='flight-logs.php?flight_id=" . htmlspecialchars($incident['TaakReferentie'] ?? '') . "'>" . htmlspecialchars($incident['TaakReferentie'] ?? 'N/A') . "</a></td>
                                <td class='px-4 py-3 whitespace-nowrap'>" . htmlspecialchars($incident['GebeurdOp'] ?? 'N/A') . "</td>
                                <td class='px-4 py-3 whitespace-nowrap'>" . htmlspecialchars($incident['KlasseType'] ?? 'N/A') . "</td>
                                <td class='px-4 py-3 whitespace-nowrap text-center'>
                                    <span class='{$severityClass} px-3 py-1 rounded-full text-xs font-medium'>" . htmlspecialchars($incident['Ernst'] ?? 'Onbekend') . "</span>
                                </td>
                                <td class='px-4 py-3' title='" . htmlspecialchars($incident['Details'] ?? '') . "'>" . htmlspecialchars($shortDetails) . "</td>
                                <td class='px-4 py-3 whitespace-nowrap'>" . htmlspecialchars($incident['Melder'] ?? 'N/A') . "</td>
                                <td class='px-4 py-3 whitespace-nowrap text-center'>
                                     <span class='{$statusClass} px-3 py-1 rounded-full text-xs font-medium'>" . htmlspecialchars($incident['Status'] ?? 'Onbekend') . "</span>
                                </td>
                                <td class='px-4 py-3 whitespace-nowrap'>
                                    <a href='#' class='text-blue-600 hover:text-blue-800 mr-2' title='Details'><i class='fa-solid fa-eye'></i></a>
                                    <a href='#' class='text-gray-600 hover:text-gray-800' title='Bewerk'><i class='fa-solid fa-pencil'></i></a>
                                </td>
                            </tr>";
}

if (empty($incidents)) {
    $bodyContent .= "<tr><td colspan='10' class='text-center text-gray-500 py-10'>Geen incidenten gemeld.</td></tr>";
}

$bodyContent .= "
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
";

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../layouts/template.php';
