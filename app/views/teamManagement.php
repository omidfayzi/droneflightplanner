<?php
// /var/www/public/app/views/teamManagement.php
// Dit is de centrale hub die routeert naar het juiste dashboard type na organisatiekeuze.

session_start();

// Het pad naar config.php en functions.php moet ook correct zijn vanuit DIT bestand.
// Als deze in /var/www/public/ staan, en dit script in /var/www/public/app/views/, dan is het:
require_once __DIR__ . '/../../config/config.php'; // ../../ betekent 2 mappen omhoog vanaf 'views'
require_once __DIR__ . '/../../functions.php';   // Dit pad ook aanpassen als nodig

// Haal sessievariabelen op, ingesteld door landing-page.php
$selectedOrgId = $_SESSION['selected_organisation_id'] ?? null;
$loggedInUserId = $_SESSION['user']['id'] ?? null; // ID van de ingelogde gebruiker
$selectedFunctionId = $_SESSION['selected_function_id'] ?? null; // ID van de gekozen Functie/Rol


// --- Routing Logica ---
// Check primaire validaties voor de gehele dashboard-flow
if ($loggedInUserId === null) {
    // Gebruiker is niet ingelogd of geen user ID in sessie
    $_SESSION['form_error'] = "U bent niet ingelogd. Log in alstublieft.";
    // Correcte redirect naar landing-page.php vanaf DIT bestand
    // '/../../frontend/pages/landing-page.php' ten opzichte van /app/views/teamManagement.php
    header("Location: " . __DIR__ . "/../../frontend/pages/landing-page.php");
    exit;
} elseif ($selectedOrgId !== null && $selectedOrgId !== 0) {
    // Scenario: Een SPECIFIEKE ORGANISATIE is geselecteerd
    // INCLUDE HET ORGANISATIE-DASHBOARD MET GECORRIGEERD RELATIEF PAD
    // Dit pad is: (vanuit /app/views/) --> 2 mappen omhoog (/public/) --> dan /frontend/pages/ --> dan organization_dashboard.php
    require_once __DIR__ . '/../../frontend/pages/organization_dashboard.php';
    // organization_dashboard.php genereert zelf al de HTML en required de header/template.
    // DUS: Geen verdere HTML/require_once hieronder.

} else {
    // Scenario: Individueel Account geselecteerd (selectedOrgId is null of 0)
    // Toon een persoonlijk dashboard of dwing een keuze af.

    $showHeader = 1;
    $userName = $_SESSION['user']['first_name'] ?? 'Onbekend';
    $headTitle = "Mijn Persoonlijk Dashboard";
    // Correcte redirect voor terug-knop (pad vanaf deze pagina naar landing-page)
    $gobackUrl = __DIR__ . '/../../frontend/pages/landing-page.php';
    $rightAttributes = 0; // Geen extra knoppen

    $bodyContent = "
        <div class='h-[83.5vh] bg-gray-50 shadow-md rounded-tl-xl w-full max-w-full lg:w-13/15 mx-auto p-4 md:p-8'>
            <div class='mb-6 bg-white rounded-lg shadow-sm p-4'>
                <nav class='text-sm text-gray-600'>
                    <a href='" . htmlspecialchars($gobackUrl) . "' class='hover:text-gray-900'>Start</a>
                    <span class='mx-2'>/</span>
                    <span class='font-medium text-gray-800'>Mijn Persoonlijk Dashboard</span>
                </nav>
            </div>
            <h1 class='text-2xl font-bold mb-4 text-gray-800'>Welkom, " . htmlspecialchars($userName) . "!</h1>
            <p class='text-gray-700 mb-6'>Dit is uw persoonlijk dashboard. Geen specifieke organisatie geselecteerd. U bent ingelogd met ID: " . htmlspecialchars($loggedInUserId) . "</p>
            
            <div class='grid grid-cols-1 md:grid-cols-2 gap-6'>
                 <div class='bg-white rounded-lg shadow-sm p-5'>
                    <h3 class='text-lg font-semibold text-gray-800'>Geselecteerde Functie</h3>
                    <p class='text-gray-600 mt-2'>Functie ID: " . htmlspecialchars($selectedFunctionId) . "</p>
                    <p class='text-gray-500 text-sm mt-1'>Dit kan de rol van uw account voorstellen.</p>
                 </div>
                 <div class='bg-blue-600 rounded-lg shadow-lg p-5 flex flex-col items-center justify-center text-white'>
                    <h3 class='text-xl font-bold mb-3'>Start Nieuwe Persoonlijke Vlucht</h3>
                    <a href='" . htmlspecialchars(__DIR__ . '/../../frontend/pages/flight-planning/step1.php') . "' class='bg-white text-blue-600 font-bold py-2 px-6 rounded-full hover:bg-blue-100 transition-colors'>
                        <i class='fa-solid fa-plane mr-2'></i>Mijn Vlucht Starten
                    </a>
                 </div>
            </div>

            <p class='mt-6'><a href='" . htmlspecialchars($gobackUrl) . "' class='text-blue-500 hover:underline'>Wijzig organisatie of functie</a></p>
        </div>
    ";
    // Deze dashboard.php pagina INCLUDEt nu de header en template files als deze content wordt getoond
    // Hier worden de paden aangepast naar hun locatie RELATIEF AAN teamManagement.php (dit bestand)
    require_once __DIR__ . '/../components/header.php'; // Een map omhoog naar 'app', dan naar 'components'
    require_once __DIR__ . '/layouts/template.php';    // Dezelfde map '/layouts'
}
