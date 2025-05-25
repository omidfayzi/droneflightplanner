<?php
// /var/www/public/frontend/pages/incidents/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../functions.php';

// Haal data uit de API
$apiBaseUrl = "http://devserv01.holdingthedrones.com:4539";
$incidentsUrl = "$apiBaseUrl/incidents";
$incidentsResponse = @file_get_contents($incidentsUrl);
$incidents = $incidentsResponse ? json_decode($incidentsResponse, true) : [];
if (isset($incidents['data'])) $incidents = $incidents['data'];

// Dynamisch alle kolomnamen verzamelen
$kolomSet = [];
foreach ($incidents as $incident) {
    foreach ($incident as $key => $value) {
        $kolomSet[$key] = true;
    }
}
$kolommen = array_keys($kolomSet);

$showHeader = 1;
$userName = $_SESSION['user']['first_name'] ?? 'Onbekend';
$headTitle = "Incidenten Log";
$gobackUrl = 0;
$rightAttributes = 0;

// BodyContent
$bodyContent = "
    <style>
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); display: none; align-items: center; justify-content: center; z-index: 1050; opacity: 0; transition: opacity 0.3s; }
        .modal-overlay.active { display: flex; opacity: 1; }
        .modal-content { background-color: white; padding: 2rem; border-radius: 0.5rem; width: 90%; max-width: 700px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04); position: relative; transform: translateY(-20px) scale(0.95); opacity: 0; transition: transform 0.3s, opacity 0.3s; max-height: 90vh; overflow-y: auto; }
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
                        <input type='text' placeholder='Zoek incident...' class='px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 w-64'>
                    </div>
                </div>
                <div class='overflow-x-auto'>
                    <table class='w-full'>
                        <thead class='bg-gray-50 text-xs uppercase text-gray-700'>
                            <tr>";
foreach ($kolommen as $kolom) {
    $bodyContent .= "<th class='px-4 py-3 text-left'>" . htmlspecialchars($kolom) . "</th>";
}
$bodyContent .= "<th class='px-4 py-3 text-left'>Acties</th>
                            </tr>
                        </thead>
                        <tbody class='divide-y divide-gray-200 text-sm'>";
if (!empty($incidents) && is_array($incidents)) {
    foreach ($incidents as $incident) {
        $bodyContent .= "<tr class='hover:bg-gray-50 transition'>";
        foreach ($kolommen as $kolom) {
            $waarde = array_key_exists($kolom, $incident) ? $incident[$kolom] : '';
            if (is_bool($waarde)) $waarde = $waarde ? 'Ja' : 'Nee';
            $bodyContent .= "<td class='px-4 py-3 whitespace-nowrap'>" . htmlspecialchars((string)$waarde) . "</td>";
        }
        // Unieke id zoeken
        $id = $incident['Id'] ?? $incident['DFPPINC_Id'] ?? $incident['id'] ?? '';
        $disabledClass = $id ? "" : "opacity-50 pointer-events-none";
        $bodyContent .= "<td class='px-4 py-3 whitespace-nowrap'>
            <button onclick='viewIncidentDetails(\"$id\")' class='text-blue-600 hover:text-blue-800 mr-2 $disabledClass' title='Details'><i class='fa-solid fa-eye'></i></button>
            <button onclick='openEditIncidentModal(\"$id\")' class='text-gray-600 hover:text-gray-800 $disabledClass' title='Bewerk'><i class='fa-solid fa-pencil'></i></button>
        </td>";
        $bodyContent .= "</tr>";
    }
} else {
    $bodyContent .= "<tr><td colspan='" . (count($kolommen) + 1) . "' class='text-center text-gray-500 py-10'>Geen incidenten gevonden of data kon niet worden geladen.</td></tr>";
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
                <input type='hidden' name='edit_incident_id_db' id='edit_incident_id_db'>
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
        const incidentsData = " . json_encode($incidents) . ";

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

        function openEditIncidentModal(incidentDbId) {
            const incidentEntry = incidentsData.find(inc =>
                inc.Id == incidentDbId ||
                inc.DFPPINC_Id == incidentDbId ||
                inc.id == incidentDbId
            );
            if (incidentEntry && editIncidentModal) {
                document.getElementById('edit_incident_id_db').value = incidentEntry.Id || incidentEntry.DFPPINC_Id || incidentEntry.id || '';
                document.getElementById('edit_incident_ref').value = incidentEntry.IncidentReferentie || incidentEntry.incidentRef || incidentEntry.DFPPINC_Ref || '';
                document.getElementById('edit_incident_flight_id_ref').value = incidentEntry.TaakReferentie || incidentEntry.flightRef || incidentEntry.DFPPINC_FlightRef || '';
                let datetimeValue = '';
                if((incidentEntry.GebeurdOpDatum || incidentEntry.date) && (incidentEntry.GebeurdOpTijd || incidentEntry.time)){
                    const d = incidentEntry.GebeurdOpDatum || incidentEntry.date;
                    const t = incidentEntry.GebeurdOpTijd || incidentEntry.time;
                    datetimeValue = d + 'T' + t;
                }
                document.getElementById('edit_incident_datetime').value = datetimeValue;
                document.getElementById('edit_incident_type').value = incidentEntry.KlasseType || incidentEntry.type || '';
                document.getElementById('edit_incident_severity').value = incidentEntry.Ernst || incidentEntry.severity || 'Minor';
                document.getElementById('edit_incident_details').value = incidentEntry.Details || incidentEntry.details || '';
                document.getElementById('edit_incident_action_taken').value = incidentEntry.GenomenActie || incidentEntry.actionTaken || '';
                document.getElementById('edit_incident_reporter').value = incidentEntry.Melder || incidentEntry.reporter || '';
                document.getElementById('edit_incident_status').value = incidentEntry.Status || incidentEntry.status || 'Open';

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
            alert('Details bekijken voor incident ID: ' + incidentDbId + '\\nDeze functionaliteit kan je uitbreiden naar een eigen modal.');
        }

        if (editIncidentModal) {
            editIncidentModal.addEventListener('click', (event) => { if (event.target === editIncidentModal) closeEditIncidentModal(); });
        }
    </script>
";

// ---- INCLUDE LAYOUT ----
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../layouts/template.php';
