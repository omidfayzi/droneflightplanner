<?php
// /var/www/public/frontend/pages/template.php
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
                'assets' => '/src/frontend/assets/',
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
            'assets' => '/src/frontend/assets/',
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
    'assets' => '/src/frontend/assets/',
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
    <link rel="stylesheet" href="/src/frontend/assets/styles/custom_styling.scss">
    <script src="/src/frontend/assets/scripts/global120.js"></script>
    <script src="/src/frontend/assets/scripts/idin.js"></script>
    <script src="/src/frontend/assets/scripts/database.js"></script>
    <script src="/src/frontend/assets/scripts/mapbox.js"></script>
    
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
                        'icon' => 'fa-chart-line',
                        'text' => 'Dashboard'
                    ],
                    [
                        'url' => '/frontend/pages/flight-planning-step1.php',
                        'icon' => 'fa-map-marked-alt',
                        'text' => 'Vluchtplanning'
                    ],
                    [
                        'url' => '#',
                        'icon' => 'fa-chart-bar',
                        'text' => 'Monitoring'
                    ],
                    [
                        'url' => '/frontend/pages/resources_drones.php',
                        'icon' => 'fa-folder-open',
                        'text' => 'Resources'
                    ],
                    [
                        'url' => '/frontend/pages/team.php',
                        'icon' => 'fa-users-cog',
                        'text' => 'Teambeheer'
                    ]
                ];

                foreach ($menuItems as $item) {
                    $isActive = $_SERVER['REQUEST_URI'] === $item['url'] ? 'active-menu' : 'text-gray-300 hover:bg-gray-700/50';
                    echo <<<HTML
                    <a href="{$item['url']}" 
                       class="w-full flex items-center space-x-4 p-4 rounded-lg transition-colors duration-300 {$isActive}">
                        <i class="fa-solid {$item['icon']} ml-8 text-xl w-6 text-center"></i>
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
                        Opslaan
                    </button>
                <?php endif; ?>
                <?php if ($rightAttributes == 1): ?>
                    <a href="/sso.php" class="text-gray-600 hover:text-gray-800 p-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                        </svg>
                    </a>
                <?php else: ?>
                    <a href="/frontend/pages/profile.php" class="text-gray-600 hover:text-gray-800 p-2">
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