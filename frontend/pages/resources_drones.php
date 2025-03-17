<?php
session_start();
require_once __DIR__ . '/../../config/config.php';

// Stel variabelen in voor template.php
$showHeader = 1;
$userName = $_SESSION['user']['first_name'] ?? 'Onbekend'; // Haal uit sessie
$org = isset($organisation) ? $organisation : 'Organisatie B'; // Dynamisch uit config.php, fallback naar Organisatie B
$headTitle = "Drones"; // Paginatitel
$gobackUrl = 0;
$rightAttributes = 0; // Geen logout-knop, wel notificatie en profiel

// Body content voor Resources - Drones
$bodyContent = "
    <div class='h-[83.5vh] bg-gray-100 shadow-md rounded-tl-xl w-13/15'>

        <!-- Navigatie en Actieknop -->
        <div class='p-8 bg-white flex justify-between items-center border-b border-gray-200'>
            <div class='flex space-x-4 text-sm font-medium'>
                <a href='/frontend/pages/resources_drones.php' class='text-gray-600 hover:text-gray-900 border-b-2 border-black pb-2'>Drones</a>
                <a href='/frontend/pages/resources_teams.php' class='text-gray-600 hover:text-gray-900'>Teams</a>
                <a href='/frontend/pages/resources_personeel.php' class='text-blackborder-b-2 border-black pb-2'>Personeel</a>
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
                    <h2 class='text-xl font-semibold text-gray-800'>Drone Inventory</h2>
                    <div class='relative'>
                        <select class='border border-gray-300 rounded-lg px-4 py-2 text-gray-600 focus:outline-none pr-8'>
                            <option>Filter: Alle statuses</option>
                            <option>Actief</option>
                            <option>Onderhoud</option>
                            <option>Inactief</option>
                        </select>
                    </div>
                </div>
                <div class='overflow-x-auto'>
                    <table class='w-full'>
                        <thead class='bg-gray-200 text-sm'>
                            <tr>
                                <th class='p-4 text-left text-gray-600 font-medium'>Model</th>
                                <th class='p-4 text-left text-gray-600 font-medium'>Serienummer</th>
                                <th class='p-4 text-left text-gray-600 font-medium'>Laatste Inspectie</th>
                                <th class='p-4 text-left text-gray-600 font-medium'>Volgende Kalibratie</th>
                                <th class='p-4 text-left text-gray-600 font-medium'>Status</th>
                                <th class='p-4 text-left text-gray-600 font-medium'>Acties</th>
                            </tr>
                        </thead>
                        <tbody class='divide-y divide-gray-200 text-sm'>
                            <tr class='hover:bg-gray-50 transition'>
                                <td class='p-4 text-gray-800'>DJI Matrice 300 RTK</td>
                                <td class='p-4 text-gray-600'>SN-2345HIJK</td>
                                <td class='p-4 text-gray-600'>12-03-2024</td>
                                <td class='p-4 text-gray-600'>12-06-2024</td>
                                <td class='p-4'>
                                    <span class='bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium'>Actief</span>
                                </td>
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
                                <td class='p-4 text-gray-800'>Autel Robotics EVO II</td>
                                <td class='p-4 text-gray-600'>SN-6798ABCD</td>
                                <td class='p-4 text-gray-600'>01-04-2024</td>
                                <td class='p-4 text-gray-600'>01-07-2024</td>
                                <td class='p-4'>
                                    <span class='bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-sm font-medium'>Onderhoud</span>
                                </td>
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
                                <td class='p-4 text-gray-800'>Parrot Anafi USA</td>
                                <td class='p-4 text-gray-600'>SN-1123FGH</td>
                                <td class='p-4 text-gray-600'>15-03-2024</td>
                                <td class='p-4 text-gray-600'>15-06-2024</td>
                                <td class='p-4'>
                                    <span class='bg-pink-100 text-pink-800 px-3 py-1 rounded-full text-sm font-medium'>Inactief</span>
                                </td>
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

require_once __DIR__ . '/components/header.php'; 
require_once __DIR__ . '/template.php';
?>