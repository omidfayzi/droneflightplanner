<?php
// /var/www/public/frontend/pages/assets/teamManagement.php

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../functions.php';

$apiBaseUrl = "http://devserv01.holdingthedrones.com:4539";
$teamsUrl = "$apiBaseUrl/teams";
$teamsResponse = @file_get_contents($teamsUrl);
$teams = $teamsResponse ? json_decode($teamsResponse, true) : [];
if (isset($teams['data'])) $teams = $teams['data'];

// Verzamel ALLE kolomnamen uit alle teams
$kolomSet = [];
foreach ($teams as $team) {
    foreach ($team as $key => $value) {
        $kolomSet[$key] = true;
    }
}
$kolommen = array_keys($kolomSet);

$showHeader = 1;
$userName = $_SESSION['user']['first_name'] ?? 'Onbekend';
$org = isset($organisation) ? $organisation : 'Holding the Drones';
$headTitle = "Teams Overzicht";
$gobackUrl = 0;
$rightAttributes = 0;

// Body content
$bodyContent = "
    <div class='h-[83.5vh] bg-gray-100 shadow-md rounded-tl-xl w-13/15'>
        <!-- Navigatie -->
        <div class='p-8 bg-white flex justify-between items-center border-b border-gray-200'>
            <div class='flex space-x-4 text-sm font-medium'>
                <a href='drones.php' class='text-gray-600 hover:text-gray-900'>Drones</a>
                <a href='teamManagement.php' class='text-black border-b-2 border-black pb-2'>Organisaties</a>
                <a href='employees.php' class='text-gray-600 hover:text-gray-900'>Personeel</a>
                <a href='addons.php' class='text-gray-600 hover:text-gray-900'>Overige Assets</a>
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
                        <!-- Teamleider filter eventueel dynamisch genereren -->
                    </div>
                </div>
                <div class='overflow-x-auto'>
                    <table class='w-full'>
                        <thead class='bg-gray-200 text-sm'>
                            <tr>";

// Dynamisch alle kolommen tonen als header
foreach ($kolommen as $kolom) {
    $bodyContent .= "<th class='p-4 text-left text-gray-600 font-medium'>" . htmlspecialchars($kolom) . "</th>";
}
// Altijd actieskolom
$bodyContent .= "<th class='p-4 text-left text-gray-600 font-medium'>Acties</th>";
$bodyContent .= "</tr>
                        </thead>
                        <tbody class='divide-y divide-gray-200 text-sm'>";

// Alle rijen dynamisch
if (!empty($teams) && is_array($teams)) {
    foreach ($teams as $team) {
        $bodyContent .= "<tr class='hover:bg-gray-50 transition'>";
        foreach ($kolommen as $kolom) {
            $waarde = array_key_exists($kolom, $team) ? $team[$kolom] : '';
            if (is_bool($waarde)) $waarde = $waarde ? 'Ja' : 'Nee';
            $bodyContent .= "<td class='p-4 text-gray-600'>" . htmlspecialchars((string)$waarde) . "</td>";
        }
        // Acties — als teamId/DFPPSTM_Id/id bestaat, anders disabled
        $id = $team['teamId'] ?? $team['DFPPSTM_Id'] ?? $team['id'] ?? '';
        $disabledClass = $id ? "" : "opacity-50 pointer-events-none";
        $beheerLink = $id ? "../employees.php?team=" . urlencode($id) : "#";
        $viewLink = $id ? "view_team.php?id=" . urlencode($id) : "#";
        $editLink = $id ? "edit_team.php?id=" . urlencode($id) : "#";
        $bodyContent .= "<td class='p-4 text-gray-600 flex space-x-2'>
            <a href='$beheerLink' class='hover:text-blue-700 $disabledClass' title='Teambeheer'><i class='fa-solid fa-users'></i></a>
            <a href='$viewLink' class='hover:text-blue-700 $disabledClass' title='Bekijken'><i class='fa-solid fa-eye'></i></a>
            <a href='$editLink' class='hover:text-blue-700 $disabledClass' title='Bewerken'><i class='fa-solid fa-pencil'></i></a>
        </td>";
        $bodyContent .= "</tr>";
    }
} else {
    $bodyContent .= "<tr><td colspan='" . (count($kolommen) + 1) . "' class='text-center text-gray-500 py-10'>Geen teams gevonden of data kon niet worden geladen.</td></tr>";
}

$bodyContent .= "
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
";

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../layouts/template.php';
