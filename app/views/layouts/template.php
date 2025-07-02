<?php
// /var/www/public/app/pages/template.php
// Hoofdtemplate voor de Drone Vluchtvoorbereidingssysteem pagina's

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Dynamisch de geselecteerde organisatie ophalen ---
$selectedOrgId = $_SESSION['selected_organisation_id'] ?? null;
$orgNaam = '';
$orgLogoUrl = '/app/assets/images/default-org-logo.svg'; // Fallback/logo placeholder

if ($selectedOrgId) {
    // Pas hier de juiste backend-API-URL aan indien nodig
    $apiUrl = 'http://devserv01.holdingthedrones.com:3006/organisaties/' . $selectedOrgId;
    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
        CURLOPT_TIMEOUT => 10,
    ]);
    $resp = curl_exec($ch);
    curl_close($ch);

    $data = @json_decode($resp, true);

    if (is_array($data) && !isset($data['error'])) {
        $orgNaam = $data['organisatienaam'] ?? '';
        $orgLogoUrl = (!empty($data['logoUrl'])) ? $data['logoUrl'] : $orgLogoUrl;
    }
}

// --- Rest van je config inladen zoals jij had ---
$configPath = __DIR__ . '/../../config/config.php';
if (file_exists($configPath)) {
    $config = require_once $configPath;
    if (!is_array($config)) {
        // Fallback als config.php geen array retourneert
        $config = [
            'defaults' => [
                'showHeader' => 1,
                'userName' => 'Onbekend',
                'org' => '',
                'headTitle' => 'Drone Vluchtvoorbereidingssysteem',
                'gobackUrl' => 0,
                'rightAttributes' => 0,
                'bodyContent' => '<div class="container mx-auto p-4"><h1 class="text-3xl font-bold text-gray-900">Welkom bij het Drone Vluchtvoorbereidingssysteem</h1><p class="mt-4 text-gray-700">Gebruik het navigatiemenu om verder te gaan.</p></div>',
            ],
            'paths' => [
                'assets' => '/src/app/assets/',
            ],
            'env' => [
                'mapboxGlCss' => 'https://api.mapbox.com/mapbox-gl-js/v2.11.0/mapbox-gl.css',
                'mapboxGlJs' => 'https://api.mapbox.com/mapbox-gl-js/v2.11.0/mapbox-gl.js',
                'jquery' => 'https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js',
                'mapboxGlGeocoderJs' => 'https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v5.0.0/mapbox-gl-geocoder.min.js',
                'mapboxGlGeocoderCss' => 'https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v5.0.0/mapbox-gl-geocoder.css',
                'tailwindDev' => 'https://cdn.tailwindcss.com',
                'jqueryScript' => 'https://code.jquery.com/jquery-3.6.0.min.js',
            ],
        ];
    }
} else {
    // Fallback als config.php niet bestaat
    $config = [
        'defaults' => [
            'showHeader' => 1,
            'userName' => 'Onbekend',
            'org' => '',
            'headTitle' => 'Drone Vluchtvoorbereidingssysteem',
            'gobackUrl' => 0,
            'rightAttributes' => 0,
            'bodyContent' => '<div class="container mx-auto p-4"><h1 class="text-3xl font-bold text-gray-900">Welkom bij het Drone Vluchtvoorbereidingssysteem</h1><p class="mt-4 text-gray-700">Gebruik het navigatiemenu om verder te gaan.</p></div>',
        ],
        'paths' => [
            'assets' => '/src/app/assets/',
        ],
        'env' => [
            'mapboxGlCss' => 'https://api.mapbox.com/mapbox-gl-js/v2.11.0/mapbox-gl.css',
            'mapboxGlJs' => 'https://api.mapbox.com/mapbox-gl-js/v2.11.0/mapbox-gl.js',
            'jquery' => 'https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js',
            'mapboxGlGeocoderJs' => 'https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v5.0.0/mapbox-gl-geocoder.min.js',
            'mapboxGlGeocoderCss' => 'https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v5.0.0/mapbox-gl-geocoder.css',
            'tailwindDev' => 'https://cdn.tailwindcss.com',
            'jqueryScript' => 'https://code.jquery.com/jquery-3.6.0.min.js',
        ],
    ];
}

