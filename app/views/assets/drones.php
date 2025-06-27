<?php
// /var/www/public/frontend/pages/assets/drones.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../functions.php';

$apiBaseUrl = defined('API_BASE_URL') ? API_BASE_URL : "http://devserv01.holdingthedrones.com:4539";
$dronesUrl = "$apiBaseUrl/drones";

$dronesResponse = @file_get_contents($dronesUrl);
$drones = $dronesResponse ? json_decode($dronesResponse, true) : [];

if (json_last_error() !== JSON_ERROR_NONE && $dronesResponse) {
    error_log("JSON Decode Error for drones: " . json_last_error_msg() . " | Response: " . $dronesResponse);
    $drones = [];
}

if (isset($drones['data']) && is_array($drones['data'])) {
    $drones = $drones['data'];
}

$uniqueStatuses = [];
$fabrikanten = [];
if (!empty($drones) && is_array($drones)) {
    foreach ($drones as $drone) {
        if (!empty($drone['status']) && !in_array($drone['status'], $uniqueStatuses)) {
            $uniqueStatuses[] = $drone['status'];
        }
        if (!empty($drone['fabrikant']) && !in_array($drone['fabrikant'], $fabrikanten)) {
            $fabrikanten[] = $drone['fabrikant'];
        }
    }
    sort($uniqueStatuses);
    sort($fabrikanten);
}

$kolomDefinities = [
    'droneId' => 'Drone ID',
    'in_huis_id' => 'In-Huis ID',
    'droneNaam' => 'Model',
    'naam' => 'Naam/Omschrijving',
    'serienummer' => 'Serienummer',
    'fabrikant' => 'Fabrikant',
    'verzekering' => 'Verzekering',
    'verzekering_geldig_tot' => 'Verzekering Geldig Tot',
    'registratie_autoriteit' => 'Registratie Autoriteit',
    'certificaat' => 'Certificaat',
    'laatste_onderhoud' => 'Laatste Onderhoud',
    'volgende_onderhoud' => 'Volgende Onderhoud',
    'status' => 'Status',
    'aankoopdatum' => 'Aankoopdatum',
    'droneCategorieId' => 'Cat. ID',
    'easaKlasseId' => 'EASA Klasse ID',
    'organisatieId' => 'Org. ID',
];

function formatDate($date)
{
    if (empty($date) || $date === '0000-00-00' || $date === '0000-00-00 00:00:00') {
        return 'N/B';
    }
    try {
        $dateTime = new DateTime($date);
        return $dateTime->format('d-m-Y');
    } catch (Exception $e) {
        error_log("Date formatting error for: " . $date . " - " . $e->getMessage());
        return htmlspecialchars($date);
    }
}

function getStatusClass($status)
{
    $statusLower = strtolower($status);
    return match ($statusLower) {
        'actief', 'online' => 'bg-green-100 text-green-800',
        'in onderhoud', 'onderhoud' => 'bg-yellow-100 text-yellow-800',
        'afgekeurd', 'inactief', 'offline' => 'bg-red-100 text-red-800',
        'verkocht' => 'bg-blue-100 text-blue-800',
        default => 'bg-gray-100 text-gray-800'
    };
}

$showHeader = 1;
$userName = $_SESSION['user']['first_name'] ?? 'Onbekend';
$headTitle = "Drone Inventaris";
$gobackUrl = 0;
$rightAttributes = 0;

