<?php
session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../backend/functions/functions.php'; 

// Variabelen voor template.php
$showHeader = 1;
$userName = $_SESSION['user']['first_name'] ?? 'Onbekend';
$org = isset($organisation) ? $organisation : 'Organisatie A';
$headTitle = "Teambeheer";
$gobackUrl = 1; // Terug-knop aan
$rightAttributes = 0;

// Simuleer teamdata (in praktijk uit database)
$teamId = $_GET['team'] ?? null;
$team = [
    'name' => 'Team Alpha',
    'leader' => 'Jan Smit',
    'members' => [
        ['name' => 'Jan Smit', 'role' => 'Hoofd piloot', 'email' => 'jan@example.com'],
        ['name' => 'Eva de Jong', 'role' => 'Drone Operator', 'email' => 'eva@example.com'],
        ['name' => 'Omid Fayzi', 'role' => 'Developer', 'email' => 'omid@example.com'],
        ['name' => 'Jan de boer', 'role' => 'Drone Operator', 'email' => 'omid@example.com']
    ],
    'status' => 'Actief',
    'last_updated' => '2023-10-24'
];

// Body content
$bodyContent = "
    <div class='h-[83.5vh] bg-gray-50 shadow-md rounded-tl-xl w-13/15'>
        <!-- Kruimelpad en acties -->
        <div class='p-6 bg-white border-b border-gray-200 flex justify-between items-center'>
            <nav class='text-sm text-gray-600'>
                <a href='/frontend/pages/resources_teams.php' class='hover:text-gray-900'>Teams</a> 
                <span class='mx-2'>></span> 
                <span class='font-medium text-gray-800'>{$team['name']}</span>
            </nav>
             <button title='Team verwijderen' class='text-black px-4 py-2 rounded-lg'>
                 <i class='fa-solid fa-trash'></i> 
                </button>
        </div>

        <!-- Hoofdinhoud -->
        <div class='p-8 overflow-y-auto max-h-[calc(90vh-200px)]'>
            <!-- Teamdetails -->
            <div class='bg-white rounded-xl shadow-lg p-6 mb-8'>
                <div class='grid grid-cols-1 md:grid-cols-3 gap-8'>
                    <div>
                        <p class='text-sm text-gray-500 mb-2'>Teamleider</p>
                        <p class='text-lg font-medium text-gray-800 flex items-center'>
                            <i class='fa-solid fa-user mr-3 text-gray-600'></i> {$team['leader']}
                        </p>
                    </div>
                    <div>
                        <p class='text-sm text-gray-500 mb-2'>Status</p>
                        <span class='bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium'>{$team['status']}</span>
                    </div>
                    <div>
                        <p class='text-sm text-gray-500 mb-2'>Laatst gewijzigd</p>
                        <p class='text-lg font-medium text-gray-800 flex items-center'>
                            <i class='fa-solid fa-calendar-alt mr-3 text-gray-600'></i> {$team['last_updated']}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Ledenlijst -->
            <div class='bg-white rounded-xl shadow-lg overflow-hidden'>
                <div class='p-6 border-b border-gray-200 flex justify-between items-center'>
                    <h2 class='text-2xl font-semibold text-gray-900'>Teamleden</h2>
                    <input type='text' placeholder='Zoek lid...' class='border border-gray-300 rounded-lg px-4 py-2 text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500' />
                </div>
                <div class='overflow-x-auto'>
                    <table class='w-full text-sm'>
                        <thead class='bg-gray-100'>
                            <tr>
                                <th class='p-4 text-left text-gray-600'>Naam</th>
                                <th class='p-4 text-left text-gray-600'>Rol</th>
                                <th class='p-4 text-left text-gray-600'>Email</th>
                                <th class='p-4 text-left text-gray-600'>Acties</th>
                            </tr>
                        </thead>
                        <tbody class='divide-y divide-gray-200'>
                            " . implode('', array_map(function($member) {
                                $initials = strtoupper(substr($member['name'], 0, 1));
                                return "
                                    <tr class='hover:bg-gray-50 transition'>
                                        <td class='p-4 flex items-center'>
                                            <div class='w-8 h-8 bg-blue-500 text-white rounded-full flex items-center justify-center mr-3'>{$initials}</div>
                                            <span class='text-gray-800 font-medium'>{$member['name']}</span>
                                        </td>
                                        <td class='p-4 text-gray-600'>{$member['role']}</td>
                                        <td class='p-4 text-gray-600'>{$member['email']}</td>
                                        <td class='p-4 text-right'>
                                            <button class='text-gray-600 hover:text-gray-800 transition'>
                                                <i class='fa-solid fa-ellipsis-vertical'></i>
                                            </button>
                                        </td>
                                    </tr>
                                ";
                            }, $team['members'])) . "
                        </tbody>
                    </table>
                </div>
                <div class='p-4 bg-gray-50 border-t border-gray-200 flex justify-between text-sm'>
                    <span class='text-gray-600'>" . count($team['members']) . " leden</span>
                    <div class='flex space-x-2'>
                        <button class='px-3 py-1 bg-gray-200 rounded hover:bg-gray-300'>Vorige</button>
                        <button class='px-3 py-1 bg-gray-200 rounded hover:bg-gray-300'>Volgende</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
";

// Include header en template
require_once __DIR__ . '/components/header.php';
require_once __DIR__ . '/template.php';
?>