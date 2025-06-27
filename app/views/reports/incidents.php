<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../functions.php';

// API data ophalen
$apiBaseUrl = defined('API_BASE_URL') ? API_BASE_URL : "http://devserv01.holdingthedrones.com:4539";
$incidentsUrl = "$apiBaseUrl/incidenten";
$incidentsResponse = @file_get_contents($incidentsUrl);
$incidents = $incidentsResponse ? json_decode($incidentsResponse, true) : [];
if (isset($incidents['data'])) $incidents = $incidents['data'];

// Kolommen dynamisch bepalen
$kolomSet = [];
foreach ($incidents as $incident) {
    foreach ($incident as $key => $value) {
        $kolomSet[$key] = true;
    }
}
$kolommen = array_keys($kolomSet);

// Verzamel unieke waarden voor filters
$uniqueStatuses = [];
$uniqueSeverities = [];

foreach ($incidents as $incident) {
    if (!empty($incident['status']) && !in_array($incident['status'], $uniqueStatuses)) {
        $uniqueStatuses[] = $incident['status'];
    }
    if (!empty($incident['ernst']) && !in_array($incident['ernst'], $uniqueSeverities)) {
        $uniqueSeverities[] = $incident['ernst'];
    }
}
sort($uniqueStatuses);
sort($uniqueSeverities);

$showHeader = 1;
$userName = $_SESSION['user']['first_name'] ?? 'Onbekend';
$headTitle = "Incidenten Overzicht";
$gobackUrl = 0;
$rightAttributes = 0;

