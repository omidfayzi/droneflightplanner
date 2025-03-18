<?php
session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../backend/functions/functions.php'; 

// Variabelen voor template.php
$showHeader = 1;
$userName = $_SESSION['user']['first_name'] ?? 'Onbekend'; // Gebruikersnaam uit sessie
$org = isset($organisation) ? $organisation : 'Organisatie A'; // Organisatie uit config
$headTitle = "Personeelsbeheer"; // Paginatitel
$gobackUrl = 0; // Geen terug-knop
$rightAttributes = 0; // Standaard header-attributen

// Body content
$bodyContent = "
    <div class='h-[83.5vh] bg-gray-100 shadow-md rounded-tl-xl w-13/15'>
        <!-- Navigatie -->
        <div class='p-8 bg-white flex justify-between items-center border-b border-gray-200'>
            <div class='flex space-x-4 text-sm font-medium'>
                <a href='/frontend/pages/resources_drones.php' class='text-gray-600 hover:text-gray-900'>Drones</a>
                <a href='/frontend/pages/resources_teams.php' class='text-gray-600 hover:text-gray-900'>Teams</a>
                <a href='/frontend/pages/resources_personeel.php' class='text-black border-b-2 border-black pb-2'>Personeel</a>
                <a href='/frontend/pages/resources_addons.php' class='text-gray-600 hover:text-gray-900'>Add-ons</a>
            </div>
        </div>

        <!-- Hoofdinhoud -->
        <div class='p-6 overflow-y-auto max-h-[calc(90vh-200px)]'>
            <div class='bg-white rounded-lg shadow overflow-hidden'>
                <div class='p-6 border-b border-gray-200 flex justify-between items-center'>
                    <h2 class='text-xl font-semibold text-gray-800'>Personeelsleden van {$org}</h2>
                    <div class='flex space-x-4'>
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
                                <td class='p-4 text-gray-400'>Alleen-lezen</td>
                            </tr>
                            <!-- Extra rijen kunnen hier worden toegevoegd -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
";

// Include header en template
require_once __DIR__ . '/components/header.php';
require_once __DIR__ . '/template.php';
?>