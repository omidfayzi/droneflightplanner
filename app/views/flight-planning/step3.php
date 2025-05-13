<?php
// /var/www/public/frontend/pages/flight-planning/step3.php
// Vluchtplanning Stap 3

session_start();
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../functions.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $_SESSION['step2_data'] = $_POST; // Voorbeeld: sla alle POST-data op
}

// Stel variabelen in voor template.php
$showHeader = 1;
$userName = $_SESSION['user']['first_name'] ?? 'Onbekend';
$org = isset($organisation) ? $organisation : 'Organisatie A';
$headTitle = "Goedkeuring";
$gobackUrl = 0;
$rightAttributes = 0;

// Body content voor Vluchtplanning Stap 3
$bodyContent = "
    <div class='h-[83.5vh] bg-gray-100 shadow-md rounded-tl-xl w-13/15'>
        <!-- Stappenbalk -->
        <div class='p-4 bg-gray-100'>
            <div class='flex justify-center items-center space-x-4'>
                <span class='w-8 h-8 bg-black text-white rounded-full flex items-center justify-center'>1</span>
                <div class='flex-1 h-1 bg-black'></div>
                <span class='w-8 h-8 bg-black text-white rounded-full flex items-center justify-center'>2</span>
                <div class='flex-1 h-1 bg-black'></div>
                <span class='w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold'>3</span>
                <div class='flex-1 h-1 bg-gray-300'></div>
                <span class='w-8 h-8 bg-gray-300 text-black rounded-full flex items-center justify-center'>4</span>
            </div>
        </div>

        <!-- Content -->
        <div class='p-6 overflow-y-auto max-h-[calc(90vh-200px)]'>
            <h2 class='text-xl font-bold mb-4 text-gray-800'>Goedkeuring aanvragen</h2>
            <form action='step4.php' method='post' class='space-y-6'>
                <div class='bg-white rounded-lg shadow-md p-4'>
                    <h3 class='text-lg font-semibold mb-3 text-gray-700'>Vereiste vergunningen</h3>
                    <div class='space-y-3'>
                        <div class='flex items-center'>
                            <input type='checkbox' id='airspace_permission' name='permissions[]' value='airspace' class='h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500'>
                            <label for='airspace_permission' class='ml-2 text-sm text-gray-700'>Luchtruimtoestemming</label>
                        </div>
                        <div class='flex items-center'>
                            <input type='checkbox' id='privacy_statement' name='permissions[]' value='privacy' class='h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500'>
                            <label for='privacy_statement' class='ml-2 text-sm text-gray-700'>Privacyverklaring</label>
                        </div>
                        <div class='flex items-center'>
                            <input type='checkbox' id='risk_acceptance' name='permissions[]' value='risk' class='h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500'>
                            <label for='risk_acceptance' class='ml-2 text-sm text-gray-700'>Risicoacceptatie</label>
                        </div>
                    </div>
                </div>
                <div class='bg-white rounded-lg shadow-md p-4'>
                    <h3 class='text-lg font-semibold mb-3 text-gray-700'>Documentupload</h3>
                    <div class='border-2 border-dashed border-gray-300 p-6 text-center rounded-lg'>
                        <p class='text-sm text-gray-500'>Sleep bestanden hierheen</p>
                        <p class='text-sm text-gray-500'>of</p>
                        <label for='file_upload' class='cursor-pointer text-blue-600 hover:text-blue-800'>Selecteer bestanden</label>
                        <input type='file' id='file_upload' name='documents[]' multiple class='hidden'>
                    </div>
                </div>
                <div class='flex justify-between items-center mt-6'>
                    <a href='flight-planning-step2.php' class='text-gray-500 hover:text-gray-700 flex items-center text-sm px-3 py-2'>
                        <i class='fa-solid fa-arrow-left mr-2'></i> Vorige stap
                    </a>
                    <button type='submit' class='bg-black text-white px-6 py-3 rounded-full hover:bg-gray-800 transition-colors flex items-center'>
                        Aanvraag indienen <i class='fa-solid fa-arrow-right ml-2'></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
";
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../layouts/template.php';
