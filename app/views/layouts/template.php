<?php
// /var/www/public/app/pages/template.php
// Hoofdtemplate voor de Drone Vluchtvoorbereidingssysteem pagina's

// Start sessie als deze nog niet is gestart
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Laad configuratie met foutafhandeling
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
$org = $_SESSION['org'] ?? $defaults['org'] ?? '';
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
    <div class="w-64 sm:h-screen h-16 fixed bottom-0 sm:top-0 z-40 border-r border-gray-200 shadow-xl bg-custom-gray">
        <div class="flex flex-col h-full">
            <!-- Logo Section -->
            <div class="w-full p-6 border-b border-gray-700">
                <a href="/app/pages/dashboard.php" class="block hover:opacity-90 transition-opacity">
                    <img src="/app/assets/images/holding_the_drone_logo.png" 
                         alt="Drone Control" 
                         class="h-28 w-auto object-contain mx-auto p-2.5">
                </a>
            </div>

            <!-- Include Navigatie Component -->
            <?php require_once __DIR__ . '/../../components/nav.php'; ?>

            <!-- Profile Section -->
            <div class="mt-auto w-full p-4 border-t border-gray-700 flex justify-center items-center my-4">
                <div class="flex items-center space-x-4">
                    <a href="/src/app/pages/profile.php">
                        <div class="relative">
                            <img src="/app/assets/images/default-avatar.svg" 
                                class="w-10 h-10 rounded-full border-2 border-gray-600 cursor-pointer"
                                alt="Profile">
                            <div class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 rounded-full border-2 border-gray-900"></div>
                        </div>
                    </a>
                    <div>
                        <p class="text-sm font-medium text-gray-100"><?php echo htmlspecialchars($userName); ?></p>
                        <p class="text-xs text-gray-400"><?php echo htmlspecialchars($org); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="sm:hidden absolute bottom-0 w-full bg-gray-900 border-t border-gray-700">
            <!-- Include Mobiele Navigatie Component -->
            <?php require_once __DIR__ . '/../../components/nav.php'; ?>
        </div>
    </div>

    <!-- Main Content of the page -->
    <div class="sm:ml-64 h-[90h] bg-gray-50 p-8 overflow-hidden">
        <?php echo $bodyContent; ?>
    </div>
    
<?php else: ?>
    <?php echo $bodyContent; ?>
<?php endif; ?>
</body>
</html>