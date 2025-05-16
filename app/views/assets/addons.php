<?php
// Start sessie veilig
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Laad benodigde bestanden
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../functions.php';

// Haal data op van de API
$apiBaseUrl = "http://devserv01.holdingthedrones.com:4539";
$addOnsUrl = "$apiBaseUrl/addOns"; // Controleer of dit de juiste endpoint is

// Haal add-ons op
$addOnsResponse = file_get_contents($addOnsUrl);
$addOns = $addOnsResponse ? json_decode($addOnsResponse, true) : [];

// Stel variabelen in voor template.php
$showHeader = 1;
$userName = $_SESSION['user']['first_name'] ?? 'Onbekend';
$org = isset($organisation) ? $organisation : 'Organisatie A';
$headTitle = "Add-ons";
$gobackUrl = 0;
$rightAttributes = 0;

// Body content met dynamische data
$bodyContent = "
    <div class='h-[83.5vh] bg-gray-100 shadow-md rounded-tl-xl w-13/15'>
        <!-- Navigatie en Actieknop -->
        <div class='p-8 bg-white flex justify-between items-center border-b border-gray-200'>
            <div class='flex space-x-4 text-sm font-medium'>
                <a href='drones.php' class='text-gray-600 hover:text-gray-900'>Drones</a>
                <a href='teams.php' class='text-gray-600 hover:text-gray-900'>Teams</a>
                <a href='employees.php' class='text-black'>Personeel</a>
                <a href='addons.php' class='text-gray-600 hover:text-gray-900 border-b-2 border-black pb-2'>Add-ons</a>
            </div>
            <button class='bg-black text-white px-4 py-2 rounded-lg hover:bg-gray-800 transition-colors'>
                + Nieuw item
            </button>
        </div>

        <!-- Main Content of the page -->
        <div class='p-6 overflow-y-auto max-h-[calc(90vh-200px)]'>
            <div class='bg-white rounded-lg shadow overflow-hidden'>
                <div class='p-6 border-b border-gray-200 flex justify-between items-center'>
                    <h2 class='text-xl font-semibold text-gray-800'>Add-ons</h2>
                </div>
                <div class='overflow-x-auto'>
                    <table class='w-full'>
                        <thead class='bg-gray-200 text-sm'>
                            <tr>
                                <th class='p-4 text-left text-gray-600 font-medium'>Naam</th>
                                <th class='p-4 text-left text-gray-600 font-medium'>Model</th>
                                <th class='p-4 text-left text-gray-600 font-medium'>Serienummer</th>
                                <th class='p-4 text-left text-gray-600 font-medium'>Status</th>
                                <th class='p-4 text-left text-gray-600 font-medium'>Acties</th>
                            </tr>
                        </thead>
                        <tbody class='divide-y divide-gray-200 text-sm'>";

// Loop door de add-ons en genereer dynamische rijen
foreach ($addOns as $addOn) {
    // Stel de statuskleur in op basis van de status
    $statusClass = match ($addOn['DFPPADD_Status'] ?? 'Onbekend') {
        'Actief' => 'bg-green-100 text-green-800',
        'Nieuw' => 'bg-blue-100 text-blue-800',
        'Onderhoud' => 'bg-yellow-100 text-yellow-800',
        'Inactief' => 'bg-red-100 text-red-800',
        default => 'bg-gray-100 text-gray-800'
    };

    $bodyContent .= "
                            <tr class='hover:bg-gray-50 transition'>
                                <td class='p-4 text-gray-800'>" . htmlspecialchars($addOn['DFPPADD_Name'] ?? 'N/A') . "</td>
                                <td class='p-4 text-gray-600'>" . htmlspecialchars($addOn['DFPPADD_Model'] ?? 'N/A') . "</td>
                                <td class='p-4 text-gray-600'>" . htmlspecialchars($addOn['DFPPADD_SerialNumber'] ?? 'N/A') . "</td>
                                <td class='p-4'>
                                    <span class='$statusClass px-3 py-1 rounded-full text-sm font-medium'>" . htmlspecialchars($addOn['DFPPADD_Status'] ?? 'Onbekend') . "</span>
                                </td>
                                <td class='p-4 text-gray-600'>
                                    <a href='edit.php?id=" . htmlspecialchars($addOn['DFPPADD_Id'] ?? '') . "' class='text-gray-600 hover:text-gray-800 transition mr-2'>
                                        <i class='fa-solid fa-pencil'></i>
                                    </a>
                                </td>
                            </tr>";
}

$bodyContent .= "
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
";

// Inclusie van header en template
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../layouts/template.php';
