<?php
session_start();
require_once __DIR__ . '/../../config/config.php';

// Stel variabelen in voor template.php
$showHeader = 1;
$userName = $_SESSION['user']['first_name'] ?? 'Onbekend'; // Haal uit sessie
$org = isset($organisation) ? $organisation : 'Organisatie A'; // Dynamisch uit config.php, fallback naar Organisatie A
$headTitle = "Team"; // Paginatitel
$gobackUrl = 0;
$rightAttributes = 0; // Geen logout-knop, wel notificatie en profiel

// Body content voor Resources - Team
$bodyContent = "
    <div class='h-[85.5vh] mx-auto bg-gray-100 shadow-md rounded-tl-xl overflow-y-hidden w-13/15'>

        <!-- Navigatie en Actieknop -->
        <div class='p-8 bg-white flex justify-between items-center border-b border-gray-200'>
            <div class='flex space-x-4 text-sm font-medium'>
                <a href='/frontend/pages/resources_drones.php' class='text-gray-600 hover:text-gray-900'>Drones</a>
                <a href='/frontend/pages/resources_team.php' class='text-black border-b-2 border-black pb-2'>Team</a>
                <a href='/frontend/pages/resources_addons.php' class='text-gray-600 hover:text-gray-900'>Add-ons</a>
            </div>
            <button class='bg-black text-white px-4 py-2 rounded-lg hover:bg-gray-800 transition-colors'>
                + Nieuw item
            </button>
        </div>

        <!-- Hoofdinhoud -->
        <div class='p-6 overflow-y-auto max-h-[calc(90vh-200px)]'>
            <div class='bg-white rounded-lg shadow overflow-hidden'>
                <div class='p-6 border-b border-gray-200 flex justify-between items-center'>
                    <h2 class='text-xl font-semibold text-gray-800'>Holding the Drones leden</h2>
                    <div class='flex space-x-4'>
                        <button class='bg-black text-white px-4 py-2 rounded-lg hover:bg-gray-800 transition-colors'>
                            Team samenstellen
                        </button>
                        <div class='relative'>
                            <select class='border border-gray-300 rounded-lg px-4 py-2 text-gray-600 focus:outline-none pr-8'>
                                <option>Filter: Alle rollen</option>
                                <option>Hoofd piloot</option>
                                <option>Drone Operator</option>
                                <option>Developer</option>
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
                        <tbody class='divide-y divide-gray-200 text-sm'>
                            <tr class='hover:bg-gray-50 transition'>
                                <td class='p-4 text-gray-800'>Jan Smit</td>
                                <td class='p-4 text-gray-600'>Hoofd piloot</td>
                                <td class='p-4 text-gray-600'>ROC Light UAS</td>
                                <td class='p-4 text-gray-600'>2025-03-01</td>
                                <td class='p-4 text-right'>
                                    <a href='#' class='text-gray-600 hover:text-gray-800 transition mr-2'>
                                        <i class='fa-solid fa-pencil'></i>
                                    </a>
                                    <a href='#' class='text-red-600 hover:text-red-800 transition'>
                                        <i class='fa-solid fa-trash'></i>
                                    </a>
                                </td>
                            </tr>
                            <tr class='hover:bg-gray-50 transition'>
                                <td class='p-4 text-gray-800'>Eva de Jong</td>
                                <td class='p-4 text-gray-600'>Drone Operator</td>
                                <td class='p-4 text-gray-600'>A1/A3 Certificaat</td>
                                <td class='p-4 text-gray-600'>2024-12-15</td>
                                <td class='p-4 text-right'>
                                    <a href='#' class='text-gray-600 hover:text-gray-800 transition mr-2'>
                                        <i class='fa-solid fa-pencil'></i>
                                    </a>
                                    <a href='#' class='text-red-600 hover:text-red-800 transition'>
                                        <i class='fa-solid fa-trash'></i>
                                    </a>
                                </td>
                            </tr>
                            <tr class='hover:bg-gray-50 transition'>
                                <td class='p-4 text-gray-800'>Omid Fayzi</td>
                                <td class='p-4 text-gray-600'>Developer</td>
                                <td class='p-4 text-gray-600'>Geen vereist</td>
                                <td class='p-4 text-gray-600'>2024-12-15</td>
                                <td class='p-4 text-right'>
                                    <a href='#' class='text-gray-600 hover:text-gray-800 transition mr-2'>
                                        <i class='fa-solid fa-pencil'></i>
                                    </a>
                                    <a href='#' class='text-red-600 hover:text-red-800 transition'>
                                        <i class='fa-solid fa-trash'></i>
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
";

require_once __DIR__ . '/componments/header.php'; 
require_once __DIR__ . '/template.php';
?>