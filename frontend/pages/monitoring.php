<?php
// /var/www/public/frontend/pages/monitoring.php
// Monitoring-pagina voor het Drone Vluchtvoorbereidingssysteem

require_once __DIR__ . '/../../config/config.php';

// Stel pagina-specifieke variabelen in
$headTitle = "Monitoring";
$userName = $_SESSION['user']['first_name'] ?? 'Onbekend';
$org = $_SESSION['org'] ?? '';
$gobackUrl = 0; // Geen terugknop nodig
$rightAttributes = 0; // Geen SSO-knop, alleen profielicoon

// Definieer body content (dynamische data, aangepaste lay-out)
$bodyContent = "
    <div class='h-[83.5v] mx-auto flex bg-gray-200 shadow-md rounded-xl overflow-y-auto w-13/15'>
        <!-- Linkerkolom: Kaart/Grafiek Placeholder -->
        <div class='w-2/3 p-6'>
            <div id='mapPlaceholder' class='w-full h-full bg-gray-300 rounded-lg'>
                <p class='text-center text-gray-600 pt-40'>Kaart of grafiek wordt hier geladen (placeholder)</p>
            </div>
        </div>

        <!-- Rechterkolom: Vluchtcontroles en Statusoverzicht -->
        <div class='w-1/3 p-6 flex flex-col space-y-6'>
            <!-- Vluchtcontroles -->
            <div class='bg-white p-4 rounded-lg shadow'>
                <h2 class='text-lg font-semibold text-gray-800 mb-4 uppercase'>Vluchtcontroles</h2>
                <div class='space-y-3'>
                    <button id='startButton' class='w-full bg-black text-white px-6 py-3 rounded-xl flex items-center justify-center hover:bg-gray-800 transition'>
                        <i class='fa-solid fa-play mr-3 text-lg'></i>
                        <span class='text-lg font-bold uppercase'>Starten</span>
                    </button>
                    <button id='pauseButton' class='w-full bg-black text-white px-6 py-3 rounded-xl flex items-center justify-center hover:bg-gray-800 transition'>
                        <i class='fa-solid fa-pause mr-3 text-lg'></i>
                        <span class='text-lg font-bold uppercase'>Pauzeren</span>
                    </button>
                    <button id='emergencyButton' class='w-full bg-black text-white px-6 py-3 rounded-xl flex items-center justify-center hover:bg-red-800 transition'>
                        <i class='fa-solid fa-exclamation-triangle mr-3 text-lg'></i>
                        <span class='text-lg font-bold uppercase'>Noodstop</span>
                    </button>
                </div>
            </div>

            <!-- Statusoverzicht -->
            <div class='bg-white p-4 rounded-lg shadow flex-grow'>
                <h2 class='text-lg font-semibold text-gray-800 mb-4 uppercase'>Statusoverzicht</h2>
                <div class='grid grid-cols-2 gap-3'>
                    <div>
                        <p class='text-sm text-gray-500 uppercase'>Batterij:</p>
                        <p class='text-base font-semibold text-gray-800'>78%</p>
                    </div>
                    <div>
                        <p class='text-sm text-gray-500 uppercase'>Hoogte:</p>
                        <p class='text-base font-semibold text-gray-800'>142m</p>
                    </div>
                    <div>
                        <p class='text-sm text-gray-500 uppercase'>Snelheid:</p>
                        <p class='text-base font-semibold text-gray-800'>12m/s</p>
                    </div>
                    <div>
                        <p class='text-sm text-gray-500 uppercase'>GPS:</p>
                        <p class='text-base font-semibold text-gray-800'>14 satellieten</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Basis JavaScript voor knopinteracties (placeholders)
        document.getElementById('startButton').addEventListener('click', function() {
            alert('Vlucht gestart!');
            // Voeg hier logica toe om de vlucht te starten
        });

        document.getElementById('pauseButton').addEventListener('click', function() {
            alert('Vlucht gepauzeerd!');
            // Voeg hier logica toe om de vlucht te pauzeren
        });

        document.getElementById('emergencyButton').addEventListener('click', function() {
            if (confirm('Weet je zeker dat je een noodstop wilt uitvoeren?')) {
                alert('Noodstop uitgevoerd!');
                // Voeg hier logica toe om de noodstop te activeren
            }
        });
    </script>
";

// Gebruik de template
require_once __DIR__ . '/components/header.php'; 
require_once __DIR__ . '/template.php';
?>