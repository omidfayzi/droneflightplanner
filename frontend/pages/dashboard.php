<?php
// /var/www/public/frontend/pages/dashboard.php
// Dashboard-pagina voor het Drone Vluchtvoorbereidingssysteem

// Laad de configuratie met relatief pad
require_once __DIR__ . '/../../config/config.php';

// Laad backend-functies met relatief pad
require_once __DIR__ . '/../../backend/functions/functions.php'; 

// Stel pagina-specifieke variabelen in
$headTitle = "Dashboard";
$userName = $_SESSION['user']['first_name'] ?? 'Onbekend';
$org = $_SESSION['org'] ?? '';
$gobackUrl = 0; // Geen terugknop nodig
$rightAttributes = 0; // Geen SSO-knop, alleen profielicoon

// Roep functie aan (verondersteld gedefinieerd in functions.php)
echo fetchPropPrefTxt(1);

// Definieer body content (statische data, kan later dynamisch worden)
$bodyContent = "
    <div class='h-[83.5vh] bg-gray-100 shadow-md rounded-tl-xl w-13/15'>
        <div class='p-6 overflow-y-auto max-h-[calc(90vh-200px)]'>
            <!-- KPI Grid -->
            <div class='grid grid-cols-1 md:grid-cols-3 gap-6 mb-8'>
                <div class='bg-white p-6 rounded-xl shadow hover:shadow-lg transition'>
                    <div class='flex justify-between items-center'>
                        <div>
                            <p class='text-sm text-gray-500 mb-1'>Actieve Vluchten</p>
                            <p class='text-3xl font-bold text-gray-800'>3</p>
                        </div>
                        <div class='w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center'>
                            <i class='fa-solid fa-rocket text-blue-700'></i>
                        </div>
                    </div>
                </div>
                <div class='bg-white p-6 rounded-xl shadow hover:shadow-lg transition'>
                    <div class='flex justify-between items-center'>
                        <div>
                            <p class='text-sm text-gray-500 mb-1'>Wachtend op Goedkeuring</p>
                            <p class='text-3xl font-bold text-gray-800'>2</p>
                        </div>
                        <div class='w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center'>
                            <i class='fa-solid fa-clock text-yellow-700'></i>
                        </div>
                    </div>
                </div>
                <div class='bg-white p-6 rounded-xl shadow hover:shadow-lg transition'>
                    <div class='flex justify-between items-center'>
                        <div>
                            <p class='text-sm text-gray-500 mb-1'>Totaal Vluchten</p>
                            <p class='text-3xl font-bold text-gray-800'>127</p>
                        </div>
                        <div class='w-12 h-12 bg-green-100 rounded-full flex items-center justify-center'>
                            <i class='fa-solid fa-chart-line text-green-700'></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recente Vluchten Tabel -->
            <div class='bg-white rounded-xl shadow overflow-hidden'>
                <div class='p-6 border-b border-gray-200 flex justify-between items-center'>
                    <h3 class='text-xl font-semibold text-gray-800'>Recente Operaties</h3>
                    <button class='flex items-center text-blue-600 hover:text-blue-800 transition'>
                        <i class='fa-solid fa-plus mr-2'></i> Nieuwe Vlucht
                    </button>
                </div>
                <div class='overflow-x-auto'>
                    <table class='w-full'>
                        <thead class='bg-gray-100 text-sm'>
                            <tr>
                                <th class='p-4 text-left text-gray-600'>Vlucht ID</th>
                                <th class='p-4 text-left text-gray-600'>Type</th>
                                <th class='p-4 text-left text-gray-600'>Locatie</th>
                                <th class='p-4 text-left text-gray-600'>Uitgevoerd door</th>
                                <th class='p-4 text-left text-gray-600'>Status</th>
                                <th class='p-4 text-left text-gray-600'></th>
                            </tr>
                        </thead>
                        <tbody class='divide-y divide-gray-200 text-sm'>
                            <tr class='hover:bg-gray-50 transition'>
                                <td class='p-4 font-medium text-gray-800'>#FL-2309</td>
                                <td class='p-4 text-gray-600'>Objectinspectie</td>
                                <td class='p-4 text-gray-600'>Windmolenpark Eemmeerdijk</td>
                                <td class='p-4 text-gray-600'>J. van den Berg</td>
                                <td class='p-4'>
                                    <span class='bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium'>Voltooid</span>
                                </td>
                                <td class='p-4 text-right'>
                                    <button class='text-gray-600 hover:text-gray-800 transition'>
                                        <i class='fa-solid fa-ellipsis-vertical'></i>
                                    </button>
                                </td>
                            </tr>
                            <tr class='hover:bg-gray-50 transition'>
                                <td class='p-4 font-medium text-gray-800'>#FL-2310</td>
                                <td class='p-4 text-gray-600'>BVLOS Route</td>
                                <td class='p-4 text-gray-600'>A12 Corridor</td>
                                <td class='p-4 text-gray-600'>M. de Vries</td>
                                <td class='p-4'>
                                    <span class='bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-sm font-medium'>In behandeling</span>
                                </td>
                                <td class='p-4 text-right'>
                                    <button class='text-gray-600 hover:text-gray-800 transition'>
                                        <i class='fa-solid fa-ellipsis-vertical'></i>
                                    </button>
                                </td>
                            </tr>
                            <tr class='hover:bg-gray-50 transition'>
                                <td class='p-4 font-medium text-gray-800'>#FL-2311</td>
                                <td class='p-4 text-gray-600'>Thermische Scan</td>
                                <td class='p-4 text-gray-600'>Industrieterrein Twente</td>
                                <td class='p-4 text-gray-600'>A. Bakker</td>
                                <td class='p-4'>
                                    <span class='bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium'>Gepland</span>
                                </td>
                                <td class='p-4 text-right'>
                                    <button class='text-gray-600 hover:text-gray-800 transition'>
                                        <i class='fa-solid fa-ellipsis-vertical'></i>
                                    </button>
                                </td>
                            </tr>
                            <tr class='hover:bg-gray-50 transition'>
                                <td class='p-4 font-medium text-gray-800'>#FL-2312</td>
                                <td class='p-4 text-gray-600'>Noodinspectie</td>
                                <td class='p-4 text-gray-600'>Haven Rotterdam</td>
                                <td class='p-4 text-gray-600'>P. Jansen</td>
                                <td class='p-4'>
                                    <span class='bg-red-100 text-red-800 px-3 py-1 rounded-full text-sm font-medium'>Mislukt</span>
                                </td>
                                <td class='p-4 text-right'>
                                    <button class='text-gray-600 hover:text-gray-800 transition'>
                                        <i class='fa-solid fa-ellipsis-vertical'></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
";

// Inclusie van header-component en template.php met relatieve paden
require_once __DIR__ . '/components/header.php'; 
require_once __DIR__ . '/template.php';