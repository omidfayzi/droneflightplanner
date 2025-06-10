<?php
// /var/www/public/frontend/pages/assets/drones.php

// Start sessie veilig
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Laad benodigde bestanden
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../functions.php';

// --- API Data Ophalen ---
$apiBaseUrl = defined('API_BASE_URL') ? API_BASE_URL : "http://devserv01.holdingthedrones.com:4539";
$dronesUrl = "$apiBaseUrl/drones";

$dronesResponse = @file_get_contents($dronesUrl);
$drones = $dronesResponse ? json_decode($dronesResponse, true) : [];
if (json_last_error() !== JSON_ERROR_NONE && $dronesResponse) {
    error_log("JSON Decode Error for drones: " . json_last_error_msg() . " | Response: " . $dronesResponse);
    $drones = [];
}

// Variabelen voor template
$showHeader = 1;
$userName = $_SESSION['user']['first_name'] ?? 'Onbekend';
$headTitle = "Drone Inventaris";
$gobackUrl = 0;
$rightAttributes = 0;

// Start bodyContent
$bodyContent = "
    <style>
        .modal-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background-color: rgba(0,0,0,0.6);
            display: none; align-items: center; justify-content: center;
            z-index: 1050; opacity: 0; transition: opacity 0.3s ease-in-out;
        }
        .modal-overlay.active { display: flex; opacity: 1; }
        .modal-content {
            background-color: white; padding: 2rem; border-radius: 0.5rem;
            width: 90%; max-width: 600px;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);
            position: relative; transform: translateY(-20px) scale(0.95);
            opacity: 0; transition: transform 0.3s ease-out, opacity 0.3s ease-out;
        }
        .modal-overlay.active .modal-content { transform: translateY(0) scale(1); opacity: 1; }
        .modal-close-btn {
            position: absolute; top: 1rem; right: 1rem; font-size: 1.5rem;
            color: #9ca3af; background: none; border: none; cursor: pointer; padding: 0.25rem; line-height: 1;
        }
        .modal-close-btn:hover { color: #6b7280; }
        .modal-content h3 { font-size: 1.25rem; font-weight: 600; margin-bottom: 1.5rem; color: #1f2937; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; }
    </style>

    <div class='h-full bg-gray-100 shadow-md rounded-tl-xl w-full flex flex-col'>
        <!-- Navigatie en Actieknop -->
        <div class='p-6 bg-white flex justify-between items-center border-b border-gray-200 flex-shrink-0'>
            <div class='flex space-x-6 text-sm font-medium'>
                <a href='drones.php' class='text-gray-900 border-b-2 border-black pb-2'>Drones</a>
                <a href='employees.php' class='text-gray-600 hover:text-gray-900'>Personeel</a>
                <a href='addons.php' class='text-gray-600 hover:text-gray-900'>Add-ons</a>
            </div>
            <button onclick='openDroneModal()' class='bg-black text-white px-4 py-2 rounded-lg hover:bg-gray-800 transition-colors text-sm flex items-center'>
                <i class='fa-solid fa-plus mr-2'></i>Nieuwe Drone
            </button>
        </div>

        <!-- Hoofdinhoud -->
        <div class='p-6 overflow-y-auto flex-grow'>
            <div class='bg-white rounded-lg shadow overflow-hidden'>
                <div class='p-6 border-b border-gray-200 flex justify-between items-center'>
                    <h2 class='text-xl font-semibold text-gray-800'>Drone Inventaris</h2>
                    <div class='relative'>
                        <select class='border border-gray-300 rounded-lg px-4 py-2 text-sm text-gray-600 focus:outline-none focus:ring-1 focus:ring-blue-500 pr-8 appearance-none'>
                            <option>Filter: Alle statussen</option>
                            <option value='Actief'>Actief</option>
                            <option value='Onderhoud'>Onderhoud</option>
                            <option value='Inactief'>Inactief</option>
                        </select>
                        <i class='fa-solid fa-chevron-down absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 pointer-events-none'></i>
                    </div>
                </div>
                <div class='overflow-x-auto'>
                    <table class='w-full'>
                        <thead class='bg-gray-50 text-xs uppercase text-gray-700'>
                            <tr>
                                <th class='px-6 py-3 text-left'>Drone ID</th>
                                <th class='px-6 py-3 text-left'>In-Huis ID</th>
                                <th class='px-6 py-3 text-left'>Model</th>
                                <th class='px-6 py-3 text-left'>Serienummer</th>
                                <th class='px-6 py-3 text-left'>Fabrikant</th>
                                <th class='px-6 py-3 text-left'>Verzekering</th>
                                <th class='px-6 py-3 text-left'>Verz. geldig tot</th>
                                <th class='px-6 py-3 text-left'>Registratie Autoriteit</th>
                                <th class='px-6 py-3 text-left'>Certificaat</th>
                                <th class='px-6 py-3 text-left'>Laatste onderhoud</th>
                                <th class='px-6 py-3 text-left'>Volgende onderhoud</th>
                                <th class='px-6 py-3 text-center'>Status</th>
                                <th class='px-6 py-3 text-left'>Aankoopdatum</th>
                                <th class='px-6 py-3 text-left'>Acties</th>
                            </tr>
                        </thead>
                        <tbody class='divide-y divide-gray-200 text-sm'>";
if (!empty($drones) && is_array($drones)) {
    foreach ($drones as $drone) {
        $laatsteOnderhoud = (!empty($drone['laatsteOnderhoud']) && $drone['laatsteOnderhoud'] !== '0000-00-00') ? (new DateTime($drone['laatsteOnderhoud']))->format('d-m-Y') : 'N/B';
        $volgendeOnderhoud = (!empty($drone['volgendeOnderhoud']) && $drone['volgendeOnderhoud'] !== '0000-00-00') ? (new DateTime($drone['volgendeOnderhoud']))->format('d-m-Y') : 'N/B';

        $status = ($drone['isActive'] ?? 1) ? 'Actief' : 'Inactief';
        $statusClass = match (strtolower($status)) {
            'actief' => 'bg-green-100 text-green-800',
            'onderhoud' => 'bg-yellow-100 text-yellow-800',
            'inactief' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800'
        };

        $bodyContent .= "
            <tr class='hover:bg-gray-50 transition'>
                <td class='px-6 py-4 whitespace-nowrap text-gray-600'>" . htmlspecialchars($drone['droneId'] ?? '', ENT_QUOTES, 'UTF-8') . "</td>
                <td class='px-6 py-4 whitespace-nowrap text-gray-600'>" . htmlspecialchars($drone['inHuisId'] ?? '', ENT_QUOTES, 'UTF-8') . "</td>
                <td class='px-6 py-4 whitespace-nowrap text-gray-800 font-medium'>" . htmlspecialchars($drone['model'] ?? '', ENT_QUOTES, 'UTF-8') . "</td>
                <td class='px-6 py-4 whitespace-nowrap text-gray-600'>" . htmlspecialchars($drone['serienummer'] ?? '', ENT_QUOTES, 'UTF-8') . "</td>
                <td class='px-6 py-4 whitespace-nowrap text-gray-600'>" . htmlspecialchars($drone['fabrikant'] ?? '', ENT_QUOTES, 'UTF-8') . "</td>
                <td class='px-6 py-4 whitespace-nowrap text-gray-600'>" . htmlspecialchars($drone['verzekering'] ?? '', ENT_QUOTES, 'UTF-8') . "</td>
                <td class='px-6 py-4 whitespace-nowrap text-gray-600'>" . htmlspecialchars($drone['verzekeringGeldigTot'] ?? '', ENT_QUOTES, 'UTF-8') . "</td>
                <td class='px-6 py-4 whitespace-nowrap text-gray-600'>" . htmlspecialchars($drone['registratieAutoriteit'] ?? '', ENT_QUOTES, 'UTF-8') . "</td>
                <td class='px-6 py-4 whitespace-nowrap text-gray-600'>" . htmlspecialchars($drone['certificaatLuchtwaardigheid'] ?? '', ENT_QUOTES, 'UTF-8') . "</td>
                <td class='px-6 py-4 whitespace-nowrap text-gray-600'>" . $laatsteOnderhoud . "</td>
                <td class='px-6 py-4 whitespace-nowrap text-gray-600'>" . $volgendeOnderhoud . "</td>
                <td class='px-6 py-4 whitespace-nowrap text-center'>
                    <span class='{$statusClass} px-3 py-1 rounded-full text-xs font-semibold'>" . $status . "</span>
                </td>
                <td class='px-6 py-4 whitespace-nowrap text-gray-600'>" . htmlspecialchars($drone['aankoopDatum'] ?? '', ENT_QUOTES, 'UTF-8') . "</td>
                <td class='px-6 py-4 whitespace-nowrap text-gray-600'>
                    <a href='edit_drone.php?id=" . htmlspecialchars($drone['droneId'] ?? '', ENT_QUOTES, 'UTF-8') . "' class='text-blue-600 hover:text-blue-800 transition mr-3' title='Bewerk drone'>
                        <i class='fa-solid fa-pencil'></i>
                    </a>
                    <a href='view_drone.php?id=" . htmlspecialchars($drone['droneId'] ?? '', ENT_QUOTES, 'UTF-8') . "' class='text-gray-600 hover:text-gray-800 transition' title='Details drone'>
                        <i class='fa-solid fa-eye'></i>
                    </a>
                </td>
            </tr>
        ";
    }
} else {
    $bodyContent .= "<tr><td colspan='14' class='text-center text-gray-500 py-10'>Geen drones gevonden of data kon niet worden geladen.</td></tr>";
}
$bodyContent .= "
                        </tbody>
                    </table>
                </div>
                 <div class='p-4 border-t border-gray-200 flex justify-between items-center text-sm'>
                    <span>Toont 1-" . count($drones) . " van " . count($drones) . " drones</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal voor Nieuwe Drone Toevoegen -->
    <div id='addDroneModal' class='modal-overlay'>
        <div class='modal-content'>
            <button class='modal-close-btn' onclick='closeDroneModal()'>×</button>
            <h3>Nieuwe Drone Toevoegen</h3>
            <form id='addDroneForm' action='save_drone.php' method='POST'>
                <div class='form-grid'>
                    <div class='form-group'>
                        <label for='drone_model' class='block text-sm font-medium text-gray-700 mb-1'>Model <span class='text-red-500'>*</span></label>
                        <input type='text' name='drone_model' id='drone_model' class='mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm' required>
                    </div>
                    <div class='form-group'>
                        <label for='drone_serial' class='block text-sm font-medium text-gray-700 mb-1'>Serienummer <span class='text-red-500'>*</span></label>
                        <input type='text' name='drone_serial' id='drone_serial' class='mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm' required>
                    </div>
                    <div class='form-group'>
                        <label for='drone_manufacturer' class='block text-sm font-medium text-gray-700 mb-1'>Fabrikant</label>
                        <input type='text' name='drone_manufacturer' id='drone_manufacturer' class='mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm'>
                    </div>
                    <div class='form-group'>
                        <label for='drone_purchase_date' class='block text-sm font-medium text-gray-700 mb-1'>Aankoopdatum</label>
                        <input type='date' name='drone_purchase_date' id='drone_purchase_date' class='mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm'>
                    </div>
                    <div class='form-group'>
                        <label for='drone_status' class='block text-sm font-medium text-gray-700 mb-1'>Status <span class='text-red-500'>*</span></label>
                        <select name='drone_status' id='drone_status' class='mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm' required>
                            <option value='Actief'>Actief</option>
                            <option value='Onderhoud'>Onderhoud</option>
                            <option value='Inactief'>Inactief</option>
                            <option value='Nieuw'>Nieuw</option>
                        </select>
                    </div>
                    <div class='form-group'>
                        <label for='drone_last_inspection' class='block text-sm font-medium text-gray-700 mb-1'>Laatste Inspectie</label>
                        <input type='date' name='drone_last_inspection' id='drone_last_inspection' class='mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm'>
                    </div>
                     <div class='form-group md:col-span-2'>
                         <label for='drone_next_calibration' class='block text-sm font-medium text-gray-700 mb-1'>Volgende Kalibratie</label>
                         <input type='date' name='drone_next_calibration' id='drone_next_calibration' class='mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm'>
                    </div>
                    <div class='form-group md:col-span-2'>
                         <label for='drone_notes' class='block text-sm font-medium text-gray-700 mb-1'>Notities</label>
                         <textarea name='drone_notes' id='drone_notes' rows='3' class='mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm'></textarea>
                    </div>
                </div>
                <div class='mt-6 flex justify-end space-x-3'>
                    <button type='button' onclick='closeDroneModal()' class='bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 text-sm'>Annuleren</button>
                    <button type='submit' class='bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm flex items-center'><i class='fa-solid fa-save mr-2'></i>Drone Opslaan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const addDroneModal = document.getElementById('addDroneModal');
        function openDroneModal() {
            if (addDroneModal) addDroneModal.classList.add('active');
        }
        function closeDroneModal() {
            if (addDroneModal) {
                 addDroneModal.classList.remove('active');
                 document.getElementById('addDroneForm')?.reset();
            }
        }
        if (addDroneModal) {
            addDroneModal.addEventListener('click', function(event) {
                if (event.target === addDroneModal) { closeDroneModal(); }
            });
        }
    </script>
";

// Inclusie van header en template
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../layouts/template.php';
