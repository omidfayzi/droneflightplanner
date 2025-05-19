<?php
// /var/www/public/frontend/pages/incidents/index.php (of incidents.php)
// (Begin van het bestand, inclusief session_start, require_once, data simulatie, $headTitle etc. zoals in het vorige antwoord)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../functions.php';

// --- DATA SIMULATIE VOOR INCIDENTEN ---
$incidents = [
    [
        'Id' => 1,
        'IncidentReferentie' => 'INC-FL2024-003-01',
        'TaakReferentie' => 'FL2024-003',
        'GebeurdOpDatum' => '2024-07-20',
        'GebeurdOpTijd' => '15:38',
        'KlasseType' => 'Technisch - Communicatie',
        'Ernst' => 'Minor',
        'Details' => 'Kortstondig verlies van C2 link tijdens inspectievlucht. Drone initieerde RTH protocol zoals verwacht.',
        'Melder' => 'Jan de Vries',
        'Status' => 'Afgehandeld',
        'GenomenActie' => 'Vlucht beëindigd na RTH. C2 link gecontroleerd en gereset. Telemetrie data geanalyseerd, geen duidelijke oorzaak gevonden voor signaalverlies. Vluchtgebied gecontroleerd op mogelijke interferentiebronnen.'
    ],
    [
        'Id' => 2,
        'IncidentReferentie' => 'INC-FL2024-001-01',
        'TaakReferentie' => 'FL2024-001',
        'GebeurdOpDatum' => '2024-07-15',
        'GebeurdOpTijd' => '10:22',
        'KlasseType' => 'Omgeving - Weer',
        'Ernst' => 'Moderate',
        'Details' => 'Onverwachte sterke windvlaag zorgde voor tijdelijke instabiliteit van de drone. Piloot kon controle behouden.',
        'Melder' => 'Jan de Vries',
        'Status' => 'Afgehandeld',
        'GenomenActie' => 'Vlucht voortgezet na stabilisatie, windsnelheid continu gemonitord.'
    ],
    [
        'Id' => 3,
        'IncidentReferentie' => 'INC-FL2024-00X-01',
        'TaakReferentie' => 'Nog te bepalen',
        'GebeurdOpDatum' => '2024-07-28',
        'GebeurdOpTijd' => '10:50',
        'KlasseType' => 'Operationeel - Procedurefout',
        'Ernst' => 'Major',
        'Details' => 'Pre-flight checklist item overgeslagen, leidde tot onjuiste camera instelling.',
        'Melder' => 'Amina El Amrani',
        'Status' => 'Open',
        'GenomenActie' => 'Vlucht direct afgebroken na constatering. Procedure review gepland.'
    ],
];
// --- EINDE DATA SIMULATIE ---

$showHeader = 1;
$userName = $_SESSION['user']['first_name'] ?? 'Onbekend';
$headTitle = "Incidenten Log";
$gobackUrl = 0;
$rightAttributes = 0;

$bodyContent = "
    <style>
        /* ... (Kopieer de modal CSS van je vorige drones.php/flight-logs.php hier) ... */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.6); display: none; align-items: center; justify-content: center; z-index: 1050; opacity: 0; transition: opacity 0.3s ease-in-out; }
        .modal-overlay.active { display: flex; opacity: 1; }
        .modal-content { background-color: white; padding: 2rem; border-radius: 0.5rem; width: 90%; max-width: 700px; /* Iets breder voor incident details */ box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04); position: relative; transform: translateY(-20px) scale(0.95); opacity: 0; transition: transform 0.3s ease-out, opacity 0.3s ease-out; max-height: 90vh; overflow-y: auto; }
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
                <a href='flight-logs.php' class='text-gray-600 hover:text-gray-900'>Vlucht Logs</a>
                <a href='incidents.php' class='text-gray-900 border-b-2 border-black pb-2'>Incidenten</a>
            </div>
            <button onclick='openAddIncidentModal()' class='bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors text-sm flex items-center'>
                 <i class='fa-solid fa-triangle-exclamation mr-2'></i>Nieuw Incident Melden
            </button>
        </div>

        <div class='p-6 overflow-y-auto flex-grow'>
            <div class='bg-white rounded-lg shadow overflow-hidden'>
                <div class='p-6 border-b border-gray-200 flex justify-between items-center'>
                    <h2 class='text-xl font-semibold text-gray-800'>Overzicht Incidenten</h2>
                     <div>
                        <input type='text' placeholder='Zoek incident (ID, Vlucht ID, Type)...' class='px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 w-64'>
                    </div>
                </div>
                <div class='overflow-x-auto'>
                    <table class='w-full'>
                        <thead class='bg-gray-50 text-xs uppercase text-gray-700'>
                            <tr>
                                <th class='px-4 py-3 text-left'>Incident ID</th>
                                <th class='px-4 py-3 text-left'>Vlucht ID</th>
                                <th class='px-4 py-3 text-left'>Datum Incident</th>
                                <th class='px-4 py-3 text-left'>Type/Klasse</th>
                                <th class='px-4 py-3 text-center'>Ernst</th>
                                <th class='px-4 py-3 text-left'>Details (kort)</th>
                                <th class='px-4 py-3 text-left'>Gemeld Door</th>
                                <th class='px-4 py-3 text-center'>Status</th>
                                <th class='px-4 py-3 text-left'>Acties</th>
                            </tr>
                        </thead>
                        <tbody class='divide-y divide-gray-200 text-sm'>";

