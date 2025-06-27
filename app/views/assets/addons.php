<?php
// /var/www/public/frontend/pages/assets/overigeassets.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../functions.php';

// API Data Ophalen
$apiBaseUrl = defined('API_BASE_URL') ? API_BASE_URL : "http://devserv01.holdholdingthedrones.com:4539";
$overigeAssetsUrl = "$apiBaseUrl/overige_assets";
$assetsResponse = @file_get_contents($overigeAssetsUrl);
$assets = $assetsResponse ? json_decode($assetsResponse, true) : [];

if (isset($assets['data']) && is_array($assets['data'])) {
    $assets = $assets['data'];
}

if (json_last_error() !== JSON_ERROR_NONE && $assetsResponse) {
    error_log("JSON Decode Error for other assets: " . json_last_error_msg() . " | Response: " . $assetsResponse);
    $assets = [];
}

// Data Voorbereiding
$uniqueStatuses = [];
$uniqueTypes = [];
$uniqueCategories = [];
$uniqueDepartments = [];
$uniqueLocations = [];

if (!empty($assets) && is_array($assets)) {
    foreach ($assets as $asset) {
        if (isset($asset['status']) && !empty($asset['status']) && !in_array($asset['status'], $uniqueStatuses)) {
            $uniqueStatuses[] = $asset['status'];
        }

        $typeValue = $asset['type'] ?? $asset['assetType'] ?? null;
        if (!empty($typeValue) && !in_array($typeValue, $uniqueTypes)) {
            $uniqueTypes[] = $typeValue;
        }

        $categoryValue = $asset['categorie'] ?? $asset['category'] ?? null;
        if (!empty($categoryValue) && !in_array($categoryValue, $uniqueCategories)) {
            $uniqueCategories[] = $categoryValue;
        }

        $departmentValue = $asset['department'] ?? $asset['afdeling'] ?? null;
        if (!empty($departmentValue) && !in_array($departmentValue, $uniqueDepartments)) {
            $uniqueDepartments[] = $departmentValue;
        }

        $locationValue = $asset['locatie'] ?? $asset['location'] ?? null;
        if (!empty($locationValue) && !in_array($locationValue, $uniqueLocations)) {
            $uniqueLocations[] = $locationValue;
        }
    }

    sort($uniqueStatuses);
    sort($uniqueTypes);
    sort($uniqueCategories);
    sort($uniqueDepartments);
    sort($uniqueLocations);
}

// Kolomdefinities zoals in drones.php
$kolomDefinities = [
    'assetId' => 'Asset ID',
    'naam' => 'Naam',
    'type' => 'Type',
    'categorie' => 'Categorie',
    'serienummer' => 'Serienummer',
    'status' => 'Status',
    'afdeling' => 'Afdeling',
    'locatie' => 'Locatie',
    'aanschafdatum' => 'Aanschafdatum',
    'notities' => 'Notities',
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
    if (!$status) return 'bg-gray-100 text-gray-800';
    $statusLower = strtolower($status);
    if ($statusLower . includes('actief') || $statusLower . includes('beschikbaar')) return 'bg-green-100 text-green-800';
    if ($statusLower . includes('onderhoud')) return 'bg-yellow-100 text-yellow-800';
    if ($statusLower . includes('defect') || $statusLower . includes('verwijderd')) return 'bg-red-100 text-red-800';
    if ($statusLower . includes('gereserveerd')) return 'bg-blue-100 text-blue-800';
    return 'bg-gray-100 text-gray-800';
}

$showHeader = 1;
$userName = $_SESSION['user']['first_name'] ?? 'Onbekend';
$org = isset($organisation) ? $organisation : 'Holding the Drones Assets';
$headTitle = "Overige Assets Inventaris";
$gobackUrl = 0;
$rightAttributes = 0;

