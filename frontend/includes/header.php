<?php
// /src/frontend/includes/header.php

// Start de sessie als deze nog niet actief is
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Laad de Composer-autoloader en .env-variabelen
require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';
use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable('/var/www/env');
$dotenv->load();

// Stel standaardwaarden in voor variabelen (pas aan indien nodig)
$overpassApiUrl = $_ENV['OVERPASS_API_URL'] ?? '';
$mainUrl = $_ENV['MAIN_URL'] ?? '';

$bluemToken = $_ENV['BLUEM_TOKEN'] ?? '';
$bluemSenderId = $_ENV['BLUEM_SENDER_ID'] ?? '';
$bluemIdinBrandId = $_ENV['BLUEM_IDIN_BRAND_ID'] ?? '';
$bluemIdealBrandId = $_ENV['BLUEM_IDEAL_BRAND_ID'] ?? '';

$bluemCreateTransaction = $_ENV['BLUEM_CREATE_TRANSACTION_URL'] ?? '';
$bluemRequestTransaction = $_ENV['BLUEM_REQUEST_TRANSACTION_URL'] ?? '';

$mapBoxAccessToken = $_ENV['MAPBOX_ACCESS_TOKEN'] ?? '';
$kadasterBagAccesToken = $_ENV['KADASTER_BAG_ACCESS_TOKEN'] ?? '';
$kadasterKadataAccesToken = $_ENV['KADASTER_KADATA_ACCESS_TOKEN'] ?? '';
$keyCloakClientSecret = $_ENV['KEYCLOAK_CLIENT_SECRET'] ?? '';

// Database-endpoints
$getTextWithId = $_ENV['GET_TEXT_WITH_ID'] ?? '';
$getAllPrefList = $_ENV['GET_ALL_PREF_LIST'] ?? '';
$getHighestIdPrefList = $_ENV['GET_HIGHEST_ID_PREF_LIST'] ?? '';
$getAllPlots = $_ENV['GET_ALL_PLOTS'] ?? '';
$getAllLinkedPreferences = $_ENV['GET_ALL_LINKED_PREFERENCES'] ?? '';
$getAllPreferences = $_ENV['GET_ALL_PREFERENCES'] ?? '';
$getUsersWithKeycloak = $_ENV['GET_USERS_WITH_KEYCLOAK'] ?? '';

$insertLinkedPreferences = $_ENV['INSERT_LINKED_PREFERENCES'] ?? '';
$insertPlot = $_ENV['INSERT_PLOT'] ?? '';
$insertIntoPrefList = $_ENV['INSERT_INTO_PREF_LIST'] ?? '';
$insertIntoUsers = $_ENV['INSERT_INTO_USERS'] ?? '';

$updateIdinCheckAddress = $_ENV['UPDATE_IDIN_CHECK_ADRESS'] ?? '';

$deleteFromPlots = $_ENV['DELETE_FROM_PLOTS'] ?? '';
$deleteFromLinkedPreferencesWithPropId = $_ENV['DELETE_FROM_LINKED_PREFERENCES_WITH_PROP_ID'] ?? '';

// Mapbox
$mapboxGlCss = $_ENV['MAPBOX_GL_CSS'] ?? '';
$mapboxGlJs = $_ENV['MAPBOX_GL_JS'] ?? '';
$mapboxGlGeocoderJs = $_ENV['MAPBOX_GL_GEOCODER_JS'] ?? '';
$mapboxGlGeocoderCss = $_ENV['MAPBOX_GL_GEOCODER_CSS'] ?? '';
$mapBoxReverseGeocoding = $_ENV['MAPBOX_REVERSE_GEOCODING'] ?? '';
$mapboxStaticImageApi = $_ENV['MAPBOX_STATIC_IMAGE_API'] ?? '';

// jQuery & Tailwind
$jquery = $_ENV['JQUERY'] ?? '';
$jqueryScript = $_ENV['JQUERY_SCRIPT'] ?? '';
$tailwindDev = $_ENV['TAILWIND_DEV'] ?? '';

// CORS en organisatie-database
$corsAnywhere = $_ENV['CORS_ANYWHERE'] ?? '';
$userOrgDatabaseUser = $_ENV['USER_ORG_DATABASE_USER'] ?? '';
$userOrgDatabaseOrg = $_ENV['USER_ORG_DATABASE_ORG'] ?? '';
$userOrgDatabaseBearerToken = $_ENV['USER_ORG_DATABASE_BEARER_TOKEN'] ?? '';

