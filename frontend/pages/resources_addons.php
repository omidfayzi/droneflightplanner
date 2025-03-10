<?php
session_start();
require_once __DIR__ . '/../../config/config.php';

// Stel variabelen in voor template.php
$showHeader = 1;
$userName = $_SESSION['user']['first_name'] ?? 'Onbekend'; // Haal uit sessie
$org = isset($organisation) ? $organisation : 'Organisatie A'; // Dynamisch uit config.php, fallback naar Organisatie A
$headTitle = "Add-ons"; // Paginatitel
$gobackUrl = 0;
$rightAttributes = 0; // Geen logout-knop, wel notificatie en profiel

// Body content zonder header, maar met navigatie en hoofdinhoud
$bodyContent = "
    <div class='h-[85.5vh] mx-auto bg-gray-100 shadow-md rounded-tl-xl overflow-y-hidden w-13/15'>
        <!-- Navigatie en Actieknop -->
        <div class='p-8 bg-white flex justify-between items-center border-b border-gray-200'>
            <div class='flex space-x-4 text-sm font-medium'>
                <a href='/frontend/pages/resources_drones.php' class='text-gray-600 hover:text-gray-900'>Drones</a>
                <a href='/frontend/pages/resources_team.php' class='text-gray-600 hover:text-gray-900'>Team</a>
                <a href='/frontend/pages/resources_addons.php' class='text-black border-b-2 border-black pb-2'>Add-ons</a>
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
                        <tbody class='divide-y divide-gray-200 text-sm'>
                            <tr class='hover:bg-gray-50 transition'>
                                <td class='p-4 text-gray-800'>Batterij</td>
                                <td class='p-4 text-gray-600'>TB60 Smart Battery</td>
                                <td class='p-4 text-gray-600'>BAT-6789XYZ</td>
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
                                <td class='p-4 text-gray-800'>Camera</td>
                                <td class='p-4 text-gray-600'>DJI ZENMUSE</td>
                                <td class='p-4 text-gray-600'>CAM-12345</td>
                                <td class='p-4'>
                                    <span class='bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium'>Nieuw</span>
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
                                <td class='p-4 text-gray-800'>Gimbal</td>
                                <td class='p-4 text-gray-600'>DJI RONIN</td>
                                <td class='p-4 text-gray-600'>GIM-67890</td>
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
                                <td class='p-4 text-gray-800'>Filter</td>
                                <td class='p-4 text-gray-600'>ND Filter</td>
                                <td class='p-4 text-gray-600'>FLT-1111</td>
                                <td class='p-4'>
                                    <span class='bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium'>Nieuw</span>
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
                                <td class='p-4 text-gray-800'>RemoteID</td>
                                <td class='p-4 text-gray-600'>REMOTEID V2</td>
                                <td class='p-4 text-gray-600'>RID-2222</td>
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
                                <td class='p-4 text-gray-800'>Props</td>
                                <td class='p-4 text-gray-600'>Propeller X</td>
                                <td class='p-4 text-gray-600'>PRP-3333</td>
                                <td class='p-4'>
                                    <span class='bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium'>Nieuw</span>
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
                                <td class='p-4 text-gray-800'>Controller</td>
                                <td class='p-4 text-gray-600'>DJI SMART CONTROLLER</td>
                                <td class='p-4 text-gray-600'>CTRL-4444</td>
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
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
";

// Inclusie van header-component en template.php
require_once __DIR__ . '/components/header.php'; 
require_once __DIR__ . '/template.php';
?>