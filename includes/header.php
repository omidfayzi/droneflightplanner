<?php
require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';
use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable('/var/www/env');
$dotenv->load();
//include '/var/www/env/env.php';

$overpassApiUrl = $_ENV['OVERPASS_API_URL'];
$mainUrl = $_ENV['MAIN_URL'];
// General access tokens
$bluemToken = $_ENV['BLUEM_TOKEN'];
$bluemSenderId = $_ENV['BLUEM_SENDER_ID'];
$bluemIdinBrandId = $_ENV['BLUEM_IDIN_BRAND_ID'];
$bluemIdealBrandId = $_ENV['BLUEM_IDEAL_BRAND_ID'];

$bluemCreateTransaction = $_ENV['BLUEM_CREATE_TRANSACTION_URL'];
$bluemRequestTransaction = $_ENV['BLUEM_REQUEST_TRANSACTION_URL'];

$mapBoxAccessToken = $_ENV['MAPBOX_ACCESS_TOKEN'];
$kadasterBagAccesToken = $_ENV['KADASTER_BAG_ACCESS_TOKEN'];
$kadasterKadataAccesToken = $_ENV['KADASTER_KADATA_ACCESS_TOKEN'];
$keyCloakClientSecret = $_ENV['KEYCLOAK_CLIENT_SECRET'];

// Links to database
$getTextWithId = $_ENV['GET_TEXT_WITH_ID'];
$getAllPrefList = $_ENV['GET_ALL_PREF_LIST'];
$getHighestIdPrefList = $_ENV['GET_HIGHEST_ID_PREF_LIST'];
$getAllPlots = $_ENV['GET_ALL_PLOTS'];
$getAllLinkedPreferences = $_ENV['GET_ALL_LINKED_PREFERENCES'];
$getAllPreferences = $_ENV['GET_ALL_PREFERENCES'];
$getUsersWithKeycloak = $_ENV['GET_USERS_WITH_KEYCLOAK'];


$insertLinkedPreferences = $_ENV['INSERT_LINKED_PREFERENCES'];
$insertPlot = $_ENV['INSERT_PLOT'];
$insertIntoPrefList = $_ENV['INSERT_INTO_PREF_LIST'];
$insertIntoUsers = $_ENV['INSERT_INTO_USERS'];

$updateIdinCheckAddress = $_ENV['UPDATE_IDIN_CHECK_ADRESS'];

$deleteFromPlots = $_ENV['DELETE_FROM_PLOTS'] ?? '';
$deleteFromLinkedPreferencesWithPropId = $_ENV['DELETE_FROM_LINKED_PREFERENCES_WITH_PROP_ID'] ?? '';

// Mapbox
$mapboxGlCss = $_ENV['MAPBOX_GL_CSS'];
$mapboxGlJs = $_ENV['MAPBOX_GL_JS'];

$mapboxGlGeocoderJs = $_ENV['MAPBOX_GL_GEOCODER_JS'];
$mapboxGlGeocoderCss = $_ENV['MAPBOX_GL_GEOCODER_CSS'];

$mapBoxReverseGeocoding = $_ENV['MAPBOX_REVERSE_GEOCODING'];
$mapboxStaticImageApi = $_ENV['MAPBOX_STATIC_IMAGE_API'];

// jQuery
$jquery = $_ENV['JQUERY'];
$jqueryScript = $_ENV['JQUERY_SCRIPT'];

// Tailwind
$tailwindDev = $_ENV['TAILWIND_DEV'];

// CORS
$corsAnywhere = $_ENV['CORS_ANYWHERE'];
$userOrgDatabaseUser = $_ENV['USER_ORG_DATABASE_USER'];
$userOrgDatabaseOrg = $_ENV['USER_ORG_DATABASE_ORG'];
$userOrgDatabaseBearerToken = $_ENV['USER_ORG_DATABASE_BEARER_TOKEN'];

// Kadaster (test)
$kadasterUrl = $_ENV['KADASTER_URL'];
$kadasterApiKey = $_ENV['KADASTER_API_KEY'];

// add all componments
$directory = $_SERVER['DOCUMENT_ROOT'] . '/pages/componments/';
$files = scandir($directory);
foreach ($files as $file) {
    if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
        include $directory . $file;
    }
}

