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
$componentsDir = $_SERVER['DOCUMENT_ROOT'] . '/frontend/pages/components/';
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
  
  <!-- Eigen styles en scripts -->
  <link rel="stylesheet" href="/frontend/assets/styles/custom12.css">
  <script src="/frontend/assets/scripts/global120.js"></script>
  <script src="/frontend/assets/scripts/idin.js"></script>
  <script src="/frontend/assets/scripts/database.js"></script>
  <script src="/frontend/assets/scripts/mapbox.js"></script>

  <title><?php echo htmlspecialchars($headTitle); ?></title>
</head>
<body class="bg-gray-100 font-sans">
<?php if ($showHeader == 1): ?>
  <!-- Sidebar (Navigatie) -->
  <div class="w-1/6 sm:h-screen h-12 sm:rounded-none rounded-sm sm:w-[16.6667%] w-full bg-black bg-opacity-100 fixed bottom-0 sm:top-0 z-40 shadow-lg">
    <div class="flex sm:flex-col flex-row sm:justify-start justify-center items-center sm:pt-4 sm:space-y-4 space-x-2 sm:space-x-0 h-full">
      <div class="flex sm:flex-col flex-row items-center sm:justify-center">
        <div class="sm:mt-0 p-1 mx-auto sm:mr-0 mr-5">
          <a id="logoLink" href="/frontend/pages/dashboard.php">
            <img src="/frontend/assets/images/logo.jpg" alt="Holding the Drones Logo" class="max-w-full sm:max-h-40 sm:h-40 h-8 object-contain">
          </a>
        </div>
        <div class="sm:mt-2 text-center">
          <h1 class="text-white sm:text-base text-xs">
            <?php echo (function_exists('fetchPropPrefTxt') ? fetchPropPrefTxt(14) : '') . " " . htmlspecialchars($userName); ?>
          </h1>
          <h1 class="text-white sm:text-base text-xs">
            <?php echo htmlspecialchars($org); ?>
          </h1>
        </div>
        <!-- Hamburger-knop (alleen op mobiel) -->
        <button id="menuButton" class="ml-5 block sm:hidden focus:outline-none z-50" onclick="toggleMenu()">
          <div class="w-6 h-0.5 bg-white mb-1"></div>
          <div class="w-6 h-0.5 bg-white mb-1"></div>
          <div class="w-6 h-0.5 bg-white"></div>
        </button>
        <!-- Navigatiemenu -->
        <nav id="menu" class="sm:flex sm:relative fixed inset-0 sm:inset-auto sm:bottom-auto bottom-12 hidden sm:h-auto bg-black sm:bg-transparent w-full sm:w-auto">
          <div class="sm:relative absolute bottom-0 w-full sm:w-auto bg-black sm:bg-transparent">
            <div id="menu3" class="flex flex-col items-center justify-center sm:p-0 sm:pt-4 p-4 space-y-2">
              <!-- Enkel de twee gewenste menu-items -->
              <a href="/frontend/pages/dashboard.php" class="m-2 p-2 border-2 border-gray-700 rounded-xl w-full text-white hover:text-gray-400 text-center">Dashboard</a>
              <a href="/frontend/pages/flight-planning-step1.php?step=1" class="m-2 p-2 text-white border-2 border-gray-700 rounded-xl w-full hover:text-gray-400 text-center">Vluchtplanning</a>
            </div>
          </div>
        </nav>
      </div>
    </div>
    <div class="absolute sm:fixed bottom-0 w-1/6 bg-black hidden sm:flex">
      <div class="h-full flex justify-center items-center p-2">
        <div class="w-[90%] max-h-32 overflow-auto">
          <h1 class="text-white text-center text-xs sm:text-sm">
            <?php echo htmlspecialchars($_ENV['DISCLAIMER']); ?>
          </h1>
        </div>
      </div>
    </div>
  </div>
  <!-- Main Content (met marge zodat de sidebar de content niet bedekt) -->
  <div class="sm:ml-[16.6667%] min-h-screen bg-gray-100 sm:p-3 overflow-auto">
    <div class="m-2 p-2">
      <div class="flex justify-between items-center">
        <div class="flex items-center space-x-4">
          <?php if ($gobackUrl): ?>
            <button class="hover:scale-105 transition-all text-gray-800 font-bold p-2 rounded-full inline-flex items-center" onclick="history.back()">
              <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
              </svg>
            </button>
          <?php endif; ?>
          <h1 class="text-3xl sm:text-4xl font-bold text-gray-900"><?php echo htmlspecialchars($headTitle); ?></h1>
        </div>
        <div class="flex items-center space-x-4">
          <?php if (isset($saveAttributes)): ?>
            <button id="<?php echo htmlspecialchars($saveAttributes); ?>" class="hover:scale-105 transition-all bg-red-600 text-white p-2 px-4 rounded-xl" onclick="<?php echo htmlspecialchars($saveAttributes); ?>">
              <?php echo fetchPropPrefTxt(9); ?>
            </button>
          <?php endif; ?>
          <?php if ($rightAttributes == 1): ?>
            <a href="/sso.php" class="text-gray-800 flex items-center rounded-full hover:scale-105 transition-all">
              <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="currentColor" class="bi bi-box-arrow-in-left" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M10 3.5a.5.5 0 0 0-.5-.5h-8a.5.5 0 0 0-.5.5v9a.5.5 0 0 0 .5.5h8a.5.5 0 0 0 .5-.5v-2a.5.5 0 0 1 1 0v2A1.5 1.5 0 0 1 9.5 14h-8A1.5 1.5 0 0 1 0 12.5v-9A1.5 1.5 0 0 1 1.5 2h8A1.5 1.5 0 0 1 11 3.5v2a.5.5 0 0 1-1 0z"/>
                <path fill-rule="evenodd" d="M4.146 8.354a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L5.707 7.5H14.5a.5.5 0 0 1 0 1H5.707l2.147 2.146a.5.5 0 0 1-.708.708z"/>
              </svg>
            </a>
          <?php else: ?>
            <a href="/frontend/pages/profile.php" class="text-gray-800 flex items-center hover:scale-105 transition-all">
              <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="currentColor" class="rounded-full" viewBox="0 0 16 16">
                <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0"/>
                <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8m8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1"/>
              </svg>
            </a>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <div id="content" class="px-4 py-2">
      <?php echo $bodyContent; ?>
    </div>
    <div id="popup" class="fixed top-20 right-4 z-50 hidden">
      <div id="popup-content" class="bg-white rounded-lg shadow-lg p-4 w-80 text-center transition-opacity duration-500 opacity-100">
        <p id="popup-message" class="font-bold text-gray-800"></p>
      </div>
    </div>
  </div>
<?php else: ?>
  <?php echo $bodyContent; ?>
<?php endif; ?>
</body>
</html>
<script>
function toggleMenu() {
  document.getElementById('menu').classList.toggle('hidden');
}
</script>
