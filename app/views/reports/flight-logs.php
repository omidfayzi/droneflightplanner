<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../functions.php';

// API data ophalen voor vluchtlogboeken
$apiBaseUrl = defined('API_BASE_URL') ? API_BASE_URL : "http://devserv01.holdingthedrones.com:4539";
$flightsUrl = "$apiBaseUrl/vluchten";
$flightsResponse = @file_get_contents($flightsUrl);
$flights = $flightsResponse ? json_decode($flightsResponse, true) : [];
if (isset($flights['data'])) $flights = $flights['data'];

// Kolommen dynamisch bepalen
$kolomSet = [];
foreach ($flights as $flight) {
    foreach ($flight as $key => $value) {
        $kolomSet[$key] = true;
    }
}
$kolommen = array_keys($kolomSet);

// Verzamel unieke waarden voor filters
$uniqueStatuses = [];
$uniqueOrganisations = [];

foreach ($flights as $flight) {
    if (!empty($flight['status']) && !in_array($flight['status'], $uniqueStatuses)) {
        $uniqueStatuses[] = $flight['status'];
    }
    if (!empty($flight['organisatieId']) && !in_array($flight['organisatieId'], $uniqueOrganisations)) {
        $uniqueOrganisations[] = $flight['organisatieId'];
    }
}
sort($uniqueStatuses);
sort($uniqueOrganisations);

$showHeader = 1;
$userName = $_SESSION['user']['first_name'] ?? 'Onbekend';
$headTitle = "Vlucht Logs Overzicht";
$gobackUrl = 0;
$rightAttributes = 0;

$bodyContent = '
<style>
    /* Identieke styling als incidents.php */
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
    
    /* Algemene styling identiek aan incidents.php */
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
            <a href="flight-logs.php" class="text-gray-900 border-b-2 border-black pb-2">Vlucht Logs</a>
            <a href="incidents.php" class="text-gray-600 hover:text-gray-900">Incidenten</a>
        </div>
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
                <span class="filter-label">Organisatie ID:</span>
                <select id="organisationFilter" class="filter-select">
                    <option value="">Alle organisaties</option>';