?>
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">

        <link href="<?php echo $mapboxGlCss ?>" rel="stylesheet">
        <script src="<?php echo $mapboxGlJs ?>"></script>
        <script src="<?php echo $jquery ?>"></script>
        <script src="<?php echo $mapboxGlGeocoderJs ?>"></script>
        <link rel="stylesheet" href="<?php echo $mapboxGlGeocoderCss ?>" type="text/css">
        <!--- @vite('resources/css/app.css') --->
        <script src="<?php echo $tailwindDev ?>"></script>
        <script src="<?php echo $jqueryScript ?>"></script>
        
        <link rel="stylesheet" href="/styles/custom12.css">
        <script src="/scripts/global120.js"></script>
        <script src="/scripts/idin.js"></script>
        <script src="/scripts/database.js"></script>
        <script src="/scripts/mapbox.js"></script>

        <title>PerceelVoorkeuren3</title>
    </head>
    <body>
    <?php if($showHeader == 1) { ?>
        <!-- Sidebar-->
        <div class="w-1/6 sm:h-screen h-12 sm:rounded-none rounded-sm sm:w-[16.6667%] w-screen bg-black bg-opacity-100 fixed bottom-0 z-40">
            <div class="flex sm:flex-col flex-row sm:justify-start justify-center items-center sm:items-center sm:pt-4 sm:space-y-4 space-x-2 sm:space-x-0 max-w-full overflow-hidden z-50 h-full">
                <div class="flex sm:flex-col flex-row items-center sm:justify-center">
                    <div class="sm:mt-0 p-1 mx-auto sm:mr-0 mr-5">
                        <a id="logoLink" href="/">
                            <img src="/images/logo.jpg" alt="Holding the Drones Logo" class="max-w-full sm:max-h-40 sm:h-40 h-8 object-contain">
                        </a>
                    </div>
                    <div class="sm:mt-2 text-center">
                        <h1 class="text-white sm:text-base text-xs">
                        <?php echo fetchPropPrefTxt(14); echo " ";
                        if (isset($userName)) { echo $userName; } ?>
                        </h1>
                        <h1 class="text-white sm:text-base text-xs">
                        <?php
                        if (isset($org)) { echo $org; } ?>
                        </h1>
                    </div>
                    <!-- Hamburger Button -->
                    <button
                        id="menuButton"
                        class="ml-5 block md:hidden focus:outline-none z-60"
                        onclick="toggleMenu()"
                    >
                        <div class="w-6 h-0.5 bg-white mb-1"></div>
                        <div class="w-6 h-0.5 bg-white mb-1"></div>
                        <div class="w-6 h-0.5 bg-white"></div>
                    </button>

                    <!-- Navigation Menu -->
                    <nav id="menu" class="md:flex sm:relative fixed inset-0 bottom-12 hidden">
                        <div id="menu2" class="sm:relative absolute bottom-0 w-full bg-black">
                            <div id="menu3" class="flex flex-col items-center justify-center sm:p-0 sm:pt-4 p-4">
                                <!-- <a href="../index.php" class="m-2 p-2 border-2 border-zinc-700 rounded-xl w-full text-white hover:text-gray-400">Home</a> -->
                                <a href="../dashboard" class="m-2 p-2 border-2 border-zinc-700 rounded-xl w-full text-white hover:text-gray-400">Dashboard</a>
                                <a href="../set-plot-overview" class="m-2 p-2 text-white border-2 border-zinc-700 rounded-xl w-full hover:text-gray-400">Perceel instellen</a>
                                <a href="../set-pref-overview" class="m-2 p-2 text-white border-2 border-zinc-700 rounded-xl w-full hover:text-gray-400">Voorkeuren instellen</a>
                                <a href="../link-pref-overview" class="m-2 p-2 text-white border-2 border-zinc-700 rounded-xl w-full hover:text-gray-400">Voorkeuren koppelen</a>
                            </div>
                        </div>
                    </nav>
                </div>
            </div>

            <div class="absolute sm:fixed bottom-0 w-1/6 bg-black hidden sm:flex">
                <div class="h-full flex justify-center items-center p-2">
                    <div class="w-[90%] max-h-32 overflow-auto">
                        <h1 class="text-white text-center text-xs sm:text-sm">
                            <?php echo $_ENV['DISCLAIMER']; ?>
                        </h1>
                    </div>
                </div>
            </div>
        </div>

        <!-- Body -->
        <div style="background-color: #D9D9D9; background-attachment: fixed;" class="sm:ml-[16.6667%] ml-0 h-screen sm:p-3 overflow-scroll">
            <div class="m-2 p-2">
                <div class="flex justify-between items-center">
                    <div class="flex justify-center items-center">
                       <?php if (isset($gobackUrl)) { ?>
                        <button class="hover:scale-105 transition-all text-white font-bold p-2 rounded-3xl inline-flex items-center" onclick="history.back()">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                        </button> <?php } ?>
                        <h1 class="text-4xl font-bold text-black">
                            <?php if (isset($headTitle)) { echo $headTitle; } else { echo "Default Title"; } ?>
                        </h1>
                    </div>
                <div class="flex justify-center items-center">
                    <div> 
                        <?php 
                            if (isset($saveAttributes)) { ?>
                                <button id="<?php echo $saveAttributes;?>" class="hover:scale-105 transition-all bg-red-800 p-1 pl-4 pr-4 sm:mr-8 mr-4 rounded-xl" onclick="<?php echo $saveAttributes;?>"><?php echo fetchPropPrefTxt(9) ?></button><?php
                            } 
                        ?>
                    </div>
                    <div>
                        <?php   
                            if (isset($rightAttributes)) { 
                                if($rightAttributes == 1) { 
                                    echo "<a href='../sso' class='mb-1 mt-1 pt-1 pl-2 pb-1 text-black flex items-center rounded-3xl hover:scale-105 transition-all'><svg xmlns='http://www.w3.org/2000/svg' width='30' height='30' fill='currentColor' class='bi bi-box-arrow-in-left' viewBox='0 0 16 16'>
                                    <path fill-rule='evenodd' d='M10 3.5a.5.5 0 0 0-.5-.5h-8a.5.5 0 0 0-.5.5v9a.5.5 0 0 0 .5.5h8a.5.5 0 0 0 .5-.5v-2a.5.5 0 0 1 1 0v2A1.5 1.5 0 0 1 9.5 14h-8A1.5 1.5 0 0 1 0 12.5v-9A1.5 1.5 0 0 1 1.5 2h8A1.5 1.5 0 0 1 11 3.5v2a.5.5 0 0 1-1 0z'/>
                                    <path fill-rule='evenodd' d='M4.146 8.354a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L5.707 7.5H14.5a.5.5 0 0 1 0 1H5.707l2.147 2.146a.5.5 0 0 1-.708.708z'/>
                                    </svg></a>"; 
                                } else { 
                                    echo "<a href='../profile' class='text-black font-bold flex items-center'>
                                    <svg xmlns='http://www.w3.org/2000/svg' width='30' height='30' fill='currentColor' class='hover:scale-105 transition-all rounded-2xl bi bi-person-circle' viewBox='0 0 16 16'>
                                    <path d='M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0'/>
                                    <path fill-rule='evenodd' d='M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8m8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1'/>
                                    </svg></a>";
                                } 
                            } 
                        ?>
                    </div>
                </div>
            </div>
            </div>
            <div id="popup" class="fixed top-20 right-4 z-50 hidden">
                <div id="popup-content" class="bg-white rounded-lg shadow-lg p-4 w-80 text-center transition-opacity duration-500 opacity-100">
                    <p id="popup-message" class="font-bold"></p>
                </div>
            </div>
            <?php } ?>
            <?php if (isset($bodyContent)) { echo $bodyContent; } ?>
        </div>
    </body>
