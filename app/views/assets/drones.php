<?php
// /var/www/public/frontend/pages/assets/drones.php (of jouw pad)

// Start sessie veilig
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Laad benodigde bestanden
require_once __DIR__ . '/../../../config/config.php'; // Pas pad aan indien nodig
require_once __DIR__ . '/../../../functions.php';   // Pas pad aan indien nodig

// --- API Data Ophalen ---
// Definieer API_BASE_URL in config.php of hardcode tijdelijk
$apiBaseUrl = defined('API_BASE_URL') ? API_BASE_URL : "http://devserv01.holdingthedrones.com:4539";
$dronesUrl = "$apiBaseUrl/drones"; // Zorg dat dit de correcte endpoint is

$dronesResponse = @file_get_contents($dronesUrl); // Gebruik @ om waarschuwingen te onderdrukken als API niet bereikbaar is
$drones = $dronesResponse ? json_decode($dronesResponse, true) : [];
if (json_last_error() !== JSON_ERROR_NONE && $dronesResponse) {
    // Log de error of toon een melding aan de gebruiker dat de data niet correct is
    error_log("JSON Decode Error for drones: " . json_last_error_msg() . " | Response: " . $dronesResponse);
    $drones = []; // Zet op lege array bij fout
}

// Stel variabelen in voor template.php
$showHeader = 1;
$userName = $_SESSION['user']['first_name'] ?? 'Onbekend';
// $org = $_SESSION['user']['organisation_name'] ?? 'Standaard Organisatie'; // Als je organisatie naam wilt tonen
$headTitle = "Drone Inventaris"; // Aangepaste titel
$gobackUrl = 0;
$rightAttributes = 0;

// Body content met dynamische data en modal
$bodyContent = "
    <style>
        /* Specifieke Modal Styling (kan in globale CSS als het vaker wordt gebruikt) */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6); /* Donkerdere overlay */
            display: none; /* Standaard verborgen */
            align-items: center;
            justify-content: center;
            z-index: 1050; /* Hoger dan de rest */
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
        }
        .modal-overlay.active {
            display: flex;
            opacity: 1;
        }
        .modal-content {
            background-color: white;
            padding: 2rem; /* Tailwind p-8 */
            border-radius: 0.5rem; /* Tailwind rounded-lg */
            width: 90%;
            max-width: 600px; /* Maximale breedte modal */
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04); /* Tailwind shadow-xl */
            position: relative;
            transform: translateY(-20px) scale(0.95);
            opacity: 0;
            transition: transform 0.3s ease-out, opacity 0.3s ease-out;
        }
        .modal-overlay.active .modal-content {
            transform: translateY(0) scale(1);
            opacity: 1;
        }
        .modal-close-btn {
            position: absolute;
            top: 1rem; /* Tailwind top-4 */
            right: 1rem; /* Tailwind right-4 */
            font-size: 1.5rem; /* Tailwind text-2xl */
            color: #9ca3af; /* Tailwind text-gray-400 */
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.25rem;
            line-height: 1;
        }
        .modal-close-btn:hover {
            color: #6b7280; /* Tailwind text-gray-500 */
        }
        .modal-content h3 {
            font-size: 1.25rem; /* Tailwind text-xl */
            font-weight: 600; /* Tailwind font-semibold */
            margin-bottom: 1.5rem; /* Tailwind mb-6 */
            color: #1f2937; /* Tailwind text-gray-800 */
        }
        /* Stijlen voor formuliervelden binnen modal, als nodig (Tailwind wordt al gebruikt) */
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; }
    </style>

    <div class='h-full bg-gray-100 shadow-md rounded-tl-xl w-full flex flex-col'>
        <!-- Navigatie en Actieknop -->
        <div class='p-6 bg-white flex justify-between items-center border-b border-gray-200 flex-shrink-0'>
            <div class='flex space-x-6 text-sm font-medium'>
                <a href='drones.php' class='text-gray-900 border-b-2 border-black pb-2'>Drones</a>
                <a href='teams.php' class='text-gray-600 hover:text-gray-900'>Teams</a>
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
                                <th class='px-6 py-3 text-left'>Model</th>
                                <th class='px-6 py-3 text-left'>Serienummer</th>
                                <th class='px-6 py-3 text-left'>Laatste Inspectie</th>
                                <th class='px-6 py-3 text-left'>Volgende Kalibratie</th>
                                <th class='px-6 py-3 text-center'>Status</th>
                                <th class='px-6 py-3 text-left'>Acties</th>
                            </tr>
                        </thead>
                        <tbody class='divide-y divide-gray-200 text-sm'>";