// Haal defaults en paths op met fallbacks
$defaults = $config['defaults'] ?? [
    'showHeader' => 1,
    'userName' => 'Onbekend',
    'org' => '',
    'headTitle' => 'Drone Vluchtvoorbereidingssysteem',
    'gobackUrl' => 0,
    'rightAttributes' => 0,
    'bodyContent' => '<div class="container mx-auto p-4"><h1 class="text-3xl font-bold text-gray-900">Welkom bij het Drone Vluchtvoorbereidingssysteem</h1><p class="mt-4 text-gray-700">Gebruik het navigatiemenu om verder te gaan.</p></div>',
];
$paths = $config['paths'] ?? [
    'assets' => '/src/app/assets/',
];
$env = $config['env'] ?? [
    'mapboxGlCss' => 'https://api.mapbox.com/mapbox-gl-js/v2.11.0/mapbox-gl.css',
    'mapboxGlJs' => 'https://api.mapbox.com/mapbox-gl-js/v2.11.0/mapbox-gl.js',
    'jquery' => 'https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js',
    'mapboxGlGeocoderJs' => 'https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v5.0.0/mapbox-gl-geocoder.min.js',
    'mapboxGlGeocoderCss' => 'https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v5.0.0/mapbox-gl-geocoder.css',
    'tailwindDev' => 'https://cdn.tailwindcss.com',
    'jqueryScript' => 'https://code.jquery.com/jquery-3.6.0.min.js',
];

// Haal pagina-specifieke of standaardvariabelen met veilige fallbacks
$showHeader = $showHeader ?? $defaults['showHeader'] ?? 1;
$userName = $_SESSION['user']['first_name'] ?? $defaults['userName'] ?? 'Onbekend';
// *** ORG IS NU OVERBODIG DOOR DYNAMISCHE NAAM! ***
$headTitle = $headTitle ?? $defaults['headTitle'] ?? 'Drone Vluchtvoorbereidingssysteem';
$gobackUrl = $gobackUrl ?? $defaults['gobackUrl'] ?? 0;
$rightAttributes = $rightAttributes ?? $defaults['rightAttributes'] ?? 0;
$bodyContent = $bodyContent ?? $defaults['bodyContent'] ?? '<div class="container mx-auto p-4"><h1 class="text-3xl font-bold text-gray-900">Welkom bij het Drone Vluchtvoorbereidingssysteem</h1><p class="mt-4 text-gray-700">Gebruik het navigatiemenu om verder te gaan.</p></div>';

