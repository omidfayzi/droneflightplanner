<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../functions.php';

$apiBaseUrl = defined('API_BASE_URL') ? API_BASE_URL : "http://devserv01.holdingthedrones.com:4539";
$verzekeringenUrl = "$apiBaseUrl/verzekeringen";

$verzekeringenResponse = @file_get_contents($verzekeringenUrl);
$verzekeringen = $verzekeringenResponse ? json_decode($verzekeringenResponse, true) : [];
if (isset($verzekeringen['data'])) $verzekeringen = $verzekeringen['data'];

// Kolommen dynamisch bepalen
$kolomSet = [];
foreach ($verzekeringen as $verzekering) {
    foreach ($verzekering as $key => $value) {
        $kolomSet[$key] = true;
    }
}
$kolommen = array_keys($kolomSet);

// Verzamel unieke waarden voor filters
$uniqueStatuses = [];
$uniqueMaatschappijen = [];
$uniqueTypes = [];

foreach ($verzekeringen as $verzekering) {
    if (!empty($verzekering['status']) && !in_array($verzekering['status'], $uniqueStatuses)) {
        $uniqueStatuses[] = $verzekering['status'];
    }
    if (!empty($verzekering['maatschappij']) && !in_array($verzekering['maatschappij'], $uniqueMaatschappijen)) {
        $uniqueMaatschappijen[] = $verzekering['maatschappij'];
    }
    if (!empty($verzekering['type']) && !in_array($verzekering['type'], $uniqueTypes)) {
        $uniqueTypes[] = $verzekering['type'];
    }
}
sort($uniqueStatuses);
sort($uniqueMaatschappijen);
sort($uniqueTypes);

$showHeader = 1;
$userName = $_SESSION['user']['first_name'] ?? 'Onbekend';
$headTitle = "Verzekeringen Beheer";
$gobackUrl = 0;
$rightAttributes = 0;