$bodyContent = "
    <style>
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(17, 24, 39, 0.70);
            z-index: 50;
            display: none;
            align-items: center;
            justify-content: center;
            transition: opacity 0.25s ease-in-out;
            opacity: 0;
        }
        .modal-overlay.active {
            display: flex;
            opacity: 1;
        }
        .modal-content {
            background: #fff;
            border-radius: 1rem;
            max-width: 580px;
            width: 100%;
            box-shadow: 0 8px 32px rgba(31, 41, 55, 0.18);
            padding: 2.5rem 2rem 1.5rem 2rem;
            position: relative;
            animation: modalIn 0.18s cubic-bezier(.4, 0, .2, 1);
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
            transition: color 0.15s ease-in-out;
            line-height: 1;
            z-index: 10;
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
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }
        .form-group { margin-bottom: 1rem; }
        .form-group label {
            display: block;
            margin-bottom: 0.4rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            padding: 0.6rem 0.8rem;
            width: 100%;
            font-size: 0.875rem;
            color: #1f2937;
            box-shadow: 0 1px 0px rgba(0, 0, 0, 0.03) inset;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }
        .form-group textarea { resize: vertical; }
        .form-group .required-star { color: #ef4444; margin-left: 4px;}

        .filter-bar {
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            border-radius: 0.75rem;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
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
            background-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' class='h-6 w-6' fill='none' viewBox='0 0 24 24' stroke='%239ca3af'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z' /%3E%3C/svg%3E\");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 1rem;
            min-width: 280px;
        }
        
        .drone-detail-modal {
            max-width: 800px;
        }
        .drone-detail-header {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .drone-icon {
            font-size: 2rem;
            color: #3b82f6;
            margin-right: 1rem;
        }
        .drone-detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        .detail-group {
            margin-bottom: 1.2rem;
        }
        .detail-label {
            font-weight: 600;
            color: #4b5563;
            margin-bottom: 0.3rem;
            font-size: 0.9rem;
        }
        .detail-value {
            font-size: 1rem;
            color: #1f2937;
            padding: 0.5rem 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .detail-value.status {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .drone-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            justify-content: flex-end;
        }
        
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

    <div class='h-full bg-gray-100 shadow-md rounded-tl-xl w-full flex flex-col'>
        <div class='p-6 bg-white flex justify-between items-center border-b border-gray-200 flex-shrink-0'>
            <div class='flex space-x-6 text-sm font-medium'>
                <a href='drones.php' class='text-gray-900 border-b-2 border-black pb-2'>Drones</a>
                <a href='employees.php' class='text-gray-600 hover:text-gray-900'>Personeel</a>
                <a href='addons.php' class='text-gray-600 hover:text-gray-900'>Add-ons</a>
            </div>
            <button onclick='openAddDroneModal()' class='bg-gradient-to-r from-blue-500 to-blue-700 text-white px-4 py-2 rounded-lg hover:bg-gray-800 transition-colors text-sm flex items-center'>
                <i class='fa-solid fa-plus mr-2'></i>Nieuwe Drone
            </button>
        </div>

        <div class='px-6 pt-4'>
            <div class='filter-bar'>
                <div class='filter-group'>
                    <span class='filter-label'>Status:</span>
                    <select id='statusFilter' class='filter-select'>
                        <option value=''>Alle statussen</option>";
foreach ($uniqueStatuses as $status) {
    $bodyContent .= "<option value='" . htmlspecialchars(strtolower($status)) . "'>" . htmlspecialchars(ucfirst($status)) . "</option>";
}
$bodyContent .= "
                    </select>
                </div>
                
                <div class='filter-group'>
                    <span class='filter-label'>Fabrikant:</span>
                    <select id='manufacturerFilter' class='filter-select'>
                        <option value=''>Alle fabrikanten</option>";
foreach ($fabrikanten as $fabrikant) {
    $bodyContent .= "<option value='" . htmlspecialchars(strtolower($fabrikant)) . "'>" . htmlspecialchars($fabrikant) . "</option>";
}
$bodyContent .= "
                    </select>
                </div>
                
                <div class='filter-group flex-grow'>
                    <input id='searchInput' type='text' placeholder='Zoek drones...' class='filter-search'>
                </div>
            </div>
        </div>

        <div class='p-6 overflow-y-auto flex-grow'>
            <div class='bg-white rounded-lg shadow overflow-hidden'>
                <div class='p-6 border-b border-gray-200 flex justify-between items-center'>
                    <h2 class='text-xl font-semibold text-gray-800'>Drone Inventaris</h2>
                </div>
                <div class='overflow-x-auto'>
                    <table id='dronesTable' class='w-full'>
                        <thead class='bg-gray-50 text-xs uppercase text-gray-700'>
                            <tr>";
foreach ($kolomDefinities as $key => $header) {
    $isStatusCol = ($key === 'status');
    $isManufacturerCol = ($key === 'fabrikant');

    $headerAttributes = '';
    if ($isStatusCol) $headerAttributes .= ' data-filterable="status"';
    if ($isManufacturerCol) $headerAttributes .= ' data-filterable="manufacturer"';

    $dataKeyForFilter = match ($key) {
        'status' => 'status',
        'fabrikant' => 'fabrikant',
        default => null
    };

    $headerAttributes = '';
    if ($dataKeyForFilter) {
        $headerAttributes = ' data-filter-key="' . $dataKeyForFilter . '"';
    }

    $bodyContent .= "<th class='px-4 py-3 text-left'" . $headerAttributes . ">" . htmlspecialchars($header) . "</th>";
}
$bodyContent .= "<th class='px-4 py-3 text-left'>Acties</th>";
$bodyContent .= "</tr>
                        </thead>
                        <tbody class='divide-y divide-gray-200 text-sm'>";

if (!empty($drones) && is_array($drones)) {
    foreach ($drones as $drone) {
        $bodyContent .= "<tr class='hover:bg-gray-50 transition'";

        if (isset($drone['status'])) {
            $bodyContent .= " data-status='" . htmlspecialchars(strtolower($drone['status'])) . "'";
        }
        if (isset($drone['fabrikant'])) {
            $bodyContent .= " data-manufacturer='" . htmlspecialchars(strtolower($drone['fabrikant'])) . "'";
        }

        $bodyContent .= ">";
        foreach ($kolomDefinities as $dbKey => $headerName) {
            $cellValue = $drone[$dbKey] ?? '';

            if ($dbKey === 'laatste_onderhoud' || $dbKey === 'volgende_onderhoud' || $dbKey === 'verzekering_geldig_tot' || $dbKey === 'aankoopdatum') {
                $cellValue = formatDate($cellValue);
            } elseif ($dbKey === 'status') {
                $statusClass = getStatusClass($cellValue);
                $cellValue = "<span class='" . $statusClass . " px-3 py-1 rounded-full text-xs font-semibold'>" . htmlspecialchars(ucfirst($cellValue)) . "</span>";
            } elseif (is_string($cellValue) && $cellValue === '') {
                $cellValue = 'N/B';
            }

            $bodyContent .= "<td class='px-4 py-3 whitespace-nowrap'>" . $cellValue . "</td>";
        }

        $droneId = htmlspecialchars($drone['droneId'] ?? 'unknown', ENT_QUOTES, 'UTF-8');
        $bodyContent .= "<td class='px-4 py-3 whitespace-nowrap text-gray-600'>
                            <button onclick='openDroneDetailModal(" . json_encode($drone) . ")' class='text-blue-600 hover:text-blue-800 transition mr-3' title='Details drone'>
                                <i class='fa-solid fa-info-circle'></i>
                            </button>
                            <button onclick='openEditDroneModal(" . json_encode($drone) . ")' class='text-green-600 hover:text-green-800 transition' title='Bewerk drone'>
                                <i class='fa-solid fa-edit'></i>
                            </button>
                        </td>";

        $bodyContent .= "</tr>";
    }
} else {
    $bodyContent .= "<tr><td colspan='" . (count($kolomDefinities) + 1) . "' class='text-center text-gray-500 py-10'>Geen drones gevonden of data kon niet worden geladen.</td></tr>";
}
$bodyContent .= "
                        </tbody>
                    </table>
                </div>
                <div class='p-4 border-t border-gray-200 flex justify-between items-center text-sm'>
                    <span>Toont " . (($drones) ? ("1-" . count($drones)) : "0") . " van " . count($drones) . " drones</span>
                </div>
            </div>
        </div>
    </div>

    <div id='addDroneModal' class='modal-overlay' role='dialog' aria-modal='true' aria-labelledby='addDroneModalTitle'>
        <div class='modal-content'>
            <button class='modal-close-btn' aria-label='Sluit modal' onclick='closeAddDroneModal()'>×</button>
            <h3 id='addDroneModalTitle' class='flex items-center gap-2'>
              <i class=\"fa-solid fa-drone text-white mr-2\"></i> 
              Nieuwe Drone Toevoegen
            </h3>
            <form id='addDroneForm' action='save_drone.php' method='POST' class='space-y-4'>
                <div class='form-grid'>
                    <div class='form-group'>
                        <label for='drone_droneNaam'>Model <span class='required-star'>*</span></label>
                        <input type='text' id='drone_droneNaam' name='droneNaam' required placeholder='bv. DJI Mavic 3'>
                    </div>
                    <div class='form-group'>
                        <label for='drone_naam'>Naam/Omschrijving</label>
                        <input type='text' id='drone_naam' name='naam' placeholder='bv. Inspectie Drone West'>
                    </div>
                    <div class='form-group'>
                        <label for='drone_serienummer'>Serienummer <span class='required-star'>*</span></label>
                        <input type='text' id='drone_serienummer' name='serienummer' required placeholder='bv. SN12345ABC'>
                    </div>
                     <div class='form-group'>
                        <label for='drone_fabrikant'>Fabrikant</label>
                        <input type='text' id='drone_fabrikant' name='fabrikant' placeholder='bv. DJI'>
                    </div>
                    <div class='form-group'>
                        <label for='drone_verzekering'>Verzekering</label>
                        <input type='text' id='drone_verzekering' name='verzekering' placeholder='bv. DronePolis'>
                    </div>
                     <div class='form-group'>
                        <label for='drone_verzekering_geldig_tot'>Verzekering Geldig Tot</label>
                        <input type='date' id='drone_verzekering_geldig_tot' name='verzekering_geldig_tot'>
                    </div>
                    <div class='form-group'>
                        <label for='drone_registratie_autoriteit'>Registratie Autoriteit</label>
                        <input type='text' id='drone_registratie_autoriteit' name='registratie_autoriteit' placeholder='bv. RDW'>
                    </div>
                    <div class='form-group'>
                        <label for='drone_certificaat'>Certificaat</label>
                        <input type='text' id='drone_certificaat' name='certificaat' placeholder='bv. NL-CERT-001'>
                    </div>
                     <div class='form-group'>
                        <label for='drone_laatste_onderhoud'>Laatste Onderhoud</label>
                        <input type='date' id='drone_laatste_onderhoud' name='laatste_onderhoud'>
                    </div>
                     <div class='form-group'>
                        <label for='drone_volgende_onderhoud'>Volgende Onderhoud</label>
                        <input type='date' id='drone_volgende_onderhoud' name='volgende_onderhoud'>
                    </div>
                    <div class='form-group'>
                        <label for='drone_status'>Status <span class='required-star'>*</span></label>
                        <select id='drone_status' name='status' required>
                            <option value=''>Selecteer status...</option>";
foreach ($uniqueStatuses as $status) {
    $bodyContent .= "<option value='" . htmlspecialchars(strtolower($status)) . "'>" . htmlspecialchars(ucfirst($status)) . "</option>";
}
$bodyContent .= "
                        </select>
                    </div>
                     <div class='form-group'>
                        <label for='drone_aankoopdatum'>Aankoopdatum</label>
                        <input type='date' id='drone_aankoopdatum' name='aankoopdatum'>
                    </div>
                    
                    <div class='form-group'>
                        <label for='drone_model_id'>Model ID</label>
                        <input type='text' id='drone_model_id' name='droneModelId' placeholder='bv. 1'>
                    </div>
                     <div class='form-group'>
                        <label for='drone_categorie_id'>Categorie ID</label>
                        <input type='text' id='drone_categorie_id' name='droneCategorieId' placeholder='bv. 1'>
                    </div>
                     <div class='form-group'>
                        <label for='drone_easa_klasse_id'>EASA Klasse ID</label>
                        <input type='text' id='drone_easa_klasse_id' name='easaKlasseId' placeholder='bv. 1'>
                    </div>
                     <div class='form-group'>
                        <label for='drone_organisatie_id'>Organisatie ID</label>
                        <input type='text' id='drone_organisatie_id' name='organisatieId' placeholder='bv. 1'>
                    </div>
                    
                    <div class='form-group col-span-full'>
                         <label for='drone_notes'>Notities</label>
                         <textarea id='drone_notes' name='notes' rows='3' placeholder='Eventuele aanvullende informatie...'></textarea>
                    </div>
                </div>
                <div class='pt-4 flex justify-end space-x-3'>
                    <button type='button' onclick='closeAddDroneModal()' class='bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 text-sm'>Annuleren</button>
                    <button type='submit' class='bg-gradient-to-r from-blue-500 to-blue-700 text-white px-4 py-2 rounded-lg hover:bg-gray-800 text-sm flex items-center'>
                        <i class='fa-solid fa-save mr-2'></i>Drone Opslaan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id='droneDetailModal' class='modal-overlay' role='dialog' aria-modal='true' aria-labelledby='droneDetailModalTitle'>
        <div class='modal-content drone-detail-modal'>
            <button class='modal-close-btn' aria-label='Sluit modal' onclick='closeDroneDetailModal()'>×</button>
            <div id='droneDetailContent'></div>
        </div>
    </div>

    <div id='editDroneModal' class='modal-overlay' role='dialog' aria-modal='true' aria-labelledby='editDroneModalTitle'>
        <div class='modal-content'>
            <button class='modal-close-btn' aria-label='Sluit modal' onclick='closeEditDroneModal()'>×</button>
            <h3 id='editDroneModalTitle' class='flex items-center gap-2'>
              <i class=\"fa-solid fa-pen text-gray-700 mr-2\"></i> 
              Drone Bewerken
            </h3>
            <form id='editDroneForm' action='update_drone.php' method='POST' class='space-y-4'>
                <input type='hidden' id='edit_droneId' name='droneId'>
                <div class='form-grid' id='editDroneFormContent'>
                </div>
                <div class='pt-4 flex justify-end space-x-3'>
                    <button type='button' onclick='closeEditDroneModal()' class='bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 text-sm'>Annuleren</button>
                    <button type='submit' class='bg-gradient-to-r from-blue-500 to-blue-700 text-white px-4 py-2 rounded-lg hover:bg-gray-800 text-sm flex items-center'>
                        <i class='fa-solid fa-save mr-2'></i>Wijzigingen Opslaan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const dronesData = " . json_encode($drones, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ";
        
        const addDroneModal = document.getElementById('addDroneModal');
        const droneDetailModal = document.getElementById('droneDetailModal');
        const editDroneModal = document.getElementById('editDroneModal');
        
        function openAddDroneModal() {
            if (addDroneModal) addDroneModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        function closeAddDroneModal() {
            if (addDroneModal) {
                 addDroneModal.classList.remove('active');
                 document.getElementById('addDroneForm')?.reset();
                 document.body.style.overflow = '';
            }
        }

        function openDroneDetailModal(drone) {
            const modalContent = document.getElementById('droneDetailContent');
            if (modalContent) {
                let content = '<div class=\"drone-detail-header\">';
                content += '<i class=\"fa-solid fa-drone drone-icon\"></i>';
                content += '<h3 class=\"text-xl font-semibold\">' + (drone.droneNaam || 'Onbekend model') + '</h3>';
                content += '</div>';
                
                content += '<div class=\"drone-detail-grid\">';
                content += '<div>';
                content += '<div class=\"detail-group\"><div class=\"detail-label\">Serienummer</div><div class=\"detail-value\">' + (drone.serienummer || 'N/B') + '</div></div>';
                content += '<div class=\"detail-group\"><div class=\"detail-label\">Fabrikant</div><div class=\"detail-value\">' + (drone.fabrikant || 'N/B') + '</div></div>';
                content += '<div class=\"detail-group\"><div class=\"detail-label\">Verzekering</div><div class=\"detail-value\">' + (drone.verzekering || 'N/B') + '</div></div>';
                content += '<div class=\"detail-group\"><div class=\"detail-label\">Verzekering geldig tot</div><div class=\"detail-value\">' + formatDroneDate(drone.verzekering_geldig_tot) + '</div></div>';
                content += '<div class=\"detail-group\"><div class=\"detail-label\">Registratie autoriteit</div><div class=\"detail-value\">' + (drone.registratie_autoriteit || 'N/B') + '</div></div>';
                content += '<div class=\"detail-group\"><div class=\"detail-label\">Certificaat</div><div class=\"detail-value\">' + (drone.certificaat || 'N/B') + '</div></div>';
                content += '</div>';
                
                content += '<div>';
                content += '<div class=\"detail-group\"><div class=\"detail-label\">Laatste onderhoud</div><div class=\"detail-value\">' + formatDroneDate(drone.laatste_onderhoud) + '</div></div>';
                content += '<div class=\"detail-group\"><div class=\"detail-label\">Volgende onderhoud</div><div class=\"detail-value\">' + formatDroneDate(drone.volgende_onderhoud) + '</div></div>';
                
                let statusClass = getStatusClass(drone.status);
                content += '<div class=\"detail-group\"><div class=\"detail-label\">Status</div><div class=\"detail-value\"><span class=\"detail-value status ' + statusClass + '\">' + (drone.status || 'Onbekend') + '</span></div></div>';
                
                content += '<div class=\"detail-group\"><div class=\"detail-label\">Aankoopdatum</div><div class=\"detail-value\">' + formatDroneDate(drone.aankoopdatum) + '</div></div>';
                content += '<div class=\"detail-group\"><div class=\"detail-label\">Categorie ID</div><div class=\"detail-value\">' + (drone.droneCategorieId || 'N/B') + '</div></div>';
                content += '<div class=\"detail-group\"><div class=\"detail-label\">Organisatie ID</div><div class=\"detail-value\">' + (drone.organisatieId || 'N/B') + '</div></div>';
                content += '</div></div>';
                
                content += '<div class=\"detail-group col-span-full\"><div class=\"detail-label\">Notities</div><div class=\"detail-value\">' + (drone.notes || 'Geen notities beschikbaar') + '</div></div>';

                modalContent.innerHTML = content;
            }
            
            if (droneDetailModal) {
                droneDetailModal.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        }
        
        function closeDroneDetailModal() {
            if (droneDetailModal) {
                droneDetailModal.classList.remove('active');
                document.body.style.overflow = '';
            }
        }
        
        function openEditDroneModal(drone) {
            const formContent = document.getElementById('editDroneFormContent');
            const droneIdField = document.getElementById('edit_droneId');
            
            if (droneIdField) droneIdField.value = drone.droneId || '';
            
            if (formContent) {
                let formHtml = '';
                formHtml += '<div class=\"form-group\"><label for=\"edit_droneNaam\">Model <span class=\"required-star\">*</span></label><input type=\"text\" id=\"edit_droneNaam\" name=\"droneNaam\" value=\"' + (drone.droneNaam || '') + '\" required></div>';
                formHtml += '<div class=\"form-group\"><label for=\"edit_naam\">Naam/Omschrijving</label><input type=\"text\" id=\"edit_naam\" name=\"naam\" value=\"' + (drone.naam || '') + '\"></div>';
                formHtml += '<div class=\"form-group\"><label for=\"edit_serienummer\">Serienummer <span class=\"required-star\">*</span></label><input type=\"text\" id=\"edit_serienummer\" name=\"serienummer\" value=\"' + (drone.serienummer || '') + '\" required></div>';
                formHtml += '<div class=\"form-group\"><label for=\"edit_fabrikant\">Fabrikant</label><input type=\"text\" id=\"edit_fabrikant\" name=\"fabrikant\" value=\"' + (drone.fabrikant || '') + '\"></div>';
                formHtml += '<div class=\"form-group\"><label for=\"edit_verzekering\">Verzekering</label><input type=\"text\" id=\"edit_verzekering\" name=\"verzekering\" value=\"' + (drone.verzekering || '') + '\"></div>';
                formHtml += '<div class=\"form-group\"><label for=\"edit_verzekering_geldig_tot\">Verzekering Geldig Tot</label><input type=\"date\" id=\"edit_verzekering_geldig_tot\" name=\"verzekering_geldig_tot\" value=\"' + formatDateForInput(drone.verzekering_geldig_tot) + '\"></div>';
                formHtml += '<div class=\"form-group\"><label for=\"edit_registratie_autoriteit\">Registratie Autoriteit</label><input type=\"text\" id=\"edit_registratie_autoriteit\" name=\"registratie_autoriteit\" value=\"' + (drone.registratie_autoriteit || '') + '\"></div>';
                formHtml += '<div class=\"form-group\"><label for=\"edit_certificaat\">Certificaat</label><input type=\"text\" id=\"edit_certificaat\" name=\"certificaat\" value=\"' + (drone.certificaat || '') + '\"></div>';
                formHtml += '<div class=\"form-group\"><label for=\"edit_laatste_onderhoud\">Laatste Onderhoud</label><input type=\"date\" id=\"edit_laatste_onderhoud\" name=\"laatste_onderhoud\" value=\"' + formatDateForInput(drone.laatste_onderhoud) + '\"></div>';
                formHtml += '<div class=\"form-group\"><label for=\"edit_volgende_onderhoud\">Volgende Onderhoud</label><input type=\"date\" id=\"edit_volgende_onderhoud\" name=\"volgende_onderhoud\" value=\"' + formatDateForInput(drone.volgende_onderhoud) + '\"></div>';
                
                formHtml += '<div class=\"form-group\"><label for=\"edit_status\">Status <span class=\"required-star\">*</span></label><select id=\"edit_status\" name=\"status\" required>';
                formHtml += '<option value=\"\">Selecteer status...</option>';
                
                const statuses = " . json_encode($uniqueStatuses) . ";
                statuses.forEach(function(status) {
                    const selected = status === drone.status ? 'selected' : '';
                    formHtml += '<option value=\"' + status + '\" ' + selected + '>' + status + '</option>';
                });
                
                formHtml += '</select></div>';
                formHtml += '<div class=\"form-group\"><label for=\"edit_aankoopdatum\">Aankoopdatum</label><input type=\"date\" id=\"edit_aankoopdatum\" name=\"aankoopdatum\" value=\"' + formatDateForInput(drone.aankoopdatum) + '\"></div>';
                formHtml += '<div class=\"form-group col-span-full\"><label for=\"edit_notes\">Notities</label><textarea id=\"edit_notes\" name=\"notes\" rows=\"3\">' + (drone.notes || '') + '</textarea></div>';
                
                formContent.innerHTML = formHtml;
            }
            
            if (editDroneModal) {
                editDroneModal.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        }
        
        function closeEditDroneModal() {
            if (editDroneModal) {
                editDroneModal.classList.remove('active');
                document.body.style.overflow = '';
            }
        }
        
        function formatDroneDate(dateString) {
            if (!dateString || dateString === '0000-00-00' || dateString === '0000-00-00 00:00:00') return 'N/B';
            try {
                const date = new Date(dateString);
                return date.toLocaleDateString('nl-NL');
            } catch (e) {
                return dateString;
            }
        }
        
        function formatDateForInput(dateString) {
            if (!dateString || dateString === '0000-00-00' || dateString === '0000-00-00 00:00:00') return '';
            try {
                const date = new Date(dateString);
                return date.toISOString().split('T')[0];
            } catch (e) {
                return '';
            }
        }
        
        function getStatusClass(status) {
            if (!status) return 'bg-gray-100 text-gray-800';
            const statusLower = status.toLowerCase();
            if (statusLower.includes('actief') || statusLower.includes('online')) return 'bg-green-100 text-green-800';
            if (statusLower.includes('onderhoud')) return 'bg-yellow-100 text-yellow-800';
            if (statusLower.includes('afgekeurd') || statusLower.includes('inactief') || statusLower.includes('offline')) return 'bg-red-100 text-red-800';
            if (statusLower.includes('verkocht')) return 'bg-blue-100 text-blue-800';
            return 'bg-gray-100 text-gray-800';
        }

        function filterDrones() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const statusFilter = document.getElementById('statusFilter').value.toLowerCase();
            const manufacturerFilter = document.getElementById('manufacturerFilter').value.toLowerCase();
            
            const table = document.getElementById('dronesTable');
            if (!table) return;
            
            const tbody = table.querySelector('tbody');
            if (!tbody) return;

            const rows = tbody.querySelectorAll('tr');
            
            rows.forEach(row => {
                const rowText = row.textContent.toLowerCase();
                const status = row.dataset.status || '';
                const manufacturer = row.dataset.manufacturer || '';

                const matchesSearch = rowText.includes(searchTerm);
                const matchesStatus = statusFilter === '' || status === statusFilter;
                const matchesManufacturer = manufacturerFilter === '' || manufacturer === manufacturerFilter;
                
                row.style.display = (matchesSearch && matchesStatus && matchesManufacturer) ? '' : 'none';
            });
        }
        
        document.getElementById('searchInput')?.addEventListener('input', filterDrones);
        document.getElementById('statusFilter')?.addEventListener('change', filterDrones);
        document.getElementById('manufacturerFilter')?.addEventListener('change', filterDrones);

        document.getElementById('editDroneForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            this.submit();
        });
        
        if (addDroneModal) {
            addDroneModal.addEventListener('click', function(event) { 
                if (event.target === addDroneModal) closeAddDroneModal(); 
            });
        }
        
        if (droneDetailModal) {
            droneDetailModal.addEventListener('click', function(event) { 
                if (event.target === droneDetailModal) closeDroneDetailModal(); 
            });
        }
        
        if (editDroneModal) {
            editDroneModal.addEventListener('click', function(event) { 
                if (event.target === editDroneModal) closeEditDroneModal(); 
            });
        }
    </script>
";

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../layouts/template.php';