// Laad alle componenten uit de map /src/app/pages/components/
$componentsDir = $_SERVER['DOCUMENT_ROOT'] . '/src/app/pages/components/';
if (is_dir($componentsDir)) {
    $componentFiles = scandir($componentsDir);
    foreach ($componentFiles as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
            include $componentsDir . $file;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($headTitle); ?></title>
    <!-- Laad CSS- en JS-bestanden via de env-array met fallbacks -->
    <link href="<?php echo $env['mapboxGlCss']; ?>" rel="stylesheet">
    <script src="<?php echo $env['mapboxGlJs']; ?>"></script>
    <script src="<?php echo $env['jquery']; ?>"></script>
    <script src="<?php echo $env['mapboxGlGeocoderJs']; ?>"></script>
    <link rel="stylesheet" href="<?php echo $env['mapboxGlGeocoderCss']; ?>" type="text/css">
    <script src="<?php echo $env['tailwindDev']; ?>"></script>
    <script src="<?php echo $env['jqueryScript']; ?>"></script>

    <!-- Laad FontAwesome CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Eigen styles en scripts -->
    <link rel="stylesheet" href="/src/app/assets/styles/custom_styling.scss">
    <script src="/src/app/assets/scripts/global120.js"></script>
    <script src="/src/app/assets/scripts/idin.js"></script>

    <style>
        body {
            overflow-y: hidden;
        }

        .bg-custom-gray {
            background-color: #313234;
        }

        .active-menu {
            background-color: #2563EB;
            color: white;
        }
    </style>
</head>

<body class="bg-gray-50 font-sans">
    <?php if ($showHeader == 1): ?>

        <div class="w-64 sm:h-screen h-16 fixed bottom-0 sm:top-0 z-40 border-r border-gray-800 shadow-xl bg-gradient-to-b from-gray-900 to-gray-800">
            <div class="flex flex-col h-full">
                <!-- Logo Sectie -->
                <div class="w-full p-7 bg-gray-800 border-b border-gray-700">
                    <a href="/app/views/dashboard.php" class="block hover:opacity-90 transition-opacity">
                        <div class="flex items-center justify-center space-x-3">
                            <div class="relative">
                                <div class="w-12 h-12 rounded-full bg-gradient-to-r from-blue-600 to-blue-800 flex items-center justify-center text-white text-2xl overflow-hidden">
                                    <img src="<?php echo htmlspecialchars($orgLogoUrl); ?>" alt="Organisatie Logo" class="w-full h-full object-cover">
                                </div>
                                <div class="absolute -top-1 -right-1 w-5 h-5 rounded-full bg-green-500 flex items-center justify-center text-white text-xs">
                                    <i class="fa-solid fa-bolt"></i>
                                </div>
                            </div>
                            <div>
                                <div class="text-gray-100 font-montserrat font-bold text-xl tracking-wide">
                                    <?php echo $orgNaam ? htmlspecialchars($orgNaam) : "DRONE"; ?>
                                </div>
                                <div class="text-blue-400 font-montserrat font-medium text-sm tracking-wider">
                                    FLIGHT PLANNER
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Navigatie Sectie -->
                <div class="p-2 flex-1 overflow-y-auto bg-gray-900">
                    <?php require_once __DIR__ . '/../../components/nav.php'; ?>
                </div>

                <!-- Profiel Sectie -->
                <div class="w-full p-2 bg-gray-900 border-t border-gray-700">
                    <div class="flex items-center space-x-4 bg-gray-800 p-3 rounded-lg">
                        <a href="/../profile.php" class="flex-shrink-0">
                            <div class="relative">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-r from-blue-500 to-blue-700 flex items-center justify-center text-white font-montserrat font-bold">
                                    <?php echo strtoupper(substr($userName, 0, 1)); ?>
                                </div>
                                <div class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 rounded-full border-2 border-gray-800"></div>
                            </div>
                        </a>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-200 font-montserrat truncate">
                                <?php echo htmlspecialchars($userName ?? ""); ?>
                            </p>
                            <p class="text-xs text-gray-400 font-open-sans truncate">
                                <?php
                                // Dynamisch de gekozen functie tonen, fallback indien niet gezet
                                echo isset($_SESSION['selected_function_name']) && $_SESSION['selected_function_name']
                                    ? htmlspecialchars($_SESSION['selected_function_name'])
                                    : 'Geen rol geselecteerd';
                                ?>
                            </p>
                        </div>
                        <a href="/logout.php" class="ml-auto text-gray-400 hover:text-red-400 transition-colors">
                            <i class="fa-solid fa-right-from-bracket"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Mobiele navigatiebalk (onderaan) -->
            <div class="sm:hidden absolute bottom-0 w-full bg-gray-900 border-t border-gray-800 shadow-[0_-4px_8px_rgba(0,0,0,0.4)]">
                <div class="flex justify-around items-center h-16">
                    <?php
                    $mobileMenuItems = array_slice($menuItems, 0, 4);
                    foreach ($mobileMenuItems as $item):
                        $isActive = $_SERVER['REQUEST_URI'] === $item['url']
                            ? 'text-blue-400'
                            : 'text-gray-500';
                    ?>
                        <a href="<?php echo htmlspecialchars($item['url']); ?>"
                            class="p-3 <?php echo $isActive; ?> hover:text-blue-300 transition-colors relative group">
                            <i class="fa-solid <?php echo $item['icon']; ?> text-2xl"></i>
                            <span class="absolute bottom-0 left-1/2 transform -translate-x-1/2 text-xs font-montserrat font-medium text-gray-300 group-hover:text-blue-300 opacity-0 group-hover:opacity-100 transition-opacity duration-200 bg-gray-800 px-2 py-1 rounded shadow-lg">
                                <?php echo htmlspecialchars($item['text']); ?>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Main Content of the page -->
        <div class="sm:ml-64 h-[90h] bg-gray-50 p-8 overflow-hidden">
            <?php echo $bodyContent; ?>
            <footer class="m-0 py-6 text-gray-300">
                <div class="container mx-auto px-4">
                    <div class="flex flex-col md:flex-row justify-between items-center">
                        <div class="mb-4 md:mb-0">
                            <div class="flex items-center space-x-3">
                                <div class="bg-gray-700 p-2 rounded-lg">
                                    <i class="fa-solid fa-drone text-blue-400 text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="text-lm font-semibold">
                                        <p><?php echo date('Y'); ?> Holding The Drones. Alle rechten voorbehouden.</p>
                                    </h3>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col items-center md:items-end">
                            <div class="flex space-x-6 mb-3">
                                <a href="/app/views/legal.php"
                                    class="text-gray-300 hover:text-white transition-colors"
                                    title="Privacyverklaring">
                                    <i class="fa-solid fa-lock mr-1"></i> Privacy
                                </a>
                                <a href="/app/views/legal.php"
                                    class="text-gray-300 hover:text-white transition-colors"
                                    title="Algemene Verordening Gegevensbescherming">
                                    <i class="fa-solid fa-file-contract mr-1"></i> AVG
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-gray-700 mt-6 pt-4 text-center text-gray-500 text-sm">
                        <p>&copy; <?php echo date('Y'); ?> <?php echo $orgNaam ? htmlspecialchars($orgNaam) : "Drone Flight Planner"; ?>. Alle rechten voorbehouden.</p>
                        <p class="mt-1">v1.2.0 | Systeemstatus: <span class="text-green-400">Operationeel</span></p>
                    </div>
                </div>
            </footer>
        </div>

    <?php else: ?>
        <?php echo $bodyContent; ?>
    <?php endif; ?>
</body>

</html>