foreach ($uniqueOrganisations as $org) {
    $bodyContent .= '<option value="' . htmlspecialchars($org) . '">' . htmlspecialchars($org) . '</option>';
}
$bodyContent .= '
                </select>
            </div>
            
            <div class="filter-group flex-grow">
                <input id="searchInput" type="text" placeholder="Zoek vlucht..." class="filter-search">
            </div>
        </div>
    </div>
    
    <div class="p-6 overflow-y-auto flex-grow">
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table id="flightsTable" class="w-full">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                        <tr>';
foreach ($kolommen as $kolom) {
    $bodyContent .= '<th class="px-4 py-3 text-left">' . htmlspecialchars($kolom) . '</th>';
}
$bodyContent .= '<th class="px-4 py-3 text-left">Acties</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 text-sm">';
if (!empty($flights) && is_array($flights)) {
    foreach ($flights as $flight) {
        $bodyContent .= '<tr class="hover:bg-gray-50 transition"';
        if (isset($flight['status'])) {
            $bodyContent .= ' data-status="' . htmlspecialchars(strtolower($flight['status'])) . '"';
        }
        if (isset($flight['organisatieId'])) {
            $bodyContent .= ' data-organisation="' . htmlspecialchars($flight['organisatieId']) . '"';
        }
        $bodyContent .= '>';

        foreach ($kolommen as $kolom) {
            $waarde = $flight[$kolom] ?? '';
            if (is_array($waarde)) {
                $waarde = json_encode($waarde, JSON_UNESCAPED_UNICODE);
            } elseif (is_bool($waarde)) {
                $waarde = $waarde ? 'Ja' : 'Nee';
            }
            $bodyContent .= '<td class="px-4 py-3 whitespace-nowrap">' . htmlspecialchars((string)$waarde) . '</td>';
        }

        $bodyContent .= '<td class="px-4 py-3 whitespace-nowrap">
                <button onclick="openFlightDetailModal(' . htmlspecialchars(json_encode($flight, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) . ')" class="text-blue-600 hover:text-blue-800 mr-2" title="Details">
                    <i class="fa-regular fa-file-lines"></i>
                </button>
            </td>';
        $bodyContent .= '</tr>';
    }
} else {
    $bodyContent .= '<tr><td colspan="' . (count($kolommen) + 1) . '" class="text-center text-gray-500 py-10">Geen vluchten gevonden of data kon niet worden geladen.</td></tr>';
}
$bodyContent .= '
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nieuwe Vlucht -->
<div id="addFlightModal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="addFlightTitle">
    <div class="modal-content">
        <button class="modal-close-btn" aria-label="Sluit modal" onclick="closeAddFlightModal()">&times;</button>
        <h3 id="addFlightTitle" class="flex items-center gap-2">
            <i class="fa-solid fa-plus-circle text-blue-500"></i> 
            Nieuwe Vlucht Toevoegen
        </h3>
        <form id="addFlightForm" action="save_flight.php" method="POST" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="form-group">
                    <label for="add_flight_start">Starttijd <span class="text-red-500">*</span></label>
                    <input type="datetime-local" name="starttijd" id="add_flight_start" class="w-full p-2 border border-gray-300 rounded-md shadow-sm" required>
                </div>
                
                <div class="form-group">
                    <label for="add_flight_end">Eindtijd <span class="text-red-500">*</span></label>
                    <input type="datetime-local" name="eindtijd" id="add_flight_end" class="w-full p-2 border border-gray-300 rounded-md shadow-sm" required>
                </div>
                
                <div class="form-group">
                    <label for="add_flight_drone">Drone ID <span class="text-red-500">*</span></label>
                    <input type="text" name="droneId" id="add_flight_drone" class="w-full p-2 border border-gray-300 rounded-md shadow-sm" required>
                </div>
                
                <div class="form-group">
                    <label for="add_flight_pilot">Piloot ID <span class="text-red-500">*</span></label>
                    <input type="number" name="pilootId" id="add_flight_pilot" class="w-full p-2 border border-gray-300 rounded-md shadow-sm" required>
                </div>
                
                <div class="form-group">
                    <label for="add_flight_organisation">Organisatie ID <span class="text-red-500">*</span></label>
                    <input type="number" name="organisatieId" id="add_flight_organisation" class="w-full p-2 border border-gray-300 rounded-md shadow-sm" required>
                </div>
                
                <div class="form-group">
                    <label for="add_flight_status">Status <span class="text-red-500">*</span></label>
                    <select name="status" id="add_flight_status" class="w-full p-2 border border-gray-300 rounded-md shadow-sm" required>
                        <option value="gepland">Gepland</option>
                        <option value="actief">Actief</option>
                        <option value="voltooid">Voltooid</option>
                        <option value="geannuleerd">Geannuleerd</option>
                    </select>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="form-group">
                    <label for="add_flight_start_lat">Start Latitude</label>
                    <input type="text" name="start_locatie_latitude" id="add_flight_start_lat" class="w-full p-2 border border-gray-300 rounded-md shadow-sm">
                </div>
                
                <div class="form-group">
                    <label for="add_flight_start_lon">Start Longitude</label>
                    <input type="text" name="start_locatie_longitude" id="add_flight_start_lon" class="w-full p-2 border border-gray-300 rounded-md shadow-sm">
                </div>
                
                <div class="form-group">
                    <label for="add_flight_end_lat">Eind Latitude</label>
                    <input type="text" name="eind_locatie_latitude" id="add_flight_end_lat" class="w-full p-2 border border-gray-300 rounded-md shadow-sm">
                </div>
                
                <div class="form-group">
                    <label for="add_flight_end_lon">Eind Longitude</label>
                    <input type="text" name="eind_locatie_longitude" id="add_flight_end_lon" class="w-full p-2 border border-gray-300 rounded-md shadow-sm">
                </div>
            </div>
            
            <div class="form-group">
                <label for="add_flight_description">Beschrijving</label>
                <textarea name="beschrijving" id="add_flight_description" rows="3" class="w-full p-2 border border-gray-300 rounded-md shadow-sm" placeholder="Beschrijf de vlucht"></textarea>
            </div>
            
            <div class="pt-4 flex justify-end space-x-3">
                <button type="button" onclick="closeAddFlightModal()" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 text-sm">Annuleren</button>
                <button type="submit" class="bg-gradient-to-r from-blue-600 to-blue-800 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm flex items-center gap-2">
                    <i class="fa-solid fa-paper-plane"></i> Vlucht Opslaan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Details Vlucht -->
<div id="flightDetailModal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="flightDetailTitle">
    <div class="modal-content">
        <button class="modal-close-btn" aria-label="Sluit details" onclick="closeFlightDetailModal()">&times;</button>
        <h3 id="flightDetailTitle" class="flex items-center gap-2">
            <i class="fa-regular fa-file-lines text-blue-500"></i> 
            Vlucht Details
        </h3>
        <div id="flightDetailContent" class="detail-grid"></div>
    </div>
</div>