$bodyContent = '
<style>
    /* Stijl overgenomen van incidenten.php */
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
    
    /* Algemene styling */
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
    
    /* Verzekering-specifieke stijlen */
    .status-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.85rem;
        font-weight: 500;
    }
    .status-actief { background-color: #dcfce7; color: #166534; }
    .status-verlopen { background-color: #fee2e2; color: #b91c1c; }
    .status-in-behandeling { background-color: #fffbeb; color: #b45309; }
</style>

<div class="h-full bg-gray-100 shadow-md rounded-tl-xl w-full flex flex-col">
    <div class="p-6 bg-white flex justify-between items-center border-b border-gray-200 flex-shrink-0">
        <div class="flex space-x-6 text-sm font-medium">
            <a href="drones.php" class="text-gray-600 hover:text-gray-900">Drones</a>
            <a href="employees.php" class="text-gray-600 hover:text-gray-900">Personeel</a>
            <a href="addons.php" class="text-gray-600 hover:text-gray-900">Add-ons</a>
            <a href="verzekeringen.php" class="text-gray-900 border-b-2 border-black pb-2">Verzekeringen</a>
        </div>
        <button onclick="openAddVerzekeringModal()" class="bg-gradient-to-r from-blue-600 to-blue-800 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors text-sm flex items-center gap-2">
            <i class="fa-solid fa-plus-circle"></i> Nieuwe Verzekering
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
                <span class="filter-label">Maatschappij:</span>
                <select id="maatschappijFilter" class="filter-select">
                    <option value="">Alle maatschappijen</option>';
foreach ($uniqueMaatschappijen as $maatschappij) {
    $bodyContent .= '<option value="' . htmlspecialchars(strtolower($maatschappij)) . '">' . htmlspecialchars($maatschappij) . '</option>';
}
$bodyContent .= '
                </select>
            </div>
            
            <div class="filter-group">
                <span class="filter-label">Type:</span>
                <select id="typeFilter" class="filter-select">
                    <option value="">Alle types</option>';
foreach ($uniqueTypes as $type) {
    $bodyContent .= '<option value="' . htmlspecialchars(strtolower($type)) . '">' . htmlspecialchars($type) . '</option>';
}
$bodyContent .= '
                </select>
            </div>
            
            <div class="filter-group flex-grow">
                <input id="searchInput" type="text" placeholder="Zoek verzekering..." class="filter-search">
            </div>
        </div>
    </div>
    
    <div class="p-6 overflow-y-auto flex-grow">
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table id="verzekeringenTable" class="w-full">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                        <tr>';
foreach ($kolommen as $kolom) {
    $bodyContent .= '<th class="px-4 py-3 text-left">' . htmlspecialchars($kolom) . '</th>';
}
$bodyContent .= '<th class="px-4 py-3 text-left">Acties</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 text-sm">';
if (!empty($verzekeringen) && is_array($verzekeringen)) {
    foreach ($verzekeringen as $verzekering) {
        $bodyContent .= '<tr class="hover:bg-gray-50 transition"';
        if (isset($verzekering['status'])) {
            $bodyContent .= ' data-status="' . htmlspecialchars(strtolower($verzekering['status'])) . '"';
        }
        if (isset($verzekering['maatschappij'])) {
            $bodyContent .= ' data-maatschappij="' . htmlspecialchars(strtolower($verzekering['maatschappij'])) . '"';
        }
        if (isset($verzekering['type'])) {
            $bodyContent .= ' data-type="' . htmlspecialchars(strtolower($verzekering['type'])) . '"';
        }
        $bodyContent .= '>';

        foreach ($kolommen as $kolom) {
            $waarde = $verzekering[$kolom] ?? '';

            // Speciale opmaak voor bepaalde velden
            if ($kolom === 'status') {
                $statusClass = 'status-' . str_replace(' ', '-', strtolower($waarde));
                $waarde = '<span class="status-badge ' . $statusClass . '">' . htmlspecialchars($waarde) . '</span>';
            } elseif ($kolom === 'premie' && is_numeric($waarde)) {
                $waarde = '€' . number_format((float)$waarde, 2, ',', '.');
            } elseif (in_array($kolom, ['startdatum', 'einddatum', 'verzekering_geldig_tot', 'aankoopdatum'])) {
                if ($waarde && $waarde !== '0000-00-00') {
                    try {
                        $date = new DateTime($waarde);
                        $waarde = $date->format('d-m-Y');
                    } catch (Exception $e) {
                        // Behoud oorspronkelijke waarde bij fout
                    }
                } else {
                    $waarde = 'N/B';
                }
            }

            $bodyContent .= '<td class="px-4 py-3 whitespace-nowrap">' . $waarde . '</td>';
        }

        $bodyContent .= '<td class="px-4 py-3 whitespace-nowrap">
                <button onclick="openVerzekeringDetailModal(' . htmlspecialchars(json_encode($verzekering, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) . ')" class="text-blue-600 hover:text-blue-800 mr-2" title="Details">
                    <i class="fa-regular fa-file-lines"></i>
                </button>
                <button onclick="openEditVerzekeringModal(' . htmlspecialchars(json_encode($verzekering, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) . ')" class="text-green-600 hover:text-green-800 mr-2" title="Bewerken">
                    <i class="fa-solid fa-edit"></i>
                </button>
            </td>';
        $bodyContent .= '</tr>';
    }
} else {
    $bodyContent .= '<tr><td colspan="' . (count($kolommen) + 1) . '" class="text-center text-gray-500 py-10">Geen verzekeringen gevonden of data kon niet worden geladen.</td></tr>';
}
$bodyContent .= '
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nieuwe Verzekering -->
<div id="addVerzekeringModal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="addVerzekeringTitle">
    <div class="modal-content">
        <button class="modal-close-btn" aria-label="Sluit modal" onclick="closeAddVerzekeringModal()">&times;</button>
        <h3 id="addVerzekeringTitle" class="flex items-center gap-2">
            <i class="fa-solid fa-file-contract text-blue-500"></i> 
            Nieuwe Verzekering Toevoegen
        </h3>
        <form id="addVerzekeringForm" action="save_verzekering.php" method="POST" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="form-group">
                    <label for="add_verzekering_naam">Naam <span class="text-red-500">*</span></label>
                    <input type="text" name="naam" id="add_verzekering_naam" class="w-full p-2 border border-gray-300 rounded-md shadow-sm" required>
                </div>
                
                <div class="form-group">
                    <label for="add_verzekering_type">Type <span class="text-red-500">*</span></label>
                    <select name="type" id="add_verzekering_type" class="w-full p-2 border border-gray-300 rounded-md shadow-sm" required>
                        <option value="">Selecteer type...</option>
                        <option value="WA">WA (Wettelijke Aansprakelijkheid)</option>
                        <option value="Allrisk">Allrisk</option>
                        <option value="Casco">Casco</option>
                        <option value="Bedrijfsmatig gebruik">Bedrijfsmatig gebruik</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="add_verzekering_maatschappij">Maatschappij <span class="text-red-500">*</span></label>
                    <select name="maatschappij" id="add_verzekering_maatschappij" class="w-full p-2 border border-gray-300 rounded-md shadow-sm" required>
                        <option value="">Selecteer maatschappij...</option>
                        <option value="Unive">Unive</option>
                        <option value="Aon">Aon</option>
                        <option value="Allianz">Allianz</option>
                        <option value="Achmea">Achmea</option>
                        <option value="HDD Verzekeringen">HDD Verzekeringen</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="add_verzekering_polisnummer">Polisnummer <span class="text-red-500">*</span></label>
                    <input type="text" name="polisnummer" id="add_verzekering_polisnummer" class="w-full p-2 border border-gray-300 rounded-md shadow-sm" required>
                </div>
                
                <div class="form-group">
                    <label for="add_verzekering_premie">Premie (€) <span class="text-red-500">*</span></label>
                    <input type="number" name="premie" id="add_verzekering_premie" step="0.01" min="0" class="w-full p-2 border border-gray-300 rounded-md shadow-sm" required>
                </div>
                
                <div class="form-group">
                    <label for="add_verzekering_dekking">Dekking</label>
                    <input type="text" name="dekking" id="add_verzekering_dekking" class="w-full p-2 border border-gray-300 rounded-md shadow-sm" placeholder="bv. €1.000.000 aansprakelijkheid">
                </div>
                
                <div class="form-group">
                    <label for="add_verzekering_startdatum">Startdatum <span class="text-red-500">*</span></label>
                    <input type="date" name="startdatum" id="add_verzekering_startdatum" class="w-full p-2 border border-gray-300 rounded-md shadow-sm" required>
                </div>
                
                <div class="form-group">
                    <label for="add_verzekering_einddatum">Einddatum <span class="text-red_500">*</span></label>
                    <input type="date" name="einddatum" id="add_verzekering_einddatum" class="w-full p-2 border border-gray-300 rounded-md shadow-sm" required>
                </div>
                
                <div class="form-group">
                    <label for="add_verzekering_status">Status <span class="text-red_500">*</span></label>
                    <select name="status" id="add_verzekering_status" class="w-full p-2 border border-gray-300 rounded-md shadow-sm" required>
                        <option value="Actief">Actief</option>
                        <option value="Verlopen">Verlopen</option>
                        <option value="In behandeling">In behandeling</option>
                        <option value="Geweigerd">Geweigerd</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="add_verzekering_drone">Drone ID</label>
                    <input type="number" name="droneId" id="add_verzekering_drone" class="w-full p-2 border border-gray-300 rounded-md shadow-sm" placeholder="bv. 123">
                </div>
                
                <div class="form-group">
                    <label for="add_verzekering_org">Organisatie ID</label>
                    <input type="number" name="organisatieId" id="add_verzekering_org" class="w-full p-2 border border-gray-300 rounded-md shadow-sm" placeholder="bv. 1">
                </div>
            </div>
            
            <div class="form-group">
                <label for="add_verzekering_notes">Notities</label>
                <textarea name="notes" id="add_verzekering_notes" rows="3" class="w-full p-2 border border-gray-300 rounded-md shadow-sm" placeholder="Bijzonderheden of voorwaarden..."></textarea>
            </div>
            
            <div class="pt-4 flex justify-end space-x-3">
                <button type="button" onclick="closeAddVerzekeringModal()" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 text-sm">Annuleren</button>
                <button type="submit" class="bg-gradient-to-r from-blue-600 to-blue-800 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm flex items-center gap-2">
                    <i class="fa-solid fa-save"></i> Verzekering Opslaan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Details Verzekering -->
<div id="verzekeringDetailModal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="verzekeringDetailTitle">
    <div class="modal-content">
        <button class="modal-close-btn" aria-label="Sluit details" onclick="closeVerzekeringDetailModal()">&times;</button>
        <h3 id="verzekeringDetailTitle" class="flex items-center gap-2">
            <i class="fa-solid fa-file-contract text-blue-500"></i> 
            Verzekering Details
        </h3>
        <div id="verzekeringDetailContent" class="detail-grid"></div>
    </div>
</div>

<!-- Modal Bewerk Verzekering -->
<div id="editVerzekeringModal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="editVerzekeringTitle">
    <div class="modal-content">
        <button class="modal-close-btn" aria-label="Sluit modal" onclick="closeEditVerzekeringModal()">&times;</button>
        <h3 id="editVerzekeringTitle" class="flex items-center gap-2">
            <i class="fa-solid fa-pen text-blue-500"></i> 
            Verzekering Bewerken
        </h3>
        <form id="editVerzekeringForm" action="update_verzekering.php" method="POST" class="space-y-4">
            <input type="hidden" name="verzekeringId" id="edit_verzekering_id">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="form-group">
                    <label for="edit_verzekering_naam">Naam <span class="text-red-500">*</span></label>
                    <input type="text" name="naam" id="edit_verzekering_naam" class="w-full p-2 border border-gray-300 rounded-md shadow-sm" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_verzekering_type">Type <span class="text-red-500">*</span></label>
                    <select name="type" id="edit_verzekering_type" class="w-full p-2 border border-gray-300 rounded-md shadow-sm" required>
                        <option value="WA">WA (Wettelijke Aansprakelijkheid)</option>
                        <option value="Allrisk">Allrisk</option>
                        <option value="Casco">Casco</option>
                        <option value="Bedrijfsmatig gebruik">Bedrijfsmatig gebruik</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="edit_verzekering_maatschappij">Maatschappij <span class="text-red-500">*</span></label>
                    <select name="maatschappij" id="edit_verzekering_maatschappij" class="w-full p-2 border border-gray-300 rounded-md shadow-sm" required>
                        <option value="Unive">Unive</option>
                        <option value="Aon">Aon</option>
                        <option value="Allianz">Allianz</option>
                        <option value="Achmea">Achmea</option>
                        <option value="HDD Verzekeringen">HDD Verzekeringen</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="edit_verzekering_polisnummer">Polisnummer <span class="text-red-500">*</span></label>
                    <input type="text" name="polisnummer" id="edit_verzekering_polisnummer" class="w-full p-2 border border-gray-300 rounded-md shadow-sm" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_verzekering_premie">Premie (€) <span class="text-red-500">*</span></label>
                    <input type="number" name="premie" id="edit_verzekering_premie" step="0.01" min="0" class="w-full p-2 border border-gray-300 rounded-md shadow-sm" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_verzekering_dekking">Dekking</label>
                    <input type="text" name="dekking" id="edit_verzekering_dekking" class="w-full p-2 border border-gray-300 rounded-md shadow-sm" placeholder="bv. €1.000.000 aansprakelijkheid">
                </div>
                
                <div class="form-group">
                    <label for="edit_verzekering_startdatum">Startdatum <span class="text-red-500">*</span></label>
                    <input type="date" name="startdatum" id="edit_verzekering_startdatum" class="w-full p-2 border border-gray-300 rounded-md shadow-sm" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_verzekering_einddatum">Einddatum <span class="text-red_500">*</span></label>
                    <input type="date" name="einddatum" id="edit_verzekering_einddatum" class="w-full p-2 border border-gray-300 rounded-md shadow-sm" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_verzekering_status">Status <span class="text-red_500">*</span></label>
                    <select name="status" id="edit_verzekering_status" class="w-full p-2 border border-gray-300 rounded-md shadow-sm" required>
                        <option value="Actief">Actief</option>
                        <option value="Verlopen">Verlopen</option>
                        <option value="In behandeling">In behandeling</option>
                        <option value="Geweigerd">Geweigerd</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="edit_verzekering_drone">Drone ID</label>
                    <input type="number" name="droneId" id="edit_verzekering_drone" class="w-full p-2 border border-gray-300 rounded-md shadow-sm" placeholder="bv. 123">
                </div>
                
                <div class="form-group">
                    <label for="edit_verzekering_org">Organisatie ID</label>
                    <input type="number" name="organisatieId" id="edit_verzekering_org" class="w-full p-2 border border-gray-300 rounded-md shadow-sm" placeholder="bv. 1">
                </div>
            </div>
            
            <div class="form-group">
                <label for="edit_verzekering_notes">Notities</label>
                <textarea name="notes" id="edit_verzekering_notes" rows="3" class="w-full p-2 border border-gray-300 rounded-md shadow-sm" placeholder="Bijzonderheden of voorwaarden..."></textarea>
            </div>
            
            <div class="pt-4 flex justify-end space-x-3">
                <button type="button" onclick="closeEditVerzekeringModal()" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 text-sm">Annuleren</button>
                <button type="submit" class="bg-gradient-to-r from-blue-600 to-blue-800 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm flex items-center gap-2">
                    <i class="fa-solid fa-save"></i> Wijzigingen Opslaan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Modal open/close voor verzekeringen
    const addVerzekeringModal = document.getElementById("addVerzekeringModal");
    const verzekeringDetailModal = document.getElementById("verzekeringDetailModal");
    const editVerzekeringModal = document.getElementById("editVerzekeringModal");

    function openAddVerzekeringModal() {
        if (addVerzekeringModal) addVerzekeringModal.classList.add("active");
        document.body.style.overflow = "hidden";
        
        // Stel vandaag in als standaard startdatum
        const today = new Date().toISOString().split("T")[0];
        document.getElementById("add_verzekering_startdatum").value = today;
        
        // Stel einddatum in op 1 jaar van nu
        const nextYear = new Date();
        nextYear.setFullYear(nextYear.getFullYear() + 1);
        document.getElementById("add_verzekering_einddatum").value = nextYear.toISOString().split("T")[0];
    }
    
    function closeAddVerzekeringModal() {
        if (addVerzekeringModal) {
            addVerzekeringModal.classList.remove("active");
            document.getElementById("addVerzekeringForm").reset();
            document.body.style.overflow = "";
        }
    }
    
    if (addVerzekeringModal) {
        addVerzekeringModal.addEventListener("click", (event) => { 
            if (event.target === addVerzekeringModal) closeAddVerzekeringModal(); 
        });
    }

    // Verzekering detail modal
    function openVerzekeringDetailModal(verzekeringData) {
        const modalContent = document.getElementById("verzekeringDetailContent");
        if (!modalContent || !verzekeringData) return;
        
        modalContent.innerHTML = "";
        
        const fieldsToShow = {
            "verzekeringId": "Verzekering ID",
            "naam": "Naam",
            "type": "Type",
            "maatschappij": "Maatschappij",
            "polisnummer": "Polisnummer",
            "premie": "Premie",
            "dekking": "Dekking",
            "startdatum": "Startdatum",
            "einddatum": "Einddatum",
            "status": "Status",
            "droneId": "Drone ID",
            "organisatieId": "Organisatie ID",
            "notes": "Notities"
        };

        for (const [key, label] of Object.entries(fieldsToShow)) {
            let value = verzekeringData[key] ?? "-";
            
            // Speciale verwerking voor bepaalde velden
            if (key === "premie" && value !== "-") {
                value = "€" + parseFloat(value).toFixed(2).replace(".", ",");
            } 
            else if (key === "status") {
                const statusClass = "status-" + value.toLowerCase().replace(" ", "-");
                value = `<span class="status-badge ${statusClass}">${value}</span>`;
            }
            else if (key === "startdatum" || key === "einddatum") {
                if (value && value !== "0000-00-00") {
                    try {
                        const dateObj = new Date(value);
                        value = dateObj.toLocaleDateString("nl-NL");
                    } catch (e) {}
                }
            }
            
            modalContent.innerHTML += `
                <div class="detail-group">
                    <div class="detail-label">${label}</div>
                    <div class="detail-value">${value}</div>
                </div>`;
        }
        
        if (verzekeringDetailModal) {
            verzekeringDetailModal.classList.add("active");
            document.body.style.overflow = "hidden";
        }
    }
    
    function closeVerzekeringDetailModal() {
        if (verzekeringDetailModal) {
            verzekeringDetailModal.classList.remove("active");
            document.getElementById("verzekeringDetailContent").innerHTML = "";
            document.body.style.overflow = "";
        }
    }
    
    if (verzekeringDetailModal) {
        verzekeringDetailModal.addEventListener("click", (event) => {
            if (event.target === verzekeringDetailModal) closeVerzekeringDetailModal();
        });
    }

    // Verzekering bewerken modal
    function openEditVerzekeringModal(verzekeringData) {
        if (!verzekeringData || !editVerzekeringModal) return;
        
        // Vul het formulier in met bestaande gegevens
        document.getElementById("edit_verzekering_id").value = verzekeringData.verzekeringId || "";
        document.getElementById("edit_verzekering_naam").value = verzekeringData.naam || "";
        document.getElementById("edit_verzekering_type").value = verzekeringData.type || "";
        document.getElementById("edit_verzekering_maatschappij").value = verzekeringData.maatschappij || "";
        document.getElementById("edit_verzekering_polisnummer").value = verzekeringData.polisnummer || "";
        document.getElementById("edit_verzekering_premie").value = verzekeringData.premie || "";
        document.getElementById("edit_verzekering_dekking").value = verzekeringData.dekking || "";
        document.getElementById("edit_verzekering_startdatum").value = verzekeringData.startdatum || "";
        document.getElementById("edit_verzekering_einddatum").value = verzekeringData.einddatum || "";
        document.getElementById("edit_verzekering_status").value = verzekeringData.status || "Actief";
        document.getElementById("edit_verzekering_drone").value = verzekeringData.droneId || "";
        document.getElementById("edit_verzekering_org").value = verzekeringData.organisatieId || "";
        document.getElementById("edit_verzekering_notes").value = verzekeringData.notes || "";
        
        editVerzekeringModal.classList.add("active");
        document.body.style.overflow = "hidden";
    }
    
    function closeEditVerzekeringModal() {
        if (editVerzekeringModal) {
            editVerzekeringModal.classList.remove("active");
            document.body.style.overflow = "";
        }
    }
    
    if (editVerzekeringModal) {
        editVerzekeringModal.addEventListener("click", (event) => {
            if (event.target === editVerzekeringModal) closeEditVerzekeringModal();
        });
    }

    // Filter functionaliteit
    function filterVerzekeringen() {
        const searchTerm = document.getElementById("searchInput").value.toLowerCase();
        const statusFilter = document.getElementById("statusFilter").value.toLowerCase();
        const maatschappijFilter = document.getElementById("maatschappijFilter").value.toLowerCase();
        const typeFilter = document.getElementById("typeFilter").value.toLowerCase();
        
        const rows = document.querySelectorAll("#verzekeringenTable tbody tr");
        
        rows.forEach(row => {
            const rowText = row.textContent.toLowerCase();
            const status = row.dataset.status || "";
            const maatschappij = row.dataset.maatschappij || "";
            const type = row.dataset.type || "";
            
            const matchesSearch = rowText.includes(searchTerm);
            const matchesStatus = statusFilter === "" || status === statusFilter;
            const matchesMaatschappij = maatschappijFilter === "" || maatschappij === maatschappijFilter;
            const matchesType = typeFilter === "" || type === typeFilter;
            
            row.style.display = (matchesSearch && matchesStatus && matchesMaatschappij && matchesType) ? "" : "none";
        });
    }
    
    // Event listeners voor filters
    document.addEventListener("DOMContentLoaded", () => {
        document.getElementById("searchInput").addEventListener("input", filterVerzekeringen);
        document.getElementById("statusFilter").addEventListener("change", filterVerzekeringen);
        document.getElementById("maatschappijFilter").addEventListener("change", filterVerzekeringen);
        document.getElementById("typeFilter").addEventListener("change", filterVerzekeringen);
    });
</script>';

// INCLUDE HEADER & TEMPLATE
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../layouts/template.php';
