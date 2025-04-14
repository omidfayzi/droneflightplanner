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
$usersUrl = "$apiBaseUrl/resources/users";

// Probeer de personeelsgegevens op te halen
$usersResponse = file_get_contents($usersUrl);
$users = $usersResponse ? json_decode($usersResponse, true) : [];

// Stel variabelen in voor template.php
$showHeader = 1;
$userName = $_SESSION['user']['first_name'] ?? 'Onbekend';
$org = isset($organisation) ? $organisation : 'Organisatie A';
$headTitle = "Personeelsbeheer";
$gobackUrl = 0;
$rightAttributes = 0;

// Body content met dynamische data
$bodyContent = "
    <div class='h-[83.5vh] bg-gray-100 shadow-md rounded-tl-xl w-13/15'>
        <!-- Navigatie -->
        <div class='p-8 bg-white flex justify-between items-center border-b border-gray-200'>
            <div class='flex space-x-4 text-sm font-medium'>
                <a href='drones.php' class='text-gray-600 hover:text-gray-900'>Drones</a>
                <a href='teams.php' class='text-gray-600 hover:text-gray-900'>Teams</a>
                <a href='personeel.php' class='text-black border-b-2 border-black pb-2'>Personeel</a>
                <a href='addons.php' class='text-gray-600 hover:text-gray-900'>Add-ons</a>
            </div>
        </div>

        <!-- Hoofdinhoud -->
        <div class='p-6 overflow-y-auto max-h-[calc(90vh-200px)]'>
            <div class='bg-white rounded-lg shadow overflow-hidden'>
                <div class='p-6 border-b border-gray-200 flex justify-between items-center'>
                    <h2 class='text-xl font-semibold text-gray-800'>Personeelsleden van {$org}</h2>
                    <div class='flex space-x-4'>
                        <div class='relative'>
                            <select id='roleFilter' class='border border-gray-300 rounded-lg px-4 py-2 text-gray-600 focus:outline-none pr-8'>
                                <option value=''>Filter: Alle rollen</option>
                                <option value='Hoofd piloot'>Hoofd piloot</option>
                                <option value='Drone Operator'>Drone Operator</option>
                                <option value='Developer'>Developer</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class='overflow-x-auto'>
                    <table class='w-full'>
                        <thead class='bg-gray-200 text-sm'>
                            <tr>
                                <th class='p-4 text-left text-gray-600 font-medium'>Naam</th>
                                <th class='p-4 text-left text-gray-600 font-medium'>Rol</th>
                                <th class='p-4 text-left text-gray-600 font-medium'>Licentie</th>
                                <th class='p-4 text-left text-gray-600 font-medium'>Sinds</th>
                                <th class='p-4 text-left text-gray-600 font-medium'>Acties</th>
                            </tr>
                        </thead>
                        <tbody class='divide-y divide-gray-200 text-sm'>";

// Loop door de personeelsgegevens om tabelrijen dynamisch te genereren
foreach ($users as $user) {
    // Combineer voor- en achternaam
    $fullName = htmlspecialchars($user['DFPPUSR_FirstName'] ?? '') . ' ' . htmlspecialchars($user['DFPPUSR_LastName'] ?? '');

    // Formateer de 'Sinds' datum naar d-m-Y
    $sinceDate = $user['DFPPUSR_Since'] ? (new DateTime($user['DFPPUSR_Since']))->format('d-m-Y') : 'N/A';

    $bodyContent .= "
                            <tr class='hover:bg-gray-50 transition'>
                                <td class='p-4 text-gray-800'>$fullName</td>
                                <td class='p-4 text-gray-600'>" . htmlspecialchars($user['DFPPUSR_Role'] ?? 'N/A') . "</td>
                                <td class='p-4 text-gray-600'>" . htmlspecialchars($user['DFPPUSR_License'] ?? 'N/A') . "</td>
                                <td class='p-4 text-gray-600'>$sinceDate</td>
                                <td class='p-4 text-gray-300'>Alleen lezen</td>
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
