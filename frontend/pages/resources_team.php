<?php
session_start();

// Stel variabelen in voor de header
$showHeader = 1;
$userName = $_SESSION['user']['first_name'] ?? 'Onbekend'; // Haal uit sessie
$org = 'Organisatie A'; // Overeenkomend met de screenshot
$headTitle = "Resources";
$gobackUrl = 0;
$rightAttributes = 0; // Geen logout-knop, wel notificatie en profiel

// Body content voor Resources - Team
$bodyContent = "
    <div class='h-[90vh] max-h-[90vh] mx-auto bg-gray-100 shadow-md rounded-tl-xl overflow-y-hidden w-13/15'>
        <!-- Hoofding -->
        <div class='p-4 bg-white border-b border-gray-200 flex justify-between items-center'>
            <div>
                <h1 class='text-2xl font-bold text-gray-900'>Resources</h1>
                <p class='text-sm text-gray-500'>Laatste update: 15 minuten geleden, " . htmlspecialchars($org) . "</p>
            </div>
            <div class='flex items-center space-x-4'>
                <div class='relative'>
                    <i class='fa-solid fa-bell text-gray-600 hover:text-gray-800 cursor-pointer'></i>
                    <span class='absolute -top-1 -right-1 bg-red-600 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center'>3</span>
                </div>
                <a href='/src/frontend/pages/profile.php' class='text-gray-600 hover:text-gray-800'>
                    <i class='fa-solid fa-user text-xl'></i>
                </a>
            </div>
        </div>

        <!-- Navigatie en Actieknop -->
        <div class='p-4 bg-white flex justify-between items-center border-b border-gray-200'>
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

// Inclusie van de header (volledige lay-out)
include __DIR__ . '/../includes/header.php';
?>
