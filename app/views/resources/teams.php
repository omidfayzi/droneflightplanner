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
$teamsUrl = "$apiBaseUrl/resources/teams";

// Probeer de teamgegevens op te halen
$teamsResponse = file_get_contents($teamsUrl);
$teams = $teamsResponse ? json_decode($teamsResponse, true) : [];

// Stel variabelen in voor template.php
$showHeader = 1;
$userName = $_SESSION['user']['first_name'] ?? 'Onbekend';
$org = isset($organisation) ? $organisation : 'Holding the Drones';
$headTitle = "Teams Overzicht";
$gobackUrl = 0; // Geen terug-knop
$rightAttributes = 0; // Standaard header-attributen

// Body content met dynamische data
$bodyContent = "
    <div class='h-[83.5vh] bg-gray-100 shadow-md rounded-tl-xl w-13/15'>
        <!-- Navigatie -->
        <div class='p-8 bg-white flex justify-between items-center border-b border-gray-200'>
            <div class='flex space-x-4 text-sm font-medium'>
                <a href='drones.php' class='text-gray-600 hover:text-gray-900'>Drones</a>
                <a href='teams.php' class='text-black border-b-2 border-black pb-2'>Teams</a>
                <a href='personeel.php' class='text-gray-600 hover:text-gray-900'>Personeel</a>
                <a href='addons.php' class='text-gray-600 hover:text-gray-900'>Add-ons</a>
            </div>
        </div>

        <!-- Hoofdinhoud -->
        <div class='p-6 overflow-y-auto max-h-[calc(90vh-200px)]'>
            <div class='bg-white rounded-lg shadow overflow-hidden'>
                <div class='p-6 border-b border-gray-200 flex justify-between items-center'>
                    <h2 class='text-xl font-semibold text-gray-800'>{$org}</h2>
                    <div class='flex space-x-4'>
                        <input type='text' id='teamSearch' placeholder='Zoek team...' class='border border-gray-300 rounded-lg px-4 py-2 text-gray-600 focus:outline-none' />
                        <select id='teamStatusFilter' class='border border-gray-300 rounded-lg px-4 py-2 text-gray-600 focus:outline-none pr-8'>
                            <option value=''>Filter: Alle teams</option>
                            <option value='Actief'>Actieve teams</option>
                            <option value='Inactief'>Inactieve teams</option>
                        </select>
                        <select id='teamLeaderFilter' class='border border-gray-300 rounded-lg px-4 py-2 text-gray-600 focus:outline-none pr-8'>
                            <option value=''>Filter: Alle teamleiders</option>
                            <option value='Jan Smit'>Jan Smit</option>
                            <option value='Eva de Jong'>Eva de Jong</option>
                        </select>
                    </div>
                </div>
                <div class='overflow-x-auto'>
                    <table class='w-full'>
                        <thead class='bg-gray-200 text-sm'>
                            <tr>
                                <th class='p-4 text-left text-gray-600 font-medium'>Teamnaam</th>
                                <th class='p-4 text-left text-gray-600 font-medium'>Teamleider</th>
                                <th class='p-4 text-left text-gray-600 font-medium'>Aantal leden</th>
                                <th class='p-4 text-left text-gray-600 font-medium'>Status</th>
                                <th class='p-4 text-left text-gray-600 font-medium'>Acties</th>
                            </tr>
                        </thead>
                        <tbody class='divide-y divide-gray-200 text-sm'>";

// Loop door de teams om tabelrijen dynamisch te genereren
foreach ($teams as $team) {
    $memberCount = $team['memberCount'] ?? 'N/A'; // Aantal leden, aan te passen aan API-veld

    $bodyContent .= "
                            <tr class='hover:bg-gray-50 transition'>
                                <td class='p-4 text-gray-800'>" . htmlspecialchars($team['DFPPSTM_Name'] ?? 'N/A') . "</td>
                                <td class='p-4 text-gray-600'>" . htmlspecialchars($team['DFPPSTM_LeaderName'] ?? 'N/A') . "</td>
                                <td class='p-4 text-gray-600'>$memberCount</td>
                                <td class='p-4 text-gray-600'>" . htmlspecialchars($team['DFPPSTM_Status'] ?? 'Onbekend') . "</td>
                                <td class='p-4 text-gray-600'>
                                    <a href='../teambeheer.php?team=" . htmlspecialchars($team['DFPPSTM_Id'] ?? '') . "' class='text-blue-600 hover:text-blue-800'>Teambeheer</a>
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

// Include header en template
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../layouts/template.php';