if (!empty($incidents) && is_array($incidents)) {
    foreach ($incidents as $incident) {
        $incidentId = htmlspecialchars($incident['Id'] ?? '', ENT_QUOTES, 'UTF-8'); // Zorg voor unieke ID voor JS
        $severityClass = match (strtolower($incident['Ernst'] ?? 'onbekend')) {
            'minor' => 'bg-yellow-100 text-yellow-800',
            'moderate' => 'bg-orange-100 text-orange-800', // Definieer Tailwind oranje indien nodig
            'major' => 'bg-red-100 text-red-700',
            'critical' => 'bg-red-200 text-red-900 font-bold',
            default => 'bg-gray-100 text-gray-800'
        };
        $statusClass = match (strtolower($incident['Status'] ?? 'onbekend')) {
            'afgehandeld' => 'bg-green-100 text-green-800',
            'open' => 'bg-blue-100 text-blue-800',
            'in onderzoek' => 'bg-purple-100 text-purple-800', // Definieer Tailwind paars
            default => 'bg-gray-100 text-gray-800'
        };
        $shortDetails = strlen($incident['Details'] ?? '') > 40 ? substr($incident['Details'] ?? '', 0, 40) . "..." : ($incident['Details'] ?? 'N/A');

        $bodyContent .= "
                            <tr class='hover:bg-gray-50 transition'>
                                <td class='px-4 py-3 whitespace-nowrap'>" . htmlspecialchars($incident['IncidentReferentie'] ?? 'N/A') . "</td>
                                <td class='px-4 py-3 whitespace-nowrap text-blue-600 hover:underline'><a href='flight-logs.php?flight_id_ref=" . htmlspecialchars($incident['TaakReferentie'] ?? '') . "'>" . htmlspecialchars($incident['TaakReferentie'] ?? 'N/A') . "</a></td>
                                <td class='px-4 py-3 whitespace-nowrap'>" . htmlspecialchars($incident['GebeurdOpDatum'] ?? 'N/A') . " " . htmlspecialchars($incident['GebeurdOpTijd'] ?? '') . "</td>
                                <td class='px-4 py-3 whitespace-nowrap'>" . htmlspecialchars($incident['KlasseType'] ?? 'N/A') . "</td>
                                <td class='px-4 py-3 whitespace-nowrap text-center'>
                                    <span class='{$severityClass} px-3 py-1 rounded-full text-xs font-semibold'>" . htmlspecialchars($incident['Ernst'] ?? 'Onbekend') . "</span>
                                </td>
                                <td class='px-4 py-3' title='" . htmlspecialchars($incident['Details'] ?? '') . "'>" . htmlspecialchars($shortDetails) . "</td>
                                <td class='px-4 py-3 whitespace-nowrap'>" . htmlspecialchars($incident['Melder'] ?? 'N/A') . "</td>
                                <td class='px-4 py-3 whitespace-nowrap text-center'>
                                     <span class='{$statusClass} px-3 py-1 rounded-full text-xs font-semibold'>" . htmlspecialchars($incident['Status'] ?? 'Onbekend') . "</span>
                                </td>
                                <td class='px-4 py-3 whitespace-nowrap'>
                                    <button onclick='viewIncidentDetails(\"" . $incidentId . "\")' class='text-blue-600 hover:text-blue-800 mr-2' title='Details'><i class='fa-solid fa-eye'></i></button>
                                    <button onclick='openEditIncidentModal(\"" . $incidentId . "\")' class='text-gray-600 hover:text-gray-800' title='Bewerk'><i class='fa-solid fa-pencil'></i></button>
                                </td>
                            </tr>";
    }
} else {
    $bodyContent .= "<tr><td colspan='9' class='text-center text-gray-500 py-10'>Geen incidenten gemeld.</td></tr>";
}
$bodyContent .= "
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal voor Nieuw Incident Melden -->
    <div id='addIncidentModal' class='modal-overlay'>
        <div class='modal-content'>
            <button class='modal-close-btn' onclick='closeAddIncidentModal()'>×</button>
            <h3>Nieuw Incident Melden</h3>
            <form id='addIncidentForm' action='save_incident.php' method='POST'>
                <div class='form-grid'>
                    <div class='form-group'>
                        <label for='add_incident_flight_id' class='block text-sm font-medium text-gray-700 mb-1'>Gerelateerde Vlucht ID (Optioneel)</label>
                        <input type='text' name='incident_flight_id' id='add_incident_flight_id' class='mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm' placeholder='bv. FL2024-003'>
                    </div>
                     <div class='form-group'>
                        <label for='add_incident_datetime' class='block text-sm font-medium text-gray-700 mb-1'>Datum & Tijd Incident <span class='text-red-500'>*</span></label>
                        <input type='datetime-local' name='incident_datetime' id='add_incident_datetime' class='mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm' required>
                    </div>
                    <div class='form-group'>
                        <label for='add_incident_type' class='block text-sm font-medium text-gray-700 mb-1'>Type/Klasse Incident <span class='text-red-500'>*</span></label>
                        <select name='incident_type' id='add_incident_type' class='mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm' required>
                            <option value=''>Selecteer type...</option>
                            <option value='Technisch - Motor'>Technisch - Motor</option>
                            <option value='Technisch - Batterij'>Technisch - Batterij</option>
                            <option value='Technisch - Communicatie'>Technisch - Communicatie</option>
                            <option value='Technisch - Software'>Technisch - Software</option>
                            <option value='Operationeel - Procedurefout'>Operationeel - Procedurefout</option>
                            <option value='Operationeel - Pilootfout'>Operationeel - Pilootfout</option>
                            <option value='Omgeving - Weer'>Omgeving - Weer</option>
                            <option value='Omgeving - Obstakel'>Omgeving - Obstakel (bv. vogel)</option>
                            <option value='Omgeving - GPS/Signaal verlies'>Omgeving - GPS/Signaal verlies</option>
                            <option value='Beveiliging - Ongeautoriseerde toegang'>Beveiliging - Ongeautoriseerde toegang</option>
                            <option value='Overig'>Overig</option>
                        </select>
                    </div>
                     <div class='form-group'>
                        <label for='add_incident_severity' class='block text-sm font-medium text-gray-700 mb-1'>Ernst <span class='text-red-500'>*</span></label>
                        <select name='incident_severity' id='add_incident_severity' class='mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm' required>
                            <option value='Minor'>Minor (Geen/minimale impact)</option>
                            <option value='Moderate'>Moderate (Operationele impact, geen schade)</option>
                            <option value='Major'>Major (Significante schade/verstoring)</option>
                            <option value='Critical'>Critical (Ernstig letsel/fatale schade)</option>
                        </select>
                    </div>
                     <div class='form-group md:col-span-2'>
                        <label for='add_incident_details' class='block text-sm font-medium text-gray-700 mb-1'>Details van het Incident <span class='text-red-500'>*</span></label>
                        <textarea name='incident_details' id='add_incident_details' rows='4' class='mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm' required></textarea>
                    </div>
                    <div class='form-group md:col-span-2'>
                        <label for='add_incident_action_taken' class='block text-sm font-medium text-gray-700 mb-1'>Direct Genomen Actie(s) <span class='text-red-500'>*</span></label>
                        <textarea name='incident_action_taken' id='add_incident_action_taken' rows='3' class='mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm' required></textarea>
                    </div>
                     <div class='form-group'>
                        <label for='add_incident_reporter' class='block text-sm font-medium text-gray-700 mb-1'>Gemeld door (Naam) <span class='text-red-500'>*</span></label>
                        <input type='text' name='incident_reporter' id='add_incident_reporter' value='{$userName}' class='mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm bg-gray-50' readonly required>
                    </div>
                    <div class='form-group'>
                        <label for='add_incident_status' class='block text-sm font-medium text-gray-700 mb-1'>Status Melding</label>
                        <select name='incident_status' id='add_incident_status' class='mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm'>
                            <option value='Open' selected>Open</option>
                            <option value='In Onderzoek'>In Onderzoek</option>
                            <option value='Afgehandeld'>Afgehandeld</option>
                        </select>
                    </div>
                </div>
                <div class='mt-6 flex justify-end space-x-3'>
                    <button type='button' onclick='closeAddIncidentModal()' class='bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 text-sm'>Annuleren</button>
                    <button type='submit' class='bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm flex items-center'><i class='fa-solid fa-paper-plane mr-2'></i>Incident Melden</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal voor Bewerk Incident -->
    <div id='editIncidentModal' class='modal-overlay'>
        <div class='modal-content'>
            <button class='modal-close-btn' onclick='closeEditIncidentModal()'>×</button>
            <h3>Incident Bewerken</h3>
            <form id='editIncidentForm' action='update_incident.php' method='POST'>
                <input type='hidden' name='edit_incident_id_db' id='edit_incident_id_db'> <!-- Voor de database ID -->
                <div class='form-grid'>
                    <div class='form-group'>
                        <label for='edit_incident_ref' class='block text-sm font-medium text-gray-700 mb-1'>Incident Referentie</label>
                        <input type='text' name='edit_incident_ref' id='edit_incident_ref' class='mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm bg-gray-50' readonly>
                    </div>
                    <div class='form-group'>
                        <label for='edit_incident_flight_id_ref' class='block text-sm font-medium text-gray-700 mb-1'>Gerelateerde Vlucht ID</label>
                        <input type='text' name='edit_incident_flight_id_ref' id='edit_incident_flight_id_ref' class='mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm'>
                    </div>
                     <div class='form-group'>
                        <label for='edit_incident_datetime' class='block text-sm font-medium text-gray-700 mb-1'>Datum & Tijd Incident <span class='text-red-500'>*</span></label>
                        <input type='datetime-local' name='edit_incident_datetime' id='edit_incident_datetime' class='mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm' required>
                    </div>
                    <div class='form-group'>
                        <label for='edit_incident_type' class='block text-sm font-medium text-gray-700 mb-1'>Type/Klasse Incident <span class='text-red-500'>*</span></label>
                        <select name='edit_incident_type' id='edit_incident_type' class='mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm' required>
                             <!-- Opties zoals in add modal -->
                            <option value='Technisch - Motor'>Technisch - Motor</option>
                            <option value='Technisch - Batterij'>Technisch - Batterij</option>
                            <option value='Technisch - Communicatie'>Technisch - Communicatie</option>
                            <option value='Technisch - Software'>Technisch - Software</option>
                            <option value='Operationeel - Procedurefout'>Operationeel - Procedurefout</option>
                            <option value='Operationeel - Pilootfout'>Operationeel - Pilootfout</option>
                            <option value='Omgeving - Weer'>Omgeving - Weer</option>
                            <option value='Omgeving - Obstakel'>Omgeving - Obstakel (bv. vogel)</option>
                            <option value='Omgeving - GPS/Signaal verlies'>Omgeving - GPS/Signaal verlies</option>
                            <option value='Beveiliging - Ongeautoriseerde toegang'>Beveiliging - Ongeautoriseerde toegang</option>
                            <option value='Overig'>Overig</option>
                        </select>
                    </div>
                     <div class='form-group'>
                        <label for='edit_incident_severity' class='block text-sm font-medium text-gray-700 mb-1'>Ernst <span class='text-red-500'>*</span></label>
                        <select name='edit_incident_severity' id='edit_incident_severity' class='mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm' required>
                            <option value='Minor'>Minor</option>
                            <option value='Moderate'>Moderate</option>
                            <option value='Major'>Major</option>
                            <option value='Critical'>Critical</option>
                        </select>
                    </div>
                     <div class='form-group md:col-span-2'>
                        <label for='edit_incident_details' class='block text-sm font-medium text-gray-700 mb-1'>Details <span class='text-red-500'>*</span></label>
                        <textarea name='edit_incident_details' id='edit_incident_details' rows='4' class='mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm' required></textarea>
                    </div>
                    <div class='form-group md:col-span-2'>
                        <label for='edit_incident_action_taken' class='block text-sm font-medium text-gray-700 mb-1'>Genomen Actie(s) <span class='text-red-500'>*</span></label>
                        <textarea name='edit_incident_action_taken' id='edit_incident_action_taken' rows='3' class='mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm' required></textarea>
                    </div>
                     <div class='form-group'>
                        <label for='edit_incident_reporter' class='block text-sm font-medium text-gray-700 mb-1'>Gemeld door</label>
                        <input type='text' name='edit_incident_reporter' id='edit_incident_reporter' class='mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm bg-gray-50' readonly>
                    </div>
                    <div class='form-group'>
                        <label for='edit_incident_status' class='block text-sm font-medium text-gray-700 mb-1'>Status Melding</label>
                        <select name='edit_incident_status' id='edit_incident_status' class='mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm'>
                            <option value='Open'>Open</option>
                            <option value='In Onderzoek'>In Onderzoek</option>
                            <option value='Afgehandeld'>Afgehandeld</option>
                        </select>
                    </div>
                </div>
                <div class='mt-6 flex justify-end space-x-3'>
                    <button type='button' onclick='closeEditIncidentModal()' class='bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 text-sm'>Annuleren</button>
                    <button type='submit' class='bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm flex items-center'><i class='fa-solid fa-save mr-2'></i>Wijzigingen Opslaan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const addIncidentModal = document.getElementById('addIncidentModal');
        const editIncidentModal = document.getElementById('editIncidentModal');
        const incidentsData = " . json_encode($incidents) . "; // Maak PHP data beschikbaar

        function openAddIncidentModal() {
            if (addIncidentModal) addIncidentModal.classList.add('active');
        }
        function closeAddIncidentModal() {
            if (addIncidentModal) {
                 addIncidentModal.classList.remove('active');
                 document.getElementById('addIncidentForm')?.reset();
            }
        }
        if (addIncidentModal) {
            addIncidentModal.addEventListener('click', (event) => { if (event.target === addIncidentModal) closeAddIncidentModal(); });
        }

        function openEditIncidentModal(incidentDbId) { // incidentDbId is de 'Id' uit de data
            const incidentEntry = incidentsData.find(inc => inc.Id == incidentDbId); // Gebruik == voor losse vergelijking als ID een getal is

            if (incidentEntry && editIncidentModal) {
                document.getElementById('edit_incident_id_db').value = incidentEntry.Id;
                document.getElementById('edit_incident_ref').value = incidentEntry.IncidentReferentie || '';
                document.getElementById('edit_incident_flight_id_ref').value = incidentEntry.TaakReferentie || '';
                // Converteer datum en tijd naar datetime-local formaat
                let datetimeValue = '';
                if(incidentEntry.GebeurdOpDatum && incidentEntry.GebeurdOpTijd){
                    datetimeValue = incidentEntry.GebeurdOpDatum + 'T' + incidentEntry.GebeurdOpTijd;
                }
                document.getElementById('edit_incident_datetime').value = datetimeValue;
                document.getElementById('edit_incident_type').value = incidentEntry.KlasseType || '';
                document.getElementById('edit_incident_severity').value = incidentEntry.Ernst || 'Minor';
                document.getElementById('edit_incident_details').value = incidentEntry.Details || '';
                document.getElementById('edit_incident_action_taken').value = incidentEntry.GenomenActie || '';
                document.getElementById('edit_incident_reporter').value = incidentEntry.Melder || '';
                document.getElementById('edit_incident_status').value = incidentEntry.Status || 'Open';

                editIncidentModal.classList.add('active');
            } else {
                alert('Incident niet gevonden voor bewerken.');
            }
        }

        function closeEditIncidentModal() {
            if (editIncidentModal) {
                 editIncidentModal.classList.remove('active');
                 document.getElementById('editIncidentForm')?.reset();
            }
        }
        function viewIncidentDetails(incidentDbId){
             alert('Details bekijken voor incident ID: ' + incidentDbId + '\\nDeze functionaliteit moet nog worden uitgewerkt.');
        }

        if (editIncidentModal) {
            editIncidentModal.addEventListener('click', (event) => { if (event.target === editIncidentModal) closeEditIncidentModal(); });
        }
    </script>
";

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../layouts/template.php';
