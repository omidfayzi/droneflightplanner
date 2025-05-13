<?php
// /var/www/public/frontend/pages/flight-planning/step1.php
// Vluchtplanning Stap 1

session_start();
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../functions.php';

// Stel variabelen in voor template.php
$showHeader = 1;
$userName    = $_SESSION['user']['first_name'] ?? 'Onbekend'; // Gebruikersnaam uit sessie
$headTitle   = "Basis"; // Paginatitel
$gobackUrl   = 0; // Geen terug-knop (we tonen eigen cancel knop)
$rightAttributes = 0; // Geen logout knop, wel notificaties en profiel

// Body content voor Vluchtplanning Stap 1
$bodyContent = <<<HTML
<div class="h-[83.5vh] bg-gray-100 shadow-md rounded-tl-xl w-13/15">
    <!-- Stappenbalk -->
    <div class="p-4 bg-gray-100">
        <div class="flex justify-center items-center space-x-4">
            <span class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold">1</span>
            <div class="flex-1 h-1 bg-gray-300"></div>
            <span class="w-8 h-8 bg-gray-300 text-gray-600 rounded-full flex items-center justify-center">2</span>
            <div class="flex-1 h-1 bg-gray-300"></div>
            <span class="w-8 h-8 bg-gray-300 text-gray-600 rounded-full flex items-center justify-center">3</span>
            <div class="flex-1 h-1 bg-gray-300"></div>
            <span class="w-8 h-8 bg-gray-300 text-gray-600 rounded-full flex items-center justify-center">4</span>
        </div>
    </div>

    <!-- Content -->
    <div class="p-6 overflow-y-auto max-h-[calc(90vh-200px)]">
        <h2 class="text-xl font-bold mb-4 text-gray-800">Stap 1: Basisgegevens, Route &amp; Resources</h2>
        <form action="step2.php" method="post" class="space-y-6">
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label for="flight_name" class="block text-sm font-medium text-gray-700 mb-1">Vluchtnaam</label>
                    <input type="text" name="flight_name" id="flight_name" required
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="bv. Inspectie Windmolenpark A">
                </div>
                <div>
                    <label for="flight_type" class="block text-sm font-medium text-gray-700 mb-1">Vluchttype</label>
                    <select name="flight_type" id="flight_type" required
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Selecteer vluchttype</option>
                        <option value="route">Route</option>
                        <option value="object">Object Inspectie</option>
                        <option value="oppervlakte">Oppervlakte Mapping</option>
                    </select>
                </div>
                <div>
                    <label for="flight_date" class="block text-sm font-medium text-gray-700 mb-1">Geplande Datum</label>
                    <input type="date" name="flight_date" id="flight_date" required
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label for="flight_pilot" class="block text-sm font-medium text-gray-700 mb-1">Toegewezen Piloot</label>
                    <select name="flight_pilot" id="flight_pilot" required
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Selecteer Piloot...</option>
                        <option value="user1">Jan Smit</option>
                        <option value="user2">Fatima El Moussaoui</option>
                    </select>
                </div>
                <div>
                    <label for="flight_drone" class="block text-sm font-medium text-gray-700 mb-1">Toegewezen Drone</label>
                    <select name="flight_drone" id="flight_drone" required
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Selecteer Drone...</option>
                        <option value="drone1">DJI Mavic 3 Pro (SN123)</option>
                        <option value="drone2">Autel Evo II (SN678)</option>
                    </select>
                </div>
                <div>
                    <label for="flight_payload" class="block text-sm font-medium text-gray-700 mb-1">Toegewezen Payload/AddOn</label>
                    <select name="flight_payload" id="flight_payload"
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Geen extra payload</option>
                        <option value="addon1">Thermische Camera (PL987)</option>
                        <option value="addon2">Lidar Sensor (PL123)</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Vluchtgebied / Routeplanning</label>
                <div class="flex justify-between mb-4 flex-wrap gap-4">
                    <div>
                        <h4 class="font-semibold text-gray-800 mb-1">Luchtruim Info (Live Simulatie)</h4>
                        <ul class="list-disc list-inside text-sm text-gray-700">
                            <li class="text-green-600">Klasse G Luchtruim</li>
                            <li class="text-orange-600">LET OP: Nabijheid CTR (5km)</li>
                            <li class="text-red-600">Actieve TFR gedetecteerd!</li>
                            <li>Geen NOTAMs relevant</li>
                        </ul>
                    </div>
                    <div class="text-right">
                        <h4 class="font-semibold text-gray-800 mb-1">Weer (Simulatie)</h4>
                        <p class="text-sm text-gray-700">18°C, Bewolkt, Wind 8 kts ZW</p>
                        <button type="button"
                            class="bg-gray-200 text-gray-800 px-3 py-1 rounded hover:bg-gray-300 transition-colors"
                            onclick="alert('Weer details laden...')">Details</button>
                    </div>
                </div>
                <div class="w-full h-72 bg-gray-200 rounded-lg flex items-center justify-center border-2 border-dashed border-gray-400">
                    <i class="fa-solid fa-location-dot text-gray-600 text-2xl mr-2"></i>
                    <span class="text-gray-600">Interactieve Kaart (Mapbox) Placeholder</span>
                </div>
            </div>
            <div class="flex justify-between items-center mt-6">
                <p class="text-sm text-gray-500">".htmlspecialchars($userName)."</p>
                <div>
                    <button type="button" onclick="window.location.href='dashboard.php'"
                        class="bg-gray-300 text-gray-800 px-6 py-3 rounded-full hover:bg-gray-400 transition-colors mr-2">Annuleren</button>
                    <button type="submit"
                        class="bg-gray-900 text-white px-6 py-3 rounded-full hover:bg-gray-700 transition-colors">Volgende: Risicoanalyse</button>
                </div>
            </div>
        </form>
    </div>
</div>
HTML;

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../layouts/template.php';
