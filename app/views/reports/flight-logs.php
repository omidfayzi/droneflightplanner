<?php
// /var/www/public/frontend/pages/flight-logs/index.php (of flight-logs.php)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../functions.php';

// --- DATA SIMULATIE VOOR VLUCHTLOGS (onveranderd van vorige versie) ---
$flightLogs = [
    [
        'DFPPLF_Id' => 'FL2024-001',
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
        'StartTijd' => '2024-07-25T10:15',
        'EindTijd' => '2024-07-25T10:55',
        'TotaalMinuten' => 40,
        'LoS' => 'VLOS',
        'DagNacht' => 'Dag',
        'RolPiloot' => 'PIC',
        'BronLog' => 'Automatisch (DJI Sync)',
        'Notities' => 'Sterke zijwind, extra voorzichtig geland.'
    ],
    [
        'DFPPLF_Id' => 'FL2024-002',
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
        'StartTijd' => '2024-07-26T09:00',
        'EindTijd' => '2024-07-26T09:35',
        'TotaalMinuten' => 35,
        'LoS' => 'EVLOS',
        'DagNacht' => 'Dag',
        'RolPiloot' => 'PIC',
        'BronLog' => 'Handmatig Ingevuld',
        'Notities' => 'Uitstekende zichtbaarheid.'
    ],
];
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
    <style>
        /* ... (Kopieer de modal CSS van je vorige drones.php/flight-logs.php hier) ... */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.6); display: none; align-items: center; justify-content: center; z-index: 1050; opacity: 0; transition: opacity 0.3s ease-in-out; }
        .modal-overlay.active { display: flex; opacity: 1; }
        .modal-content { background-color: white; padding: 2rem; border-radius: 0.5rem; width: 90%; max-width: 800px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04); position: relative; transform: translateY(-20px) scale(0.95); opacity: 0; transition: transform 0.3s ease-out, opacity 0.3s ease-out; max-height: 90vh; overflow-y: auto; }
        .modal-overlay.active .modal-content { transform: translateY(0) scale(1); opacity: 1; }
        .modal-close-btn { position: absolute; top: 1rem; right: 1rem; font-size: 1.5rem; color: #9ca3af; background: none; border: none; cursor: pointer; padding: 0.25rem; line-height: 1; }
        .modal-close-btn:hover { color: #6b7280; }
        .modal-content h3 { font-size: 1.25rem; font-weight: 600; margin-bottom: 1.5rem; color: #1f2937; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; }
        .form-group { margin-bottom: 1rem; }
    </style>

    <div class='h-full bg-gray-100 shadow-md rounded-tl-xl w-full flex flex-col'>
        <div class='p-6 bg-white flex justify-between items-center border-b border-gray-200 flex-shrink-0'>
            <div class='flex space-x-6 text-sm font-medium'>
                <a href='flight-logs.php' class='text-gray-900 border-b-2 border-black pb-2'>Vlucht Logs</a>
                <a href='incidents.php' class='text-gray-600 hover:text-gray-900'>Incidenten</a>
            </div>
            <button onclick='openReportFlightModal()' class='bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors text-sm flex items-center'>
                <i class='fa-solid fa-file-medical-alt mr-2'></i>+ Vlucht Rapporteren
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
                                <th class='px-4 py-3 text-left'>Fabrikant</th>
                                <th class='px-4 py-3 text-left'>Model</th>
                                <th class='px-4 py-3 text-left'>S/N</th>
                                <th class='px-4 py-3 text-left'>Batterij</th>
                                <th class='px-4 py-3 text-left'>Taak Ref.</th>
                                <th class='px-4 py-3 text-left'>Taak Type</th>
                                <th class='px-4 py-3 text-left'>Klant</th>
                                <th class='px-4 py-3 text-left'>Locatie</th>
                                <th class='px-4 py-3 text-left'>Postcode</th>
                                <th class='px-4 py-3 text-left'>Grid Ref.</th>
                                <th class='px-4 py-3 text-left'>Lat.</th>
                                <th class='px-4 py-3 text-left'>Lng.</th>
                                <th class='px-4 py-3 text-left'>Start</th>
                                <th class='px-4 py-3 text-left'>Eind</th>
                                <th class='px-4 py-3 text-center'>Duur</th>
                                <th class='px-4 py-3 text-center'>LoS</th>
                                <th class='px-4 py-3 text-center'>Dag/Nacht</th>
                                <th class='px-4 py-3 text-left'>Rol</th>
                                <th class='px-4 py-3 text-left'>Bron</th>
                                <th class='px-4 py-3 text-left'>Notities</th>
                                <th class='px-4 py-3 text-left'>Acties</th>
                            </tr>
                        </thead>
                        <tbody class='divide-y divide-gray-200 text-sm'>";

if (!empty($flightLogs) && is_array($flightLogs)) {
    foreach ($flightLogs as $log) {
        $logId = htmlspecialchars($log['DFPPLF_Id'] ?? $log['TaakReferentie'] ?? '', ENT_QUOTES, 'UTF-8');
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
                                <td class='px-4 py-3'>" . htmlspecialchars($log['Locatie'] ?? 'N/A') . "</td>
                                <td class='px-4 py-3 whitespace-nowrap'>" . htmlspecialchars($log['Postcode'] ?? 'N/A') . "</td>
                                <td class='px-4 py-3 whitespace-nowrap'>" . htmlspecialchars($log['GridReferentie'] ?? 'N/A') . "</td>
                                <td class='px-4 py-3 whitespace-nowrap'>" . htmlspecialchars($log['Latitude'] ?? 'N/A') . "</td>
                                <td class='px-4 py-3 whitespace-nowrap'>" . htmlspecialchars($log['Longitude'] ?? 'N/A') . "</td>
                                <td class='px-4 py-3 whitespace-nowrap'>" . htmlspecialchars(str_replace('T', ' ', $log['StartTijd'] ?? 'N/A')) . "</td>
                                <td class='px-4 py-3 whitespace-nowrap'>" . htmlspecialchars(str_replace('T', ' ', $log['EindTijd'] ?? 'N/A')) . "</td>
                                <td class='px-4 py-3 whitespace-nowrap text-center'>" . htmlspecialchars($log['TotaalMinuten'] ?? 'N/A') . "</td>
                                <td class='px-4 py-3 whitespace-nowrap text-center'>" . htmlspecialchars($log['LoS'] ?? 'N/A') . "</td>
                                <td class='px-4 py-3 whitespace-nowrap text-center'>" . htmlspecialchars($log['DagNacht'] ?? 'N/A') . "</td>
                                <td class='px-4 py-3 whitespace-nowrap'>" . htmlspecialchars($log['RolPiloot'] ?? 'N/A') . "</td>
                                <td class='px-4 py-3 whitespace-nowrap'>" . htmlspecialchars($log['BronLog'] ?? 'N/A') . "</td>
                                <td class='px-4 py-3' title='" . htmlspecialchars($log['Notities'] ?? '') . "'>" . htmlspecialchars(substr($log['Notities'] ?? '', 0, 30) . (strlen($log['Notities'] ?? '') > 30 ? '...' : '')) . "</td>
                                <td class='px-4 py-3 whitespace-nowrap'>
                                    <button onclick='viewFlightLogDetails(\"" . $logId . "\")' class='text-blue-600 hover:text-blue-800 mr-2' title='Details'><i class='fa-solid fa-eye'></i></button>
                                    <button onclick='openEditFlightLogModal(\"" . $logId . "\")' class='text-gray-600 hover:text-gray-800' title='Bewerk'><i class='fa-solid fa-pencil'></i></button>
                                </td>
                            </tr>";
    }
} else {
    $bodyContent .= "<tr><td colspan='23' class='text-center text-gray-500 py-10'>Geen vluchtlogs gevonden.</td></tr>";
}
$bodyContent .= "
                        </tbody>
                    </table>
                </div>
                <div class='p-4 border-t border-gray-200 flex justify-between items-center text-sm'>
                    <span>Totale vliegtijd: <strong>" . $totalFlightTimeString . "</strong></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal voor Nieuwe Vlucht Rapporteren -->
    <div id='reportFlightModal' class='modal-overlay'>
        <div class='modal-content'>
            <button class='modal-close-btn' onclick='closeReportFlightModal()'>×</button>
            <h3>Nieuwe Vlucht Rapporteren (Handmatig Loggen)</h3>
            <form id='reportFlightForm' action='save_flight_log.php' method='POST'>
                <div class='form-grid'>
                    <div class='form-group'><label for='report_pilot'>Piloot <span class='text-red-500'>*</span></label><input type='text' name='report_pilot' id='report_pilot' class='mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm' required></div>
                    <div class='form-group'><label for='report_drone'>Drone (Model & S/N) <span class='text-red-500'>*</span></label><input type='text' name='report_drone' id='report_drone' class='mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm' required></div>
                    <div class='form-group'><label for='report_battery'>Gebruikte Batterij(en)</label><input type='text' name='report_battery' id='report_battery' class='mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm'></div>
                    <div class='form-group'><label for='report_job_ref'>Taak Referentie</label><input type='text' name='report_job_ref' id='report_job_ref' class='mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm'></div>
                    <div class='form-group'><label for='report_job_type'>Taak Type</label><input type='text' name='report_job_type' id='report_job_type' class='mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm'></div>
                    <div class='form-group'><label for='report_client_name'>Klantnaam</label><input type='text' name='report_client_name' id='report_client_name' class='mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm'></div>
                    <div class='form-group md:col-span-2'><label for='report_location'>Locatie <span class='text-red-500'>*</span></label><input type='text' name='report_location' id='report_location' class='mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm' required></div>
                    <div class='form-group'><label for='report_start_time'>Start Datum & Tijd <span class='text-red-500'>*</span></label><input type='datetime-local' name='report_start_time' id='report_start_time' class='mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm' required></div>
                    <div class='form-group'><label for='report_end_time'>Eind Datum & Tijd <span class='text-red-500'>*</span></label><input type='datetime-local' name='report_end_time' id='report_end_time' class='mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm' required></div>
                    <div class='form-group'><label for='report_total_minutes'>Totale Minuten <span class='text-red-500'>*</span></label><input type='number' name='report_total_minutes' id='report_total_minutes' class='mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm' required min='1'></div>
                    <div class='form-group'><label for='report_los'>Line of Sight (LoS) <span class='text-red-500'>*</span></label><select name='report_los' id='report_los' class='mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm' required><option value='VLOS'>VLOS</option><option value='EVLOS'>EVLOS</option><option value='BVLOS'>BVLOS</option></select></div>
                    <div class='form-group'><label for='report_day_night'>Dag/Nacht <span class='text-red-500'>*</span></label><select name='report_day_night' id='report_day_night' class='mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm' required><option value='Dag'>Dag</option><option value='Nacht'>Nacht</option></select></div>
                    <div class='form-group'><label for='report_pilot_role'>Rol Piloot <span class='text-red-500'>*</span></label><select name='report_pilot_role' id='report_pilot_role' class='mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm' required><option value='PIC'>PIC</option><option value='RP'>Remote Pilot</option><option value='Observer'>Observer (indien van toepassing)</option></select></div>
                    <div class='form-group'><label for='report_log_source'>Bron van Log <span class='text-red-500'>*</span></label><select name='report_log_source' id='report_log_source' class='mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm' required><option value='Handmatig Ingevuld'>Handmatig Ingevuld</option><option value='DJI Sync'>DJI Sync</option><option value='DroneLogbook Sync'>DroneLogbook Sync</option><option value='Anders'>Anders</option></select></div>
                    <div class='form-group md:col-span-2'><label for='report_notes'>Notities</label><textarea name='report_notes' id='report_notes' rows='3' class='mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm'></textarea></div>
                </div>
                <div class='mt-6 flex justify-end space-x-3'>
                    <button type='button' onclick='closeReportFlightModal()' class='bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 text-sm'>Annuleren</button>
                    <button type='submit' class='bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm flex items-center'><i class='fa-solid fa-plus-circle mr-2'></i>Log Rapporteren</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal voor Bewerk Vluchtlog (onveranderd van vorige versie) -->
    <div id='editFlightLogModal' class='modal-overlay'>
        <div class='modal-content'>
            <button class='modal-close-btn' onclick='closeEditFlightLogModal()'>×</button>
            <h3>Vluchtlog Bewerken</h3>
            <form id='editFlightLogForm' action='update_flight_log.php' method='POST'>
                <input type='hidden' name='edit_flight_log_id' id='edit_flight_log_id'>
                <div class='form-grid'>
                    <div class='form-group'><label for='edit_flight_pilot'>Piloot</label><input type='text' name='edit_flight_pilot' id='edit_flight_pilot' class='mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm'></div>
                    <div class='form-group'><label for='edit_flight_drone'>Drone</label><input type='text' name='edit_flight_drone' id='edit_flight_drone' class='mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm'></div>
                    <div class='form-group'><label for='edit_flight_start_time'>Starttijd</label><input type='datetime-local' name='edit_flight_start_time' id='edit_flight_start_time' class='mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm'></div>
                    <div class='form-group'><label for='edit_flight_end_time'>Eindtijd</label><input type='datetime-local' name='edit_flight_end_time' id='edit_flight_end_time' class='mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm'></div>
                    <div class='form-group md:col-span-2'><label for='edit_flight_notes'>Notities</label><textarea name='edit_flight_notes' id='edit_flight_notes' rows='3' class='mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm'></textarea></div>
                    <!-- Voeg hier meer bewerkbare velden toe indien nodig -->
                </div>
                <div class='mt-6 flex justify-end space-x-3'>
                    <button type='button' onclick='closeEditFlightLogModal()' class='bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 text-sm'>Annuleren</button>
                    <button type='submit' class='bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm flex items-center'><i class='fa-solid fa-save mr-2'></i>Wijzigingen Opslaan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const editFlightLogModal = document.getElementById('editFlightLogModal');
        const reportFlightModal = document.getElementById('reportFlightModal');
        const flightLogsData = " . json_encode($flightLogs) . ";

        function openReportFlightModal() {
            if (reportFlightModal) reportFlightModal.classList.add('active');
        }
        function closeReportFlightModal() {
            if (reportFlightModal) {
                 reportFlightModal.classList.remove('active');
                 document.getElementById('reportFlightForm')?.reset();
            }
        }
        if (reportFlightModal) {
            reportFlightModal.addEventListener('click', function(event) {
                if (event.target === reportFlightModal) closeReportFlightModal();
            });
        }

        function openEditFlightLogModal(flightLogId) {
            const logEntry = flightLogsData.find(log => log.DFPPLF_Id === flightLogId || log.TaakReferentie === flightLogId);
            if (logEntry && editFlightLogModal) {
                document.getElementById('edit_flight_log_id').value = logEntry.DFPPLF_Id || logEntry.TaakReferentie;
                document.getElementById('edit_flight_pilot').value = logEntry.Piloot || '';
                document.getElementById('edit_flight_drone').value = logEntry.Drone || '';
                document.getElementById('edit_flight_start_time').value = logEntry.StartTijd ? logEntry.StartTijd.replace(' ', 'T') : '';
                document.getElementById('edit_flight_end_time').value = logEntry.EindTijd ? logEntry.EindTijd.replace(' ', 'T') : '';
                document.getElementById('edit_flight_notes').value = logEntry.Notities || '';
                // Vul hier ALLE andere velden uit je $flightLogs array die je wilt bewerken
                // Voorbeeld: document.getElementById('edit_drone_manufacturer').value = logEntry.DroneFabrikant || '';
                // Zorg dat de IDs van de formuliervelden overeenkomen.
                editFlightLogModal.classList.add('active');
            } else {
                alert('Vluchtlog niet gevonden voor bewerken.');
            }
        }
        function closeEditFlightLogModal() {
            if (editFlightLogModal) {
                 editFlightLogModal.classList.remove('active');
                 document.getElementById('editFlightLogForm')?.reset();
            }
        }
        function viewFlightLogDetails(flightLogId){
             // Hier zou je naar een detailpagina kunnen navigeren of een uitgebreidere detail modal openen
             window.location.href = 'flight-report.php?flight_id=' + encodeURIComponent(flightLogId);
        }
        if (editFlightLogModal) {
            editFlightLogModal.addEventListener('click', function(event) {
                if (event.target === editFlightLogModal) closeEditFlightLogModal();
            });
        }
    </script>
";

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../layouts/template.php';