// Kadaster (test)
$kadasterUrl = $_ENV['KADASTER_URL'] ?? '';
$kadasterApiKey = $_ENV['KADASTER_API_KEY'] ?? '';

// Laad alle componenten uit de map /src/frontend/pages/components/
$componentsDir = $_SERVER['DOCUMENT_ROOT'] . '/src/frontend/pages/components/';
if (is_dir($componentsDir)) {
    $componentFiles = scandir($componentsDir);
    foreach ($componentFiles as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
            include $componentsDir . $file;
        }
    }
}

// Stel standaardvariabelen in als deze nog niet zijn gezet
if (!isset($showHeader)) {
    $showHeader = 1;
}
if (!isset($userName)) {
    $userName = 'Onbekend';
}
if (!isset($org)) {
    $org = '';
}
if (!isset($headTitle)) {
    $headTitle = 'Drone Vluchtvoorbereidingssysteem';
}
if (!isset($gobackUrl)) {
    $gobackUrl = 0;
}
if (!isset($rightAttributes)) {
    $rightAttributes = 0;
}
if (!isset($bodyContent)) {
    $bodyContent = '<div class="container mx-auto p-4">
        <h1 class="text-3xl font-bold text-gray-900">Welkom bij het Drone Vluchtvoorbereidingssysteem</h1>
        <p class="mt-4 text-gray-700">Gebruik het navigatiemenu om verder te gaan.</p>
    </div>';
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <!-- Laad CSS- en JS-bestanden via de .env-variabelen -->
  <link href="<?php echo $mapboxGlCss; ?>" rel="stylesheet">
  <script src="<?php echo $mapboxGlJs; ?>"></script>
  <script src="<?php echo $jquery; ?>"></script>
  <script src="<?php echo $mapboxGlGeocoderJs; ?>"></script>
  <link rel="stylesheet" href="<?php echo $mapboxGlGeocoderCss; ?>" type="text/css">
  <script src="<?php echo $tailwindDev; ?>"></script>
  <script src="<?php echo $jqueryScript; ?>"></script>
  
  <!-- Laad FontAwesome CSS -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  
  <!-- Eigen styles en scripts -->
  <link rel="stylesheet" href="/src/frontend/assets/styles/custom12.css">
  <script src="/src/frontend/assets/scripts/global120.js"></script>
  <script src="/src/frontend/assets/scripts/idin.js"></script>
  <script src="/src/frontend/assets/scripts/database.js"></script>
  <script src="/src/frontend/assets/scripts/mapbox.js"></script>

  <title><?php echo htmlspecialchars($headTitle); ?></title>
  <!-- Toegevoegde stijl om de achtergrondkleur van het navigatiemenu aan te passen -->
  <style>
    .bg-custom-gray {
      background-color: #313234;
    }
    /* Stijl voor actieve menu-item */
    .active-menu {
      background-color: #2563EB;
      color: white;
    }
  </style>
</head>
<body class="bg-gray-50 font-sans">
<?php if ($showHeader == 1): ?>

    <div class="w-64 sm:h-screen h-16 fixed bottom-0 sm:top-0 z-40 border-r border-gray-200 shadow-xl bg-custom-gray">
    <div class="flex flex-col h-full">
        <!-- Logo Section -->
        <div class="w-full p-6 border-b border-gray-700">
            <a href="/frontend/pages/dashboard.php" class="block hover:opacity-90 transition-opacity">
                <img src="/frontend/assets/images/holding_the_drone_logo.png" 
                     alt="Drone Control" 
                     class="h-28 w-auto object-contain mx-auto p-2.5">
            </a>
        </div>

        <!-- Navigatiemenu -->
        <nav class="flex-1 flex flex-col px-4 py-6 space-y-2">
    <?php
$menuItems = [
    [
        'url' => '/frontend/pages/dashboard.php',
        'icon' => 'fa-chart-line', // Geldige FontAwesome-klasse
        'text' => 'Dashboard'
    ],
    [
        'url' => '/frontend/pages/flight-planning-step1.php',
        'icon' => 'fa-map-marked-alt', // Geldige FontAwesome-klasse
        'text' => 'Vluchtplanning'
    ],
    [
        'url' => '#',
        'icon' => 'fa-chart-bar', // Geldige FontAwesome-klasse
        'text' => 'Monitoring'
    ],
    [
        'url' => '#',
        'icon' => 'fa-folder-open', // Geldige FontAwesome-klasse
        'text' => 'Resources'
    ],
    [
        'url' => '#',
        'icon' => 'fa-users-cog', // Geldige FontAwesome-klasse
        'text' => 'Teambeheer'
    ]
];

    foreach ($menuItems as $item) {
        $isActive = $_SERVER['REQUEST_URI'] === $item['url'] ? 'active-menu' : 'text-gray-300 hover:bg-gray-700/50';
        echo <<<HTML
        <a href="{$item['url']}" 
           class="w-full flex items-center space-x-4 p-4 rounded-lg transition-colors duration-300 {$isActive}">
            <i class="fa-solid {$item['icon']} ml-8 text-xl w-6 text-center "></i>
            <span class="text-base font-medium">{$item['text']}</span>
        </a>
        HTML;
    }
    ?>
</nav>

        <!-- Profile Section -->
        <div class="mt-auto w-full p-4 border-t border-gray-700 flex justify-center items-center my-4">
            <div class="flex items-center space-x-4">
                <div class="relative">
                    <img src="/frontend/assets/images/default-avatar.svg" 
                         class="w-10 h-10 rounded-full border-2 border-gray-600 cursor-pointer"
                         alt="Profile">
                    <div class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 rounded-full border-2 border-gray-900"></div>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-100"><?php echo htmlspecialchars($userName); ?></p>
                    <p class="text-xs text-gray-400"><?php echo htmlspecialchars($org); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="sm:hidden absolute bottom-0 w-full bg-gray-900 border-t border-gray-700">
    <div class="flex justify-around items-center h-16">
        <?php foreach (array_slice($menuItems, 0, 4) as $item): ?>
        <a href="<?= $item['url'] ?>" class="p-3 text-gray-300 hover:text-blue-500 transition-colors">
            <i class="fa-solid <?= $item['icon'] ?> text-2xl"></i>
        </a>
        <?php endforeach; ?>
    </div>
</div>
</div>

<style>
:root {
    --primary-blue: #2563EB;
    --hover-blue: #3B82F6;
    --dark-bg: #1F2937;
    --nav-bg: #111827;
    --text-primary: #F9FAFB;
    --text-secondary: #9CA3AF;
    --active-bg: #2563EB;
    --hover-bg: #374151;
}

body {
    @apply bg-gray-50;
}

.fa-solid {
    @apply transition-colors duration-200;
}
</style>

<!-- Main Content -->
<div class="sm:ml-64 min-h-screen bg-gray-50 p-8">
    <div class="flex justify-between items-center mb-8">
        <div class="flex items-center space-x-4">
            <?php if ($gobackUrl): ?>
                <button class="text-gray-600 hover:text-gray-800 transition-colors p-2 rounded-lg" onclick="history.back()">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>
            <?php endif; ?>
            <h1 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($headTitle); ?></h1>
        </div>
        <div class="flex items-center space-x-4">
            <?php if (isset($saveAttributes)): ?>
                <button id="<?php echo htmlspecialchars($saveAttributes); ?>" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors"
                        onclick="<?php echo htmlspecialchars($saveAttributes); ?>">
                    <?php echo fetchPropPrefTxt(9); ?>
                </button>
            <?php endif; ?>
            <?php if ($rightAttributes == 1): ?>
                <a href="/sso.php" class="text-gray-600 hover:text-gray-800 p-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                    </svg>
                </a>
            <?php else: ?>
                <a href="/src/frontend/pages/profile.php" class="text-gray-600 hover:text-gray-800 p-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </a>
            <?php endif; ?>
        </div>
    </div>
    
    <div id="content" class="bg-white rounded-xl shadow-sm p-6">
        <?php echo $bodyContent; ?>
    </div>
    
    <div id="popup" class="fixed top-20 right-8 z-50 hidden">
        <div id="popup-content" class="bg-blue-600 text-white rounded-lg shadow-xl p-4 w-64 text-center">
            <p id="popup-message" class="font-medium"></p>
        </div>
    </div>
</div>
<?php else: ?>
  <?php echo $bodyContent; ?>
<?php endif; ?>
</body>
</html>