<script>
    // Modal open/close
    const addFlightModal = document.getElementById("addFlightModal");
    const flightDetailModal = document.getElementById("flightDetailModal");

    function openAddFlightModal() {
        if (addFlightModal) addFlightModal.classList.add("active");
        document.body.style.overflow = "hidden";
        const now = new Date();
        const localDateTime = new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
        document.getElementById("add_flight_start").value = localDateTime;
        
        // Eindtijd instellen op 30 minuten later
        const endTime = new Date(now.getTime() + 30 * 60000).toISOString().slice(0, 16);
        document.getElementById("add_flight_end").value = endTime;
    }
    
    function closeAddFlightModal() {
        if (addFlightModal) {
            addFlightModal.classList.remove("active");
            document.getElementById("addFlightForm").reset();
            document.body.style.overflow = "";
        }
    }
    
    if (addFlightModal) {
        addFlightModal.addEventListener("click", (event) => { 
            if (event.target === addFlightModal) closeAddFlightModal(); 
        });
    }

    // Vlucht detail modal
    function openFlightDetailModal(flightData) {
        const modalContent = document.getElementById("flightDetailContent");
        if (!modalContent || !flightData) return;
        
        modalContent.innerHTML = "";
        
        const fieldsToShow = {
            "vluchtId": "Vlucht ID",
            "starttijd": "Starttijd",
            "eindtijd": "Eindtijd",
            "droneId": "Drone ID",
            "pilootId": "Piloot ID",
            "organisatieId": "Organisatie ID",
            "status": "Status",
            "start_locatie_latitude": "Start Latitude",
            "start_locatie_longitude": "Start Longitude",
            "eind_locatie_latitude": "Eind Latitude",
            "eind_locatie_longitude": "Eind Longitude",
            "beschrijving": "Beschrijving",
            "afstand": "Afstand (m)",
            "duur": "Duur (min)"
        };

        for (const [key, label] of Object.entries(fieldsToShow)) {
            let value = flightData[key] ?? "-";
            
            // Speciale verwerking voor bepaalde velden
            if (key === "starttijd" || key === "eindtijd") {
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
            else if (key === "status") {
                const statusColors = {
                    "gepland": "text-blue-600 bg-blue-100",
                    "actief": "text-green-600 bg-green-100",
                    "voltooid": "text-gray-600 bg-gray-100",
                    "geannuleerd": "text-red-600 bg-red-100"
                };
                const colorClass = statusColors[value.toLowerCase()] || "bg-gray-100 text-gray-800";
                value = `<span class="px-2 py-1 rounded-full text-xs ${colorClass}">${value}</span>`;
            }
            else if (key === "start_locatie_latitude" && flightData["start_locatie_longitude"]) {
                value = `<a href="https://www.google.com/maps/search/?api=1&query=${flightData["start_locatie_latitude"]},${flightData["start_locatie_longitude"]}" 
                          target="_blank" class="text-blue-500 hover:underline">
                          ${flightData["start_locatie_latitude"]}, ${flightData["start_locatie_longitude"]}
                        </a>`;
            }
            else if (key === "eind_locatie_latitude" && flightData["eind_locatie_longitude"]) {
                value = `<a href="https://www.google.com/maps/search/?api=1&query=${flightData["eind_locatie_latitude"]},${flightData["eind_locatie_longitude"]}" 
                          target="_blank" class="text-blue-500 hover:underline">
                          ${flightData["eind_locatie_latitude"]}, ${flightData["eind_locatie_longitude"]}
                        </a>`;
            }
            else if (key === "duur" && value > 0) {
                // Converteer seconden naar minuten:seconden
                const minutes = Math.floor(value / 60);
                const seconds = value % 60;
                value = `${minutes} min ${seconds} sec`;
            }
            
            modalContent.innerHTML += `
                <div class="detail-group">
                    <div class="detail-label">${label}</div>
                    <div class="detail-value">${value}</div>
                </div>`;
        }
        
        if (flightDetailModal) {
            flightDetailModal.classList.add("active");
            document.body.style.overflow = "hidden";
        }
    }
    
    function closeFlightDetailModal() {
        if (flightDetailModal) {
            flightDetailModal.classList.remove("active");
            document.getElementById("flightDetailContent").innerHTML = "";
            document.body.style.overflow = "";
        }
    }
    
    if (flightDetailModal) {
        flightDetailModal.addEventListener("click", (event) => {
            if (event.target === flightDetailModal) closeFlightDetailModal();
        });
    }

    // Filter functionaliteit
    function filterFlights() {
        const searchTerm = document.getElementById("searchInput").value.toLowerCase();
        const statusFilter = document.getElementById("statusFilter").value.toLowerCase();
        const organisationFilter = document.getElementById("organisationFilter").value;
        
        const rows = document.querySelectorAll("#flightsTable tbody tr");
        
        rows.forEach(row => {
            const rowText = row.textContent.toLowerCase();
            const status = row.dataset.status || "";
            const organisation = row.dataset.organisation || "";
            
            const matchesSearch = rowText.includes(searchTerm);
            const matchesStatus = statusFilter === "" || status === statusFilter;
            const matchesOrganisation = organisationFilter === "" || organisation === organisationFilter;
            
            row.style.display = (matchesSearch && matchesStatus && matchesOrganisation) ? "" : "none";
        });
    }
    
    // Event listeners voor filters
    document.addEventListener("DOMContentLoaded", () => {
        document.getElementById("searchInput").addEventListener("input", filterFlights);
        document.getElementById("statusFilter").addEventListener("change", filterFlights);
        document.getElementById("organisationFilter").addEventListener("change", filterFlights);
    });
</script>';

// INCLUDE HEADER & TEMPLATE
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../layouts/template.php';
