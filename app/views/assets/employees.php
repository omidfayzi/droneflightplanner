<?php
// /var/www/public/frontend/pages/assets/employees.php

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../functions.php';

$apiBaseUrl = "http://devserv01.holdingthedrones.com:4539";
$personeelUrl = "$apiBaseUrl/employees"; // <-- jouw API endpoint
$personeelResponse = @file_get_contents($personeelUrl);
$personeel = $personeelResponse ? json_decode($personeelResponse, true) : [];
if (isset($personeel['data'])) $personeel = $personeel['data'];

// Verzamel ALLE kolomnamen uit alle medewerkers
$kolomSet = [];
foreach ($personeel as $persoon) {
    foreach ($persoon as $key => $value) {
        $kolomSet[$key] = true;
    }
}
$kolommen = array_keys($kolomSet);

$showHeader = 1;
$userName = $_SESSION['user']['first_name'] ?? 'Onbekend';
$org = isset($organisation) ? $organisation : 'Holding the Drones';
$headTitle = "Personeel Overzicht";
$gobackUrl = 0;
$rightAttributes = 0;

// Body content
$bodyContent = "
    <div class='h-[83.5vh] bg-gray-100 shadow-md rounded-tl-xl w-13/15'>
        <!-- Navigatie -->
        <div class='p-8 bg-white flex justify-between items-center border-b border-gray-200'>
            <div class='flex space-x-4 text-sm font-medium'>
                <a href='drones.php' class='text-gray-600 hover:text-gray-900'>Drones</a>
                <a href='teams.php' class='text-gray-600 hover:text-gray-900'>Organisaties</a>
                <a href='employees.php' class='text-black border-b-2 border-black pb-2'>Personeel</a>
                <a href='addons.php' class='text-gray-600 hover:text-gray-900'>Overige Assets</a>
            </div>
        </div>
        <!-- Hoofdinhoud -->
        <div class='p-6 overflow-y-auto max-h-[calc(90vh-200px)]'>
            <div class='bg-white rounded-lg shadow overflow-hidden'>
                <div class='p-6 border-b border-gray-200 flex justify-between items-center'>
                    <h2 class='text-xl font-semibold text-gray-800'>{$org}</h2>
                    <div class='flex space-x-4'>
                        <input type='text' id='employeeSearch' placeholder='Zoek personeel...' class='border border-gray-300 rounded-lg px-4 py-2 text-gray-600 focus:outline-none' />
                        <select id='employeeStatusFilter' class='border border-gray-300 rounded-lg px-4 py-2 text-gray-600 focus:outline-none pr-8'>
                            <option value=''>Filter: Alle statussen</option>
                            <option value='Actief'>Actief</option>
                            <option value='Inactief'>Inactief</option>
                        </select>
                    </div>
                </div>
                <div class='overflow-x-auto'>
                    <table class='w-full'>
                        <thead class='bg-gray-200 text-sm'>
                            <tr>";

// Dynamisch alle kolommen als header
foreach ($kolommen as $kolom) {
    $bodyContent .= "<th class='p-4 text-left text-gray-600 font-medium'>" . htmlspecialchars($kolom) . "</th>";
}
// Altijd actieskolom
$bodyContent .= "<th class='p-4 text-left text-gray-600 font-medium'>Acties</th>";
$bodyContent .= "</tr>
                        </thead>
                        <tbody class='divide-y divide-gray-200 text-sm'>";

// Alle rijen dynamisch
if (!empty($personeel) && is_array($personeel)) {
    foreach ($personeel as $persoon) {
        $bodyContent .= "<tr class='hover:bg-gray-50 transition'>";
        foreach ($kolommen as $kolom) {
            $waarde = array_key_exists($kolom, $persoon) ? $persoon[$kolom] : '';
            if (is_bool($waarde)) $waarde = $waarde ? 'Ja' : 'Nee';
            $bodyContent .= "<td class='p-4 text-gray-600'>" . htmlspecialchars((string)$waarde) . "</td>";
        }
        // Acties — als personeelId/id bestaat, anders disabled
        $id = $persoon['personeelId'] ?? $persoon['id'] ?? '';
        $disabledClass = $id ? "" : "opacity-50 pointer-events-none";
        $viewLink = $id ? "view_personeel.php?id=" . urlencode($id) : "#";
        $editLink = $id ? "edit_personeel.php?id=" . urlencode($id) : "#";
        $bodyContent .= "<td class='p-4 text-gray-600 flex space-x-2'>
            <a href='$viewLink' class='hover:text-blue-700 $disabledClass' title='Bekijken'><i class='fa-solid fa-eye'></i></a>
            <a href='$editLink' class='hover:text-blue-700 $disabledClass' title='Bewerken'><i class='fa-solid fa-pencil'></i></a>
        </td>";
        $bodyContent .= "</tr>";
    }
} else {
    $bodyContent .= "<tr><td colspan='" . (count($kolommen) + 1) . "' class='text-center text-gray-500 py-10'>Geen personeel gevonden of data kon niet worden geladen.</td></tr>";
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
