<?php
// /var/www/public/frontend/pages/assets/overigeassets.php

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../functions.php';

$apiBaseUrl = "http://devserv01.holdingthedrones.com:4539";
$overigeAssetsUrl = "$apiBaseUrl/overige_assets";
$assetsResponse = @file_get_contents($overigeAssetsUrl);
$assets = $assetsResponse ? json_decode($assetsResponse, true) : [];
if (isset($assets['data'])) $assets = $assets['data'];

// 1. Verzamel ALLE mogelijke kolomnamen uit alle records
$kolomSet = [];
foreach ($assets as $asset) {
    foreach ($asset as $key => $value) {
        $kolomSet[$key] = true;
    }
}
$kolommen = array_keys($kolomSet);

$showHeader = 1;
$userName = $_SESSION['user']['first_name'] ?? 'Onbekend';
$org = isset($organisation) ? $organisation : 'Organisatie A';
$headTitle = "Overige Assets";
$gobackUrl = 0;
$rightAttributes = 0;

// Start body content
$bodyContent = "
    <div class='h-[83.5vh] bg-gray-100 shadow-md rounded-tl-xl w-13/15'>
        <div class='p-8 bg-white flex justify-between items-center border-b border-gray-200'>
            <div class='flex space-x-4 text-sm font-medium'>
                <a href='drones.php' class='text-gray-600 hover:text-gray-900'>Drones</a>
                <a href='teams.php' class='text-gray-600 hover:text-gray-900'>Organisaties</a>
                <a href='employees.php' class='text-gray-600 hover:text-gray-900'>Personeel</a>
                <a href='addons.php' class='text-black border-b-2 border-black pb-2'>Overige Assets</a>
            </div>
            <button class='bg-black text-white px-4 py-2 rounded-lg hover:bg-gray-800 transition-colors'>
                + Nieuw item
            </button>
        </div>
        <div class='p-6 overflow-y-auto max-h-[calc(90vh-200px)]'>
            <div class='bg-white rounded-lg shadow overflow-hidden'>
                <div class='p-6 border-b border-gray-200 flex justify-between items-center'>
                    <h2 class='text-xl font-semibold text-gray-800'>Overige Assets</h2>
                </div>
                <div class='overflow-x-auto'>
                    <table class='w-full'>
                        <thead class='bg-gray-200 text-sm'>
                            <tr>";

// Toon alle kolommen in de header
foreach ($kolommen as $kolom) {
    $bodyContent .= "<th class='p-4 text-left text-gray-600 font-medium'>" . htmlspecialchars($kolom) . "</th>";
}
// Altijd een kolom voor acties
$bodyContent .= "<th class='p-4 text-left text-gray-600 font-medium'>Acties</th>";
$bodyContent .= "</tr>
                        </thead>
                        <tbody class='divide-y divide-gray-200 text-sm'>";

// Toon alle rijen
if (!empty($assets) && is_array($assets)) {
    foreach ($assets as $asset) {
        $bodyContent .= "<tr class='hover:bg-gray-50 transition'>";
        foreach ($kolommen as $kolom) {
            $waarde = array_key_exists($kolom, $asset) ? $asset[$kolom] : '';
            if (is_bool($waarde)) $waarde = $waarde ? 'Ja' : 'Nee';
            $bodyContent .= "<td class='p-4 text-gray-600'>" . htmlspecialchars((string)$waarde) . "</td>";
        }
        // Acties
        $id = $asset['assetId'] ?? $asset['id'] ?? null;
        $bodyContent .= "<td class='p-4 text-gray-600 flex space-x-2'>";
        // Altijd tonen, disabled als geen id:
        $viewLink = $id ? "view_overigeasset.php?id=" . urlencode($id) : "#";
        $editLink = $id ? "edit_overigeasset.php?id=" . urlencode($id) : "#";
        $deleteLink = $id ? "deactivate_overigeasset.php?id=" . urlencode($id) : "#";
        $disabledClass = $id ? "" : "opacity-50 pointer-events-none";
        $bodyContent .= "
            <a href='$viewLink' class='hover:text-blue-700 $disabledClass' title='Bekijken'><i class='fa-solid fa-eye'></i></a>
            <a href='$editLink' class='hover:text-blue-700 $disabledClass' title='Bewerken'><i class='fa-solid fa-pencil'></i></a>
            <a href='$deleteLink' class='hover:text-red-700 $disabledClass' title='Deactiveren'><i class='fa-solid fa-trash'></i></a>
        ";
        $bodyContent .= "</td></tr>";
    }
} else {
    $bodyContent .= "<tr><td colspan='" . (count($kolommen) + 1) . "' class='text-center text-gray-500 py-10'>Geen assets gevonden of data kon niet worden geladen.</td></tr>";
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