// Body content
$bodyContent = "
    <style>
        /* Consistent met drones.php styling */
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

        /* Detail View Styling */
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
        .detail-value.status {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        /* Pagina styling */
        .h-full {
            height: 100%;
        }
        .bg-gray-100 {
            background-color: #f3f4f6;
        }
        .shadow-md {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .rounded-tl-xl {
            border-top-left-radius: 0.75rem;
        }
        .w-full {
            width: 100%;
        }
        .flex {
            display: flex;
        }
        .flex-col {
            flex-direction: column;
        }
        .p-6 {
            padding: 1.5rem;
        }
        .bg-white {
            background-color: #fff;
        }
        .border-b {
            border-bottom-width: 1px;
        }
        .border-gray-200 {
            border-color: #e5e7eb;
        }
        .flex-shrink-0 {
            flex-shrink: 0;
        }
        .space-x-6 > * + * {
            margin-left: 1.5rem;
        }
        .text-sm {
            font-size: 0.875rem;
        }
        .font-medium {
            font-weight: 500;
        }
        .text-gray-600 {
            color: #4b5563;
        }
        .hover\:text-gray-900:hover {
            color: #111827;
        }
        .text-gray-900 {
            color: #111827;
        }
        .border-b-2 {
            border-bottom-width: 2px;
        }
        .border-black {
            border-color: #000;
        }
        .pb-2 {
            padding-bottom: 0.5rem;
        }
        .bg-gradient-to-r {
            background-image: linear-gradient(to right, var(--tw-gradient-stops));
        }
        .from-blue-500 {
            --tw-gradient-from: #3b82f6;
            --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to, rgba(59, 130, 246, 0));
        }
        .to-blue-700 {
            --tw-gradient-to: #1d4ed8;
        }
        .text-white {
            color: #fff;
        }
        .px-4 {
            padding-left: 1rem;
            padding-right: 1rem;
        }
        .py-2 {
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
        }
        .rounded-lg {
            border-radius: 0.5rem;
        }
        .hover\:bg-gray-800:hover {
            background-color: #1f2937;
        }
        .transition-colors {
            transition-property: background-color, border-color, color, fill, stroke;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
            transition-duration: 150ms;
        }
        .items-center {
            align-items: center;
        }
        .fa-solid {
            display: inline-block;
            font-style: normal;
            font-variant: normal;
            text-rendering: auto;
            line-height: 1;
        }
        .mr-2 {
            margin-right: 0.5rem;
        }
        .pt-4 {
            padding-top: 1rem;
        }
        .overflow-y-auto {
            overflow-y: auto;
        }
        .flex-grow {
            flex-grow: 1;
        }
        .rounded-lg {
            border-radius: 0.5rem;
        }
        .shadow {
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        }
        .overflow-hidden {
            overflow: hidden;
        }
        .text-xl {
            font-size: 1.25rem;
            line-height: 1.75rem;
        }
        .font-semibold {
            font-weight: 600;
        }
        .text-gray-800 {
            color: #1f2937;
        }
        .overflow-x-auto {
            overflow-x: auto;
        }
        .w-full {
            width: 100%;
        }
        .bg-gray-50 {
            background-color: #f9fafb;
        }
        .text-xs {
            font-size: 0.75rem;
        }
        .uppercase {
            text-transform: uppercase;
        }
        .text-gray-700 {
            color: #374151;
        }
        .px-4 {
            padding-left: 1rem;
            padding-right: 1rem;
        }
        .py-3 {
            padding-top: 0.75rem;
            padding-bottom: 0.75rem;
        }
        .text-left {
            text-align: left;
        }
        .divide-y > * + * {
            border-top-width: 1px;
        }
        .divide-gray-200 > * + * {
            border-color: #e5e7eb;
        }
        .whitespace-nowrap {
            white-space: nowrap;
        }
        .hover\:bg-gray-50:hover {
            background-color: #f9fafb;
        }
        .transition {
            transition-property: background-color, border-color, color, fill, stroke;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
            transition-duration: 150ms;
        }
        .text-blue-600 {
            color: #2563eb;
        }
        .hover\:text-blue-800:hover {
            color: #1e40af;
        }
        .mr-3 {
            margin-right: 0.75rem;
        }
        .text-green-600 {
            color: #16a34a;
        }
        .hover\:text-green-800:hover {
            color: #166534;
        }
        .text-center {
            text-align: center;
        }
        .text-gray-500 {
            color: #6b7280;
        }
        .py-10 {
            padding-top: 2.5rem;
            padding-bottom: 2.5rem;
        }
        .border-t {
            border-top-width: 1px;
        }
        .justify-between {
            justify-content: space-between;
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
        
        /* Filter Bar Styling */
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
            background-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' class='h-6 w-6' fill='none' viewBox='0 0 24 24' stroke='%239ca3af'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z' /%3E%3C/svg%3E\");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 1rem;
            min-width: 280px;
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
                <a href='drones.php' class='text-gray-600 hover:text-gray-900'>Drones</a>
                <a href='employees.php' class='text-gray-600 hover:text-gray-900'>Personeel</a>
                <a href='overigeassets.php' class='text-gray-900 border-b-2 border-black pb-2'>Overige Assets</a>
            </div>
            <button onclick='openAddAssetModal()' class='bg-gradient-to-r from-blue-500 to-blue-700 text-white px-4 py-2 rounded-lg hover:bg-gray-800 transition-colors text-sm flex items-center'>
                <i class='fa-solid fa-plus mr-2'></i>Nieuw Asset
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
                </div>";

if (!empty($uniqueTypes)) {
    $bodyContent .= "
                <div class='filter-group'>
                    <span class='filter-label'>Type:</span>
                    <select id='typeFilter' class='filter-select'>
                        <option value=''>Alle types</option>";
    foreach ($uniqueTypes as $type) {
        $bodyContent .= "<option value='" . htmlspecialchars(strtolower($type)) . "'>" . htmlspecialchars(ucfirst($type)) . "</option>";
    }
    $bodyContent .= "
                    </select>
                </div>";
}

if (!empty($uniqueCategories)) {
    $bodyContent .= "
                <div class='filter-group'>
                    <span class='filter-label'>Categorie:</span>
                    <select id='categoryFilter' class='filter-select'>
                        <option value=''>Alle categorieën</option>";
    foreach ($uniqueCategories as $category) {
        $bodyContent .= "<option value='" . htmlspecialchars(strtolower($category)) . "'>" . htmlspecialchars(ucfirst($category)) . "</option>";
    }
    $bodyContent .= "
                    </select>
                </div>";
}

if (!empty($uniqueDepartments)) {
    $bodyContent .= "
                <div class='filter-group'>
                    <span class='filter-label'>Afdeling:</span>
                    <select id='departmentFilter' class='filter-select'>
                        <option value=''>Alle afdelingen</option>";
    foreach ($uniqueDepartments as $dept) {
        $bodyContent .= "<option value='" . htmlspecialchars(strtolower($dept)) . "'>" . htmlspecialchars(ucfirst($dept)) . "</option>";
    }
    $bodyContent .= "
                    </select>
                </div>";
}

if (!empty($uniqueLocations)) {
    $bodyContent .= "
                <div class='filter-group'>
                    <span class='filter-label'>Locatie:</span>
                    <select id='locationFilter' class='filter-select'>
                        <option value=''>Alle locaties</option>";
    foreach ($uniqueLocations as $location) {
        $bodyContent .= "<option value='" . htmlspecialchars(strtolower($location)) . "'>" . htmlspecialchars(ucfirst($location)) . "</option>";
    }
    $bodyContent .= "
                    </select>
                </div>";
}

$bodyContent .= "
                <div class='filter-group flex-grow'>
                    <input id='searchInput' type='text' placeholder='Zoek assets...' class='filter-search'>
                </div>
            </div>
        </div>

        <div class='p-6 overflow-y-auto flex-grow'>
            <div class='bg-white rounded-lg shadow overflow-hidden'>
                <div class='p-6 border-b border-gray-200 flex justify-between items-center'>
                    <h2 class='text-xl font-semibold text-gray-800'>{$org} Overige Assets</h2>
                </div>
                <div class='overflow-x-auto'>
                    <table id='assetsTable' class='w-full'>
                        <thead class='bg-gray-50 text-xs uppercase text-gray-700'>
                            <tr>";

// Genereer tabel headers
foreach ($kolomDefinities as $key => $header) {
    $isStatusCol = ($key === 'status');
    $isTypeCol = ($key === 'type');
    $isCategoryCol = ($key === 'categorie');
    $isDepartmentCol = ($key === 'afdeling');
    $isLocationCol = ($key === 'locatie');

    $headerAttributes = '';
    if ($isStatusCol) $headerAttributes .= ' data-filterable="status"';
    if ($isTypeCol) $headerAttributes .= ' data-filterable="type"';
    if ($isCategoryCol) $headerAttributes .= ' data-filterable="category"';
    if ($isDepartmentCol) $headerAttributes .= ' data-filterable="department"';
    if ($isLocationCol) $headerAttributes .= ' data-filterable="location"';

    $bodyContent .= "<th class='px-4 py-3 text-left'" . $headerAttributes . ">" . htmlspecialchars($header) . "</th>";
}
$bodyContent .= "<th class='px-4 py-3 text-left'>Acties</th>";
$bodyContent .= "</tr>
                        </thead>
                        <tbody class='divide-y divide-gray-200 text-sm'>";

if (!empty($assets) && is_array($assets)) {
    foreach ($assets as $asset) {
        $bodyContent .= "<tr class='hover:bg-gray-50 transition'";

        // Data attributen voor filtering
        if (isset($asset['status']) && $asset['status'] !== '') {
            $bodyContent .= " data-status='" . htmlspecialchars(strtolower($asset['status'])) . "'";
        }

        $typeValue = $asset['type'] ?? $asset['assetType'] ?? '';
        if ($typeValue !== '') {
            $bodyContent .= " data-type='" . htmlspecialchars(strtolower($typeValue)) . "'";
        }

        $categoryValue = $asset['categorie'] ?? $asset['category'] ?? '';
        if ($categoryValue !== '') {
            $bodyContent .= " data-category='" . htmlspecialchars(strtolower($categoryValue)) . "'";
        }

        $departmentValue = $asset['department'] ?? $asset['afdeling'] ?? '';
        if ($departmentValue !== '') {
            $bodyContent .= " data-department='" . htmlspecialchars(strtolower($departmentValue)) . "'";
        }

        $locationValue = $asset['locatie'] ?? $asset['location'] ?? '';
        if ($locationValue !== '') {
            $bodyContent .= " data-location='" . htmlspecialchars(strtolower($locationValue)) . "'";
        }

        $bodyContent .= ">";

        // Data rijen
        foreach ($kolomDefinities as $dbKey => $headerName) {
            $cellValue = $asset[$dbKey] ?? '';

            if ($dbKey === 'aanschafdatum') {
                $cellValue = formatDate($cellValue);
            } elseif ($dbKey === 'status') {
                $statusClass = getStatusClass($cellValue);
                $cellValue = "<span class='" . $statusClass . " px-3 py-1 rounded-full text-xs font-semibold'>" . htmlspecialchars(ucfirst($cellValue)) . "</span>";
            } elseif (is_string($cellValue) && $cellValue === '') {
                $cellValue = 'N/B';
            }

            $bodyContent .= "<td class='px-4 py-3 whitespace-nowrap'>" . $cellValue . "</td>";
        }

        // Actie knoppen
        $id = $asset['assetId'] ?? $asset['id'] ?? '';
        $bodyContent .= "<td class='px-4 py-3 whitespace-nowrap text-gray-600'>
                            <button onclick='openAssetDetailModal(" . json_encode($asset) . ")' class='text-blue-600 hover:text-blue-800 transition mr-3' title='Details'>
                                <i class='fa-solid fa-info-circle'></i>
                            </button>
                            <button onclick='openEditAssetModal(" . json_encode($asset) . ")' class='text-green-600 hover:text-green-800 transition' title='Bewerken'>
                                <i class='fa-solid fa-edit'></i>
                            </button>
                        </td>";
        $bodyContent .= "</tr>";
    }
} else {
    $colspan = count($kolomDefinities) + 1;
    $bodyContent .= "<tr><td colspan='$colspan' class='text-center text-gray-500 py-10'>Geen overige assets gevonden</td></tr>";
}

$bodyContent .= "
                        </tbody>
                    </table>
                </div>
                <div class='p-4 border-t border-gray-200 flex justify-between items-center text-sm'>
                    <span>Toont " . (($assets) ? ("1-" . count($assets)) : "0") . " van " . count($assets) . " assets</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Asset Modal -->
    <div id='addAssetModal' class='modal-overlay' role='dialog' aria-modal='true' aria-labelledby='addAssetModalTitle'>
        <div class='modal-content'>
            <button class='modal-close-btn' aria-label='Sluit modal' onclick='closeAddAssetModal()'>×</button>
            <h3 id='addAssetModalTitle' class='flex items-center gap-2'>
              <i class=\"fa-solid fa-box text-gray-700 mr-2\"></i> 
              Asset Toevoegen
            </h3>
            <form id='addAssetForm' action='save_asset.php' method='POST' class='space-y-4'>
                <div class='form-grid'>
                    <div class='form-group'>
                        <label for='asset_naam'>Naam <span class='required-star'>*</span></label>
                        <input type='text' id='asset_naam' name='naam' required placeholder='bv. Projector Zaal 1'>
                    </div>
                    
                    <div class='form-group'>
                        <label for='asset_type'>Type <span class='required-star'>*</span></label>
                        <select id='asset_type' name='type' required>
                            <option value=''>Selecteer type</option>";
foreach ($uniqueTypes as $type) {
    $bodyContent .= "<option value='" . htmlspecialchars($type) . "'>" . htmlspecialchars($type) . "</option>";
}
$bodyContent .= "
                        </select>
                    </div>
                    
                    <div class='form-group'>
                        <label for='asset_categorie'>Categorie</label>
                        <select id='asset_categorie' name='categorie'>
                            <option value=''>Selecteer categorie</option>";
foreach ($uniqueCategories as $category) {
    $bodyContent .= "<option value='" . htmlspecialchars($category) . "'>" . htmlspecialchars($category) . "</option>";
}
$bodyContent .= "
                        </select>
                    </div>
                    
                    <div class='form-group'>
                        <label for='asset_serienummer'>Serienummer</label>
                        <input type='text' id='asset_serienummer' name='serienummer' placeholder='bv. SN12345ABC'>
                    </div>
                    
                    <div class='form-group'>
                        <label for='asset_status'>Status <span class='required-star'>*</span></label>
                        <select id='asset_status' name='status' required>
                            <option value=''>Selecteer status</option>";
foreach ($uniqueStatuses as $status) {
    $bodyContent .= "<option value='" . htmlspecialchars($status) . "'>" . htmlspecialchars($status) . "</option>";
}
$bodyContent .= "
                        </select>
                    </div>
                    
                    <div class='form-group'>
                        <label for='asset_afdeling'>Afdeling</label>
                        <select id='asset_afdeling' name='afdeling'>
                            <option value=''>Selecteer afdeling</option>";
foreach ($uniqueDepartments as $dept) {
    $bodyContent .= "<option value='" . htmlspecialchars($dept) . "'>" . htmlspecialchars($dept) . "</option>";
}
$bodyContent .= "
                        </select>
                    </div>
                    
                    <div class='form-group'>
                        <label for='asset_locatie'>Locatie</label>
                        <select id='asset_locatie' name='locatie'>
                            <option value=''>Selecteer locatie</option>";
foreach ($uniqueLocations as $location) {
    $bodyContent .= "<option value='" . htmlspecialchars($location) . "'>" . htmlspecialchars($location) . "</option>";
}
$bodyContent .= "
                        </select>
                    </div>
                    
                    <div class='form-group'>
                        <label for='asset_aanschafdatum'>Aanschafdatum</label>
                        <input type='date' id='asset_aanschafdatum' name='aanschafdatum'>
                    </div>
                    
                    <div class='form-group col-span-full'>
                        <label for='asset_notities'>Notities</label>
                        <textarea id='asset_notities' name='notities' rows='3' placeholder='Eventuele aanvullende informatie...'></textarea>
                    </div>
                </div>
                <div class='pt-4 flex justify-end space-x-3'>
                    <button type='button' onclick='closeAddAssetModal()' class='bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 text-sm'>Annuleren</button>
                    <button type='submit' class='bg-gradient-to-r from-blue-500 to-blue-700 text-white px-4 py-2 rounded-lg hover:bg-gray-800 text-sm flex items-center'>
                        <i class='fa-solid fa-save mr-2'></i>Asset Opslaan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Asset Modal -->
    <div id='assetDetailModal' class='modal-overlay' role='dialog' aria-modal='true' aria-labelledby='assetDetailModalTitle'>
        <div class='modal-content drone-detail-modal'>
            <button class='modal-close-btn' aria-label='Sluit modal' onclick='closeAssetDetailModal()'>×</button>
            <div id='assetDetailContent'></div>
        </div>
    </div>

    <!-- Edit Asset Modal -->
    <div id='editAssetModal' class='modal-overlay' role='dialog' aria-modal='true' aria-labelledby='editAssetModalTitle'>
        <div class='modal-content'>
            <button class='modal-close-btn' aria-label='Sluit modal' onclick='closeEditAssetModal()'>×</button>
            <h3 id='editAssetModalTitle' class='flex items-center gap-2'>
              <i class=\"fa-solid fa-pen text-gray-700 mr-2\"></i> 
              Asset Bewerken
            </h3>
            <form id='editAssetForm' action='update_asset.php' method='POST' class='space-y-4'>
                <input type='hidden' id='edit_assetId' name='assetId'>
                <div class='form-grid' id='editAssetFormContent'>
                    <!-- Content will be populated by JavaScript -->
                </div>
                <div class='pt-4 flex justify-end space-x-3'>
                    <button type='button' onclick='closeEditAssetModal()' class='bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 text-sm'>Annuleren</button>
                    <button type='submit' class='bg-gradient-to-r from-blue-500 to-blue-700 text-white px-4 py-2 rounded-lg hover:bg-gray-800 text-sm flex items-center'>
                        <i class='fa-solid fa-save mr-2'></i>Wijzigingen Opslaan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const assetsData = " . json_encode($assets, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ";
        
        const addAssetModal = document.getElementById('addAssetModal');
        const assetDetailModal = document.getElementById('assetDetailModal');
        const editAssetModal = document.getElementById('editAssetModal');
        
        function openAddAssetModal() {
            if (addAssetModal) addAssetModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        function closeAddAssetModal() {
            if (addAssetModal) {
                 addAssetModal.classList.remove('active');
                 document.getElementById('addAssetForm')?.reset();
                 document.body.style.overflow = '';
            }
        }

        function openAssetDetailModal(asset) {
            const modalContent = document.getElementById('assetDetailContent');
            if (modalContent) {
                let content = '<div class=\"drone-detail-header\">';
                content += '<i class=\"fa-solid fa-box drone-icon\"></i>';
                content += '<h3 class=\"text-xl font-semibold\">' + (asset.naam || 'Onbekend asset') + '</h3>';
                content += '</div>';
                
                content += '<div class=\"drone-detail-grid\">';
                content += '<div>';
                content += '<div class=\"detail-group\"><div class=\"detail-label\">Type</div><div class=\"detail-value\">' + (asset.type || 'N/B') + '</div></div>';
                content += '<div class=\"detail-group\"><div class=\"detail-label\">Categorie</div><div class=\"detail-value\">' + (asset.categorie || 'N/B') + '</div></div>';
                content += '<div class=\"detail-group\"><div class=\"detail-label\">Serienummer</div><div class=\"detail-value\">' + (asset.serienummer || 'N/B') + '</div></div>';
                content += '<div class=\"detail-group\"><div class=\"detail-label\">Afdeling</div><div class=\"detail-value\">' + (asset.afdeling || asset.department || 'N/B') + '</div></div>';
                content += '</div>';
                
                content += '<div>';
                let statusClass = getStatusClass(asset.status);
                content += '<div class=\"detail-group\"><div class=\"detail-label\">Status</div><div class=\"detail-value\"><span class=\"detail-value status ' + statusClass + '\">' + (asset.status || 'Onbekend') + '</span></div></div>';
                content += '<div class=\"detail-group\"><div class=\"detail-label\">Locatie</div><div class=\"detail-value\">' + (asset.locatie || asset.location || 'N/B') + '</div></div>';
                content += '<div class=\"detail-group\"><div class=\"detail-label\">Aanschafdatum</div><div class=\"detail-value\">' + formatAssetDate(asset.aanschafdatum) + '</div></div>';
                content += '</div></div>';
                
                content += '<div class=\"detail-group col-span-full\"><div class=\"detail-label\">Notities</div><div class=\"detail-value\">' + (asset.notities || 'Geen notities beschikbaar') + '</div></div>';

                modalContent.innerHTML = content;
            }
            
            if (assetDetailModal) {
                assetDetailModal.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        }
        
        function closeAssetDetailModal() {
            if (assetDetailModal) {
                assetDetailModal.classList.remove('active');
                document.body.style.overflow = '';
            }
        }
        
        function openEditAssetModal(asset) {
            const formContent = document.getElementById('editAssetFormContent');
            const assetIdField = document.getElementById('edit_assetId');
            
            if (assetIdField) assetIdField.value = asset.assetId || asset.id || '';
            
            if (formContent) {
                let formHtml = '';
                formHtml += '<div class=\"form-group\"><label for=\"edit_naam\">Naam <span class=\"required-star\">*</span></label><input type=\"text\" id=\"edit_naam\" name=\"naam\" value=\"' + (asset.naam || '') + '\" required></div>';
                formHtml += '<div class=\"form-group\"><label for=\"edit_type\">Type <span class=\"required-star\">*</span></label><select id=\"edit_type\" name=\"type\" required><option value=\"\">Selecteer type</option>';
                
                const types = " . json_encode($uniqueTypes) . ";
                types.forEach(function(type) {
                    const selected = type === asset.type ? 'selected' : '';
                    formHtml += '<option value=\"' + type + '\" ' + selected + '>' + type + '</option>';
                });
                formHtml += '</select></div>';
                
                formHtml += '<div class=\"form-group\"><label for=\"edit_categorie\">Categorie</label><select id=\"edit_categorie\" name=\"categorie\"><option value=\"\">Selecteer categorie</option>';
                const categories = " . json_encode($uniqueCategories) . ";
                categories.forEach(function(category) {
                    const selected = category === asset.categorie ? 'selected' : '';
                    formHtml += '<option value=\"' + category + '\" ' + selected + '>' + category + '</option>';
                });
                formHtml += '</select></div>';
                
                formHtml += '<div class=\"form-group\"><label for=\"edit_serienummer\">Serienummer</label><input type=\"text\" id=\"edit_serienummer\" name=\"serienummer\" value=\"' + (asset.serienummer || '') + '\"></div>';
                
                formHtml += '<div class=\"form-group\"><label for=\"edit_status\">Status <span class=\"required-star\">*</span></label><select id=\"edit_status\" name=\"status\" required><option value=\"\">Selecteer status</option>';
                const statuses = " . json_encode($uniqueStatuses) . ";
                statuses.forEach(function(status) {
                    const selected = status === asset.status ? 'selected' : '';
                    formHtml += '<option value=\"' + status + '\" ' + selected + '>' + status + '</option>';
                });
                formHtml += '</select></div>';
                
                formHtml += '<div class=\"form-group\"><label for=\"edit_afdeling\">Afdeling</label><select id=\"edit_afdeling\" name=\"afdeling\"><option value=\"\">Selecteer afdeling</option>';
                const departments = " . json_encode($uniqueDepartments) . ";
                departments.forEach(function(dept) {
                    const selected = dept === asset.afdeling ? 'selected' : '';
                    formHtml += '<option value=\"' + dept + '\" ' + selected + '>' + dept + '</option>';
                });
                formHtml += '</select></div>';
                
                formHtml += '<div class=\"form-group\"><label for=\"edit_locatie\">Locatie</label><select id=\"edit_locatie\" name=\"locatie\"><option value=\"\">Selecteer locatie</option>';
                const locations = " . json_encode($uniqueLocations) . ";
                locations.forEach(function(location) {
                    const selected = location === asset.locatie ? 'selected' : '';
                    formHtml += '<option value=\"' + location + '\" ' + selected + '>' + location + '</option>';
                });
                formHtml += '</select></div>';
                
                formHtml += '<div class=\"form-group\"><label for=\"edit_aanschafdatum\">Aanschafdatum</label><input type=\"date\" id=\"edit_aanschafdatum\" name=\"aanschafdatum\" value=\"' + formatDateForInput(asset.aanschafdatum) + '\"></div>';
                
                formHtml += '<div class=\"form-group col-span-full\"><label for=\"edit_notities\">Notities</label><textarea id=\"edit_notities\" name=\"notities\" rows=\"3\">' + (asset.notities || '') + '</textarea></div>';
                
                formContent.innerHTML = formHtml;
            }
            
            if (editAssetModal) {
                editAssetModal.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        }
        
        function closeEditAssetModal() {
            if (editAssetModal) {
                editAssetModal.classList.remove('active');
                document.body.style.overflow = '';
            }
        }
        
        function formatAssetDate(dateString) {
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
            if (statusLower.includes('actief') || statusLower.includes('beschikbaar')) return 'bg-green-100 text-green-800';
            if (statusLower.includes('onderhoud')) return 'bg-yellow-100 text-yellow-800';
            if (statusLower.includes('defect') || statusLower.includes('verwijderd')) return 'bg-red-100 text-red-800';
            if (statusLower.includes('gereserveerd')) return 'bg-blue-100 text-blue-800';
            return 'bg-gray-100 text-gray-800';
        }

        function filterAssets() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const statusFilter = document.getElementById('statusFilter').value.toLowerCase();
            const typeFilter = document.getElementById('typeFilter')?.value.toLowerCase() || '';
            const categoryFilter = document.getElementById('categoryFilter')?.value.toLowerCase() || '';
            const departmentFilter = document.getElementById('departmentFilter')?.value.toLowerCase() || '';
            const locationFilter = document.getElementById('locationFilter')?.value.toLowerCase() || '';
            
            const table = document.getElementById('assetsTable');
            if (!table) return;
            
            const tbody = table.querySelector('tbody');
            if (!tbody) return;

            const rows = tbody.querySelectorAll('tr');
            
            rows.forEach(row => {
                const rowText = row.textContent.toLowerCase();
                const status = row.dataset.status || '';
                const type = row.dataset.type || '';
                const category = row.dataset.category || '';
                const department = row.dataset.department || '';
                const location = row.dataset.location || '';

                const matchesSearch = rowText.includes(searchTerm);
                const matchesStatus = statusFilter === '' || status === statusFilter;
                const matchesType = typeFilter === '' || type === typeFilter;
                const matchesCategory = categoryFilter === '' || category === categoryFilter;
                const matchesDepartment = departmentFilter === '' || department === departmentFilter;
                const matchesLocation = locationFilter === '' || location === locationFilter;
                
                row.style.display = (matchesSearch && matchesStatus && matchesType && matchesCategory && matchesDepartment && matchesLocation) ? '' : 'none';
            });
        }
        
        document.getElementById('searchInput')?.addEventListener('input', filterAssets);
        document.getElementById('statusFilter')?.addEventListener('change', filterAssets);
        
        if (document.getElementById('typeFilter')) {
            document.getElementById('typeFilter').addEventListener('change', filterAssets);
        }
        
        if (document.getElementById('categoryFilter')) {
            document.getElementById('categoryFilter').addEventListener('change', filterAssets);
        }
        
        if (document.getElementById('departmentFilter')) {
            document.getElementById('departmentFilter').addEventListener('change', filterAssets);
        }
        
        if (document.getElementById('locationFilter')) {
            document.getElementById('locationFilter').addEventListener('change', filterAssets);
        }

        document.getElementById('editAssetForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            this.submit();
        });
        
        if (addAssetModal) {
            addAssetModal.addEventListener('click', function(event) { 
                if (event.target === addAssetModal) closeAddAssetModal(); 
            });
        }
        
        if (assetDetailModal) {
            assetDetailModal.addEventListener('click', function(event) { 
                if (event.target === assetDetailModal) closeAssetDetailModal(); 
            });
        }
        
        if (editAssetModal) {
            editAssetModal.addEventListener('click', function(event) { 
                if (event.target === editAssetModal) closeEditAssetModal(); 
            });
        }
    </script>
";

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../layouts/template.php';