if (!empty($drones) && is_array($drones)) {
    foreach ($drones as $drone) {
        $lastInspection = (!empty($drone['DFPPDRO_LastInspection']) && $drone['DFPPDRO_LastInspection'] !== '0000-00-00 00:00:00') ? (new DateTime($drone['DFPPDRO_LastInspection']))->format('d-m-Y') : 'N/A';
        $nextCalibration = (!empty($drone['DFPPDRO_NextCalibration']) && $drone['DFPPDRO_NextCalibration'] !== '0000-00-00 00:00:00') ? (new DateTime($drone['DFPPDRO_NextCalibration']))->format('d-m-Y') : 'N/A';

        $status = htmlspecialchars($drone['DFPPDRO_Status'] ?? 'Onbekend', ENT_QUOTES, 'UTF-8');
        $statusClass = match (strtolower($status)) {
            'actief' => 'bg-green-100 text-green-800',
            'onderhoud' => 'bg-yellow-100 text-yellow-800',
            'inactief' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800'
        };

        $bodyContent .= "
                                <tr class='hover:bg-gray-50 transition'>
                                    <td class='px-6 py-4 whitespace-nowrap text-gray-800 font-medium'>" . htmlspecialchars($drone['DFPPDRO_Model'] ?? 'N/A', ENT_QUOTES, 'UTF-8') . "</td>
                                    <td class='px-6 py-4 whitespace-nowrap text-gray-600'>" . htmlspecialchars($drone['DFPPDRO_SerialNumber'] ?? 'N/A', ENT_QUOTES, 'UTF-8') . "</td>
                                    <td class='px-6 py-4 whitespace-nowrap text-gray-600'>" . $lastInspection . "</td>
                                    <td class='px-6 py-4 whitespace-nowrap text-gray-600'>" . $nextCalibration . "</td>
                                    <td class='px-6 py-4 whitespace-nowrap text-center'>
                                        <span class='{$statusClass} px-3 py-1 rounded-full text-xs font-semibold'>" . $status . "</span>
                                    </td>
                                    <td class='px-6 py-4 whitespace-nowrap text-gray-600'>
                                        <a href='edit_drone.php?id=" . htmlspecialchars($drone['DFPPDRO_Id'] ?? '', ENT_QUOTES, 'UTF-8') . "' class='text-blue-600 hover:text-blue-800 transition mr-3' title='Bewerk drone'>
                                            <i class='fa-solid fa-pencil'></i>
                                        </a>
                                        <a href='view_drone.php?id=" . htmlspecialchars($drone['DFPPDRO_Id'] ?? '', ENT_QUOTES, 'UTF-8') . "' class='text-gray-600 hover:text-gray-800 transition' title='Details drone'>
                                            <i class='fa-solid fa-eye'></i>
                                        </a>
                                    </td>
                                </tr>";
    }
} else {
    $bodyContent .= "<tr><td colspan='6' class='text-center text-gray-500 py-10'>Geen drones gevonden of data kon niet worden geladen.</td></tr>";
}

$bodyContent .= "
                        </tbody>
                    </table>
                </div>
                 <!-- Paginering (optioneel) -->
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
            <form id='addDroneForm' action='save_drone.php' method='POST'> <!-- Pas action aan naar je PHP script -->
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
                 // Optioneel: reset formulier
                 document.getElementById('addDroneForm')?.reset();
            }
        }

        // Sluit modal als buiten de content geklikt wordt
        if (addDroneModal) {
            addDroneModal.addEventListener('click', function(event) {
                if (event.target === addDroneModal) { // Check if click is on the overlay itself
                    closeDroneModal();
                }
            });
        }

        // Optioneel: formulier validatie en submit via JavaScript/AJAX als je geen page reload wilt
        // document.getElementById('addDroneForm')?.addEventListener('submit', function(event) {
        //     event.preventDefault();
        //     // Voer hier AJAX call uit om data op te slaan
        //     console.log('Formulier gesubmit (simulatie)');
        //     // Bij succes: closeDroneModal(); en update tabel (moeilijker zonder full JS framework)
        //     // Anders toon foutmelding
        // });
    </script>
";

// Inclusie van header en template
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../layouts/template.php';