</html>
<script>

  function toggleMenu() {
  const menu = document.getElementById('menu');

    if (menu.classList.contains('hidden')) {
        menu.classList.remove('hidden');
    } else {
        menu.classList.add('hidden');
    }
  }
    const overpassApiUrl = "<?php echo $_ENV['OVERPASS_API_URL']; ?>";
    const mainUrl = "<?php echo $_ENV['MAIN_URL']; ?>";

    const bluemToken = "<?php echo $_ENV['BLUEM_TOKEN']; ?>";
    const bluemSenderId = "<?php echo $_ENV['BLUEM_SENDER_ID']; ?>";
    const bluemIdinBrandId = "<?php echo $_ENV['BLUEM_IDIN_BRAND_ID']; ?>";
    const bluemIdealBrandId = "<?php echo $_ENV['BLUEM_IDEAL_BRAND_ID']; ?>";

    const bluemCreateTransaction = "<?php echo $_ENV['BLUEM_CREATE_TRANSACTION_URL']; ?>";
    const bluemRequestTransaction = "<?php echo $_ENV['BLUEM_REQUEST_TRANSACTION_URL']; ?>";

    const mapBoxAccessToken = "<?php echo $_ENV['MAPBOX_ACCESS_TOKEN']; ?>";
    const kadasterBagAccessToken = "<?php echo $_ENV['KADASTER_BAG_ACCESS_TOKEN']; ?>";
    const kadasterKadataAccessToken = "<?php echo $_ENV['KADASTER_KADATA_ACCESS_TOKEN']; ?>";
    const keyCloakClientSecret = "<?php echo $_ENV['KEYCLOAK_CLIENT_SECRET']; ?>";

    // Links to database
    const getTextWithId = "<?php echo $_ENV['GET_TEXT_WITH_ID']; ?>";
    const getAllPrefList = "<?php echo $_ENV['GET_ALL_PREF_LIST']; ?>";
    const getHighestIdPrefList = "<?php echo $_ENV['GET_HIGHEST_ID_PREF_LIST']; ?>";
    const getAllPlots = "<?php echo $_ENV['GET_ALL_PLOTS']; ?>";
    const getAllLinkedPreferences = "<?php echo $_ENV['GET_ALL_LINKED_PREFERENCES']; ?>";
    const getAllPreferences = "<?php echo $_ENV['GET_ALL_PREFERENCES']; ?>";
    const getUsersWithKeycloak = "<?php echo $_ENV['GET_USERS_WITH_KEYCLOAK']; ?>";

    const insertLinkedPreferences = "<?php echo $_ENV['INSERT_LINKED_PREFERENCES']; ?>";
    const insertPlot = "<?php echo $_ENV['INSERT_PLOT']; ?>";
    const insertIntoPrefList = "<?php echo $_ENV['INSERT_INTO_PREF_LIST']; ?>";
    const insertIntoUsers2 = "<?php echo $_ENV['INSERT_INTO_USERS']; ?>";

    const updateIdinCheckAddress2 = "<?php echo $_ENV['UPDATE_IDIN_CHECK_ADRESS']; ?>";

    // Mapbox
    const mapboxGlCss = "<?php echo $_ENV['MAPBOX_GL_CSS']; ?>";
    const mapboxGlJs = "<?php echo $_ENV['MAPBOX_GL_JS']; ?>";

    const mapboxGlGeocoderJs = "<?php echo $_ENV['MAPBOX_GL_GEOCODER_JS']; ?>";
    const mapboxGlGeocoderCss = "<?php echo $_ENV['MAPBOX_GL_GEOCODER_CSS']; ?>";

    const mapBoxReverseGeocoding = "<?php echo $_ENV['MAPBOX_REVERSE_GEOCODING']; ?>";
    const mapboxStaticImageApi = "<?php echo $_ENV['MAPBOX_STATIC_IMAGE_API']; ?>";

    // jQuery
    const jquery = "<?php echo $_ENV['JQUERY']; ?>";
    const jqueryScript = "<?php echo $_ENV['JQUERY_SCRIPT']; ?>";

    // Tailwind
    const tailwindDev = "<?php echo $_ENV['TAILWIND_DEV']; ?>";

    // CORS
    const corsAnywhere = "<?php echo $_ENV['CORS_ANYWHERE']; ?>";
    const userOrgDatabaseUser = "<?php echo $_ENV['USER_ORG_DATABASE_USER']; ?>";
    const userOrgDatabaseOrg = "<?php echo $_ENV['USER_ORG_DATABASE_ORG']; ?>";
    const userOrgDatabaseBearerToken = "<?php echo $_ENV['USER_ORG_DATABASE_BEARER_TOKEN']; ?>";

    // Kadaster (test)
    const kadasterUrl = "<?php echo $_ENV['KADASTER_URL']; ?>";
    const kadasterApiKey = "<?php echo $_ENV['KADASTER_API_KEY']; ?>";

    // Keycloak
    const keycloakAuthServerUrl = "<?php echo $_ENV['KEYCLOAK_AUTH_SERVER_URL']; ?>";
    const keycloakRealm = "<?php echo $_ENV['KEYCLOAK_REALM']; ?>";
    const keycloakClientId = "<?php echo $_ENV['KEYCLOAK_CLIENT_ID']; ?>";
    const keycloakRedirectUri = "<?php echo $_ENV['KEYCLOAK_REDIRECT_URI']; ?>";

    const deleteFromPlots = "<?php echo $_ENV['DELETE_FROM_PLOTS']; ?>";
    const deleteFromLinkedPreferencesWithPropId = "<?php echo $_ENV['DELETE_FROM_LINKED_PREFERENCES_WITH_PROP_ID']; ?>";

</script>