$bodyContent = '
<style>
    /* Identieke styling als flightlogs.php */
    .modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(17,24,39,0.70);
        z-index: 50;
        display: none;
        align-items: center;
        justify-content: center;
        transition: opacity 0.25s;
        opacity: 0;
    }
    .modal-overlay.active {
        display: flex;
        opacity: 1;
    }
    .modal-content {
        background: #fff;
        border-radius: 1.2rem;
        max-width: 700px;
        width: 100%;
        box-shadow: 0 8px 32px rgba(31, 41, 55, 0.18);
        padding: 2.5rem 2rem 1.5rem 2rem;
        position: relative;
        animation: modalIn 0.18s cubic-bezier(.4,0,.2,1);
        overflow-y: auto;
        max-height: 90vh;
    }
    @keyframes modalIn {
        from { transform: translateY(60px) scale(0.98); opacity: 0.3; }
        to   { transform: translateY(0) scale(1); opacity: 1; }
    }
    .modal-close-btn {
        position: absolute;
        right: 1.3rem;
        top: 1.3rem;
        background: transparent;
        border: none;
        font-size: 1.8rem;
        color: #bbb;
        cursor: pointer;
        transition: color 0.15s;
        line-height: 1;
    }
    .modal-close-btn:hover {
        color: #111827;
    }
    .modal-content h3 {
        margin-top: 0;
        margin-bottom: 1.7rem;
        font-size: 1.22rem;
        font-weight: 700;
        color: #1e293b;
        letter-spacing: .01em;
    }
    
    .detail-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
    }
    .detail-group {
        margin-bottom: 1.2rem;
    }
    .detail-label {
        font-size: 0.875rem;
        color: #6b7280;
        font-weight: 500;
        margin-bottom: 0.3rem;
    }
    .detail-value {
        font-size: 1rem;
        color: #1f2937;
        font-weight: 500;
    }
    
    .filter-bar {
        background: #f3f4f6;
        border: 1px solid #e5e7eb;
        border-radius: 0.75rem;
        padding: 1rem 1.5rem;
        margin: 0 1.5rem 1.5rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
    }
    .filter-group {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .filter-label {
        font-size: 0.875rem;
        color: #4b5563;
        font-weight: 500;
    }
    .filter-select, .filter-search {
        background: white;
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
        cursor: pointer;
        min-width: 180px;
    }
    .filter-select:focus, .filter-search:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    .filter-search {
        flex-grow: 1;
        background-image: url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' class=\'h-6 w-6\' fill=\'none\' viewBox=\'0 0 24 24\' stroke=\'%239ca3af\'%3E%3Cpath stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z\' /%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 0.75rem center;
        background-size: 1rem;
        min-width: 280px;
    }
    
    /* Algemene styling identiek aan flightlogs */
    .h-full { height: 100%; }
    .bg-gray-100 { background-color: #f3f4f6; }
    .shadow-md { box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06); }
    .rounded-tl-xl { border-top-left-radius: 0.75rem; }
    .w-full { width: 100%; }
    .flex { display: flex; }
    .flex-col { flex-direction: column; }
    .p-6 { padding: 1.5rem; }
    .bg-white { background-color: #fff; }
    .border-b { border-bottom-width: 1px; }
    .border-gray-200 { border-color: #e5e7eb; }
    .flex-shrink-0 { flex-shrink: 0; }
    .space-x-6 > * + * { margin-left: 1.5rem; }
    .text-sm { font-size: 0.875rem; }
    .font-medium { font-weight: 500; }
    .text-gray-600 { color: #4b5563; }
    .hover\:text-gray-900:hover { color: #111827; }
    .text-gray-900 { color: #111827; }
    .border-b-2 { border-bottom-width: 2px; }
    .border-black { border-color: #000; }
    .pb-2 { padding-bottom: 0.5rem; }
    .pt-4 { padding-top: 1rem; }
    .overflow-y-auto { overflow-y: auto; }
    .flex-grow { flex-grow: 1; }
    .rounded-lg { border-radius: 0.5rem; }
    .shadow { box-shadow: 0 1px 3px 0 rgba(0,0,0,0.1), 0 1px 2px 0 rgba(0,0,0,0.06); }
    .overflow-hidden { overflow: hidden; }
    .overflow-x-auto { overflow-x: auto; }
    .bg-gray-50 { background-color: #f9fafb; }
    .text-xs { font-size: 0.75rem; }
    .uppercase { text-transform: uppercase; }
    .text-gray-700 { color: #374151; }
    .px-4 { padding-left: 1rem; padding-right: 1rem; }
    .py-3 { padding-top: 0.75rem; padding-bottom: 0.75rem; }
    .text-left { text-align: left; }
    .divide-y > * + * { border-top-width: 1px; }
    .divide-gray-200 > * + * { border-color: #e5e7eb; }
    .whitespace-nowrap { white-space: nowrap; }
    .hover\:bg-gray-50:hover { background-color: #f9fafb; }
    .transition { transition: all 0.15s ease; }
    .text-blue-600 { color: #2563eb; }
    .hover\:text-blue-800:hover { color: #1e40af; }
    .mr-2 { margin-right: 0.5rem; }
    .text-center { text-align: center; }
    .text-gray-500 { color: #6b7280; }
    .py-10 { padding-top: 2.5rem; padding-bottom: 2.5rem; }
    .justify-between { justify-content: space-between; }
    .items-center { align-items: center; }
    
    /* Responsive aanpassingen */
    @media (max-width: 768px) {
        .filter-bar {
            flex-direction: column;
            align-items: stretch;
        }
        .filter-group {
            width: 100%;
        }
        .filter-select, .filter-search {
            width: 100%;
        }
    }
</style>

<div class="h-full bg-gray-100 shadow-md rounded-tl-xl w-full flex flex-col">
    <div class="p-6 bg-white flex justify-between items-center border-b border-gray-200 flex-shrink-0">
        <div class="flex space-x-6 text-sm font-medium">
            <a href="flight-logs.php" class="text-gray-600 hover:text-gray-900">Vlucht Logs</a>
            <a href="incidents.php" class="text-gray-900 border-b-2 border-black pb-2">Incidenten</a>
        </div>
        <button onclick="openAddIncidentModal()" class="bg-gradient-to-r from-red-600 to-red-800 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors text-sm flex items-center gap-2">
            <i class="fa-solid fa-plus-circle"></i> Nieuw Incident
        </button>
    </div>
    
    <!-- Filter Bar -->
    <div class="px-6 pt-4">
        <div class="filter-bar">
            <div class="filter-group">
                <span class="filter-label">Status:</span>
                <select id="statusFilter" class="filter-select">
                    <option value="">Alle statussen</option>';
foreach ($uniqueStatuses as $status) {
    $bodyContent .= '<option value="' . htmlspecialchars(strtolower($status)) . '">' . htmlspecialchars($status) . '</option>';
}
$bodyContent .= '
                </select>
            </div>
            
            <div class="filter-group">
                <span class="filter-label">Ernst:</span>
                <select id="severityFilter" class="filter-select">
                    <option value="">Alle ernstniveaus</option>';
foreach ($uniqueSeverities as $severity) {
    $bodyContent .= '<option value="' . htmlspecialchars(strtolower($severity)) . '">' . htmlspecialchars($severity) . '</option>';
}
$bodyContent .= '
                </select>
            </div>
            
            <div class="filter-group flex-grow">
                <input id="searchInput" type="text" placeholder="Zoek incident..." class="filter-search">
            </div>
        </div>
    </div>
    
    <div class="p-6 overflow-y-auto flex-grow">
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table id="incidentsTable" class="w-full">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                        <tr>';
foreach ($kolommen as $kolom) {
    $bodyContent .= '<th class="px-4 py-3 text-left">' . htmlspecialchars($kolom) . '</th>';
}
$bodyContent .= '<th class="px-4 py-3 text-left">Acties</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 text-sm">';
if (!empty($incidents) && is_array($incidents)) {
    foreach ($incidents as $incident) {
        $bodyContent .= '<tr class="hover:bg-gray-50 transition"';
        if (isset($incident['status'])) {
            $bodyContent .= ' data-status="' . htmlspecialchars(strtolower($incident['status'])) . '"';
        }
        if (isset($incident['ernst'])) {
            $bodyContent .= ' data-severity="' . htmlspecialchars(strtolower($incident['ernst'])) . '"';
        }
        $bodyContent .= '>';

        foreach ($kolommen as $kolom) {
            $waarde = $incident[$kolom] ?? '';
            if (is_array($waarde)) {
                $waarde = json_encode($waarde, JSON_UNESCAPED_UNICODE);
            } elseif (is_bool($waarde)) {
                $waarde = $waarde ? 'Ja' : 'Nee';
            }
            $bodyContent .= '<td class="px-4 py-3 whitespace-nowrap">' . htmlspecialchars((string)$waarde) . '</td>';
        }

        $bodyContent .= '<td class="px-4 py-3 whitespace-nowrap">
                <button onclick="openIncidentDetailModal(' . htmlspecialchars(json_encode($incident, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) . ')" class="text-blue-600 hover:text-blue-800 mr-2" title="Details">
                    <i class="fa-regular fa-file-lines"></i>
                </button>
            </td>';
        $bodyContent .= '</tr>';
    }
} else {
    $bodyContent .= '<tr><td colspan="' . (count($kolommen) + 1) . '" class="text-center text-gray-500 py-10">Geen incidenten gevonden of data kon niet worden geladen.</td></tr>';
}
$bodyContent .= '
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nieuw Incident -->
<div id="addIncidentModal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="addIncidentTitle">
    <div class="modal-content">
        <button class="modal-close-btn" aria-label="Sluit modal" onclick="closeAddIncidentModal()">&times;</button>
        <h3 id="addIncidentTitle" class="flex items-center gap-2">
            <i class="fa-solid fa-plus-circle text-red-500"></i> 
            Nieuw Incident Melden
        </h3>
        <form id="addIncidentForm" action="save_incident.php" method="POST" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="form-group">
                    <label for="add_incident_datetime">Datum & Tijd Incident <span class="text-red-500">*</span></label>
                    <input type="datetime-local" name="datum" id="add_incident_datetime" class="w-full p-2 border border-gray-300 rounded-md shadow-sm" required>
                </div>
                
                <div class="form-group">
                    <label for="add_incident_type">Type Incident <span class="text-red-500">*</span></label>
                    <select name="incident_type" id="add_incident_type" class="w-full p-2 border border-gray-300 rounded-md shadow-sm" required>
                        <option value="">Selecteer type...</option>
                        <option value="Technisch - Motor">Technisch - Motor</option>
                        <option value="Technisch - Batterij">Technisch - Batterij</option>
                        <option value="Technisch - Communicatie">Technisch - Communicatie</option>
                        <option value="Technisch - Software">Technisch - Software</option>
                        <option value="Operationeel - Procedurefout">Operationeel - Procedurefout</option>
                        <option value="Operationeel - Pilootfout">Operationeel - Pilootfout</option>
                        <option value="Omgeving - Weer">Omgeving - Weer</option>
                        <option value="Omgeving - Obstakel">Omgeving - Obstakel (bv. vogel)</option>
                        <option value="Omgeving - GPS/Signaal verlies">Omgeving - GPS/Signaal verlies</option>
                        <option value="Beveiliging - Ongeautoriseerde toegang">Beveiliging - Ongeautoriseerde toegang</option>
                        <option value="Overig">Overig</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="add_incident_severity">Ernst <span class="text-red-500">*</span></label>
                    <select name="ernst" id="add_incident_severity" class="w-full p-2 border border-gray-300 rounded-md shadow-sm" required>
                        <option value="laag">Laag</option>
                        <option value="middel">Middel</option>
                        <option value="hoog">Hoog</option>
                        <option value="kritiek">Kritiek</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="add_incident_flight_id">Gerelateerde Vlucht ID</label>
                    <input type="text" name="vluchtId" id="add_incident_flight_id" class="w-full p-2 border border-gray-300 rounded-md shadow-sm" placeholder="bv. 1">
                </div>
                
                <div class="form-group">
                    <label for="add_incident_weather">Weersomstandigheden</label>
                    <input type="text" name="weeromstandigheden" id="add_incident_weather" class="w-full p-2 border border-gray-300 rounded-md shadow-sm" placeholder="bv. Zonnig, winderig">
                </div>
                
                <div class="form-group">
                    <label for="add_incident_camera">Camera Status</label>
                    <input type="text" name="camera_status" id="add_incident_camera" class="w-full p-2 border border-gray-300 rounded-md shadow-sm" placeholder="bv. Functioneel">
                </div>
                
                <div class="form-group">
                    <label for="add_incident_battery">Batterij Status (%)</label>
                    <input type="number" name="batterij_status" id="add_incident_battery" min="0" max="100" class="w-full p-2 border border-gray-300 rounded-md shadow-sm" placeholder="0-100">
                </div>
                
                <div class="form-group">
                    <label for="add_incident_code">Incident Code</label>
                    <input type="text" name="incidentCode" id="add_incident_code" class="w-full p-2 border border-gray-300 rounded-md shadow-sm" placeholder="bv. INC-2024-001">
                </div>
                
                <div class="form-group">
                    <label for="add_incident_org">Organisatie ID</label>
                    <input type="number" name="organisatieId" id="add_incident_org" class="w-full p-2 border border-gray-300 rounded-md shadow-sm" placeholder="bv. 1">
                </div>
                
                <div class="form-group">
                    <label for="add_incident_pilot">Piloot ID</label>
                    <input type="number" name="pilootId" id="add_incident_pilot" class="w-full p-2 border border-gray-300 rounded-md shadow-sm" placeholder="bv. 1">
                </div>
                
                <div class="form-group">
                    <label for="add_incident_lat">Locatie Latitude</label>
                    <input type="text" name="locatie_latitude" id="add_incident_lat" class="w-full p-2 border border-gray-300 rounded-md shadow-sm" placeholder="bv. 52.12345">
                </div>
                
                <div class="form-group">
                    <label for="add_incident_lon">Locatie Longitude</label>
                    <input type="text" name="locatie_longitude" id="add_incident_lon" class="w-full p-2 border border-gray-300 rounded-md shadow-sm" placeholder="bv. 4.56789">
                </div>
            </div>
            
            <div class="form-group">
                <label for="add_incident_details">Beschrijving <span class="text-red-500">*</span></label>
                <textarea name="beschrijving" id="add_incident_details" rows="3" class="w-full p-2 border border-gray-300 rounded-md shadow-sm" placeholder="Wat is er gebeurd?" required></textarea>
            </div>
            
            <div class="form-group">
                <label for="add_incident_action_taken">Genomen Acties <span class="text-red_500">*</span></label>
                <textarea name="actie_ondernomen" id="add_incident_action_taken" rows="2" class="w-full p-2 border border-gray-300 rounded-md shadow-sm" placeholder="Wat is er gedaan?" required></textarea>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="form-group">
                    <label for="add_incident_reporter">Rapporteur ID <span class="text-red-500">*</span></label>
                    <input type="number" name="rapporteur_id" id="add_incident_reporter" value="' . ($_SESSION['user']['id'] ?? '') . '" class="w-full p-2 border border-gray-300 rounded-md shadow-sm" required>
                </div>
                
                <div class="form-group">
                    <label for="add_incident_status">Status</label>
                    <select name="status" id="add_incident_status" class="w-full p-2 border border-gray-300 rounded-md shadow-sm">
                        <option value="open" selected>Open</option>
                        <option value="in behandeling">In Behandeling</option>
                        <option value="gesloten">Gesloten</option>
                    </select>
                </div>
            </div>
            
            <div class="pt-4 flex justify-end space-x-3">
                <button type="button" onclick="closeAddIncidentModal()" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 text-sm">Annuleren</button>
                <button type="submit" class="bg-gradient-to-r from-red-600 to-red-800 text-white px-4 py-2 rounded-lg hover:bg-red-700 text-sm flex items-center gap-2">
                    <i class="fa-solid fa-paper-plane"></i> Incident Melden
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Details Incident -->
<div id="incidentDetailModal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="incidentDetailTitle">
    <div class="modal-content">
        <button class="modal-close-btn" aria-label="Sluit details" onclick="closeIncidentDetailModal()">&times;</button>
        <h3 id="incidentDetailTitle" class="flex items-center gap-2">
            <i class="fa-regular fa-file-lines text-blue-500"></i> 
            Incident Details
        </h3>
        <div id="incidentDetailContent" class="detail-grid"></div>
    </div>
</div>

<script>
    // Modal open/close
    const addIncidentModal = document.getElementById("addIncidentModal");
    const incidentDetailModal = document.getElementById("incidentDetailModal");

    function openAddIncidentModal() {
        if (addIncidentModal) addIncidentModal.classList.add("active");
        document.body.style.overflow = "hidden";
        const now = new Date();
        const localDateTime = new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
        document.getElementById("add_incident_datetime").value = localDateTime;
    }
    
    function closeAddIncidentModal() {
        if (addIncidentModal) {
            addIncidentModal.classList.remove("active");
            document.getElementById("addIncidentForm").reset();
            document.body.style.overflow = "";
        }
    }
    
    if (addIncidentModal) {
        addIncidentModal.addEventListener("click", (event) => { 
            if (event.target === addIncidentModal) closeAddIncidentModal(); 
        });
    }

    // Incident detail modal
    function openIncidentDetailModal(incidentData) {
        const modalContent = document.getElementById("incidentDetailContent");
        if (!modalContent || !incidentData) return;
        
        modalContent.innerHTML = "";
        
        const fieldsToShow = {
            "incidentId": "Incident ID",
            "incidentCode": "Incident Code",
            "beschrijving": "Beschrijving",
            "datum": "Datum & Tijd",
            "organisatieId": "Organisatie ID",
            "vluchtId": "Vlucht ID",
            "pilootId": "Piloot ID",
            "locatie_latitude": "Locatie Latitude",
            "locatie_longitude": "Locatie Longitude",
            "incident_type": "Type Incident",
            "ernst": "Ernst",
            "weeromstandigheden": "Weersomstandigheden",
            "camera_status": "Camera Status",
            "batterij_status": "Batterij Status",
            "actie_ondernomen": "Actie Ondernomen",
            "rapporteur_id": "Rapporteur ID",
            "status": "Status",
            "bijlagen": "Bijlagen"
        };

        for (const [key, label] of Object.entries(fieldsToShow)) {
            let value = incidentData[key] ?? "-";
            
            // Speciale verwerking voor bepaalde velden
            if (key === "bijlagen" && Array.isArray(value)) {
                value = value.map(url => `<a href="${url}" target="_blank" class="text-blue-500 hover:underline">Bekijk bijlage</a>`).join("<br>");
            } 
            else if (key === "datum" && value !== "-") {
                try {
                    const dateObj = new Date(value);
                    value = dateObj.toLocaleString("nl-NL", {
                        year: "numeric",
                        month: "2-digit",
                        day: "2-digit",
                        hour: "2-digit",
                        minute: "2-digit"
                    });
                } catch (e) {}
            }
            else if (key === "locatie_latitude" && incidentData["locatie_longitude"]) {
                value = `<a href="https://www.google.com/maps/search/?api=1&query=${incidentData["locatie_latitude"]},${incidentData["locatie_longitude"]}" 
                          target="_blank" class="text-blue-500 hover:underline">
                          ${incidentData["locatie_latitude"]}, ${incidentData["locatie_longitude"]}
                        </a>`;
            }
            else if (key === "status") {
                const statusColors = {
                    "open": "text-yellow-600 bg-yellow-100",
                    "in behandeling": "text-blue-600 bg-blue-100",
                    "gesloten": "text-green-600 bg-green-100"
                };
                const colorClass = statusColors[value.toLowerCase()] || "bg-gray-100 text-gray-800";
                value = `<span class="px-2 py-1 rounded-full text-xs ${colorClass}">${value}</span>`;
            }
            else if (key === "ernst") {
                const severityColors = {
                    "laag": "text-green-600 bg-green-100",
                    "middel": "text-yellow-600 bg-yellow-100",
                    "hoog": "text-orange-600 bg-orange-100",
                    "kritiek": "text-red-600 bg-red-100"
                };
                const colorClass = severityColors[value.toLowerCase()] || "bg-gray-100 text-gray-800";
                value = `<span class="px-2 py-1 rounded-full text-xs ${colorClass}">${value}</span>`;
            }
            
            modalContent.innerHTML += `
                <div class="detail-group">
                    <div class="detail-label">${label}</div>
                    <div class="detail-value">${value}</div>
                </div>`;
        }
        
        if (incidentDetailModal) {
            incidentDetailModal.classList.add("active");
            document.body.style.overflow = "hidden";
        }
    }
    
    function closeIncidentDetailModal() {
        if (incidentDetailModal) {
            incidentDetailModal.classList.remove("active");
            document.getElementById("incidentDetailContent").innerHTML = "";
            document.body.style.overflow = "";
        }
    }
    
    if (incidentDetailModal) {
        incidentDetailModal.addEventListener("click", (event) => {
            if (event.target === incidentDetailModal) closeIncidentDetailModal();
        });
    }

    // Filter functionaliteit
    function filterIncidents() {
        const searchTerm = document.getElementById("searchInput").value.toLowerCase();
        const statusFilter = document.getElementById("statusFilter").value.toLowerCase();
        const severityFilter = document.getElementById("severityFilter").value.toLowerCase();
        
        const rows = document.querySelectorAll("#incidentsTable tbody tr");
        
        rows.forEach(row => {
            const rowText = row.textContent.toLowerCase();
            const status = row.dataset.status || "";
            const severity = row.dataset.severity || "";
            
            const matchesSearch = rowText.includes(searchTerm);
            const matchesStatus = statusFilter === "" || status === statusFilter;
            const matchesSeverity = severityFilter === "" || severity === severityFilter;
            
            row.style.display = (matchesSearch && matchesStatus && matchesSeverity) ? "" : "none";
        });
    }
    
    // Event listeners voor filters
    document.addEventListener("DOMContentLoaded", () => {
        document.getElementById("searchInput").addEventListener("input", filterIncidents);
        document.getElementById("statusFilter").addEventListener("change", filterIncidents);
        document.getElementById("severityFilter").addEventListener("change", filterIncidents);
    });
</script>';

// INCLUDE HEADER & TEMPLATE
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../layouts/template.php';
