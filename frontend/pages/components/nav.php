<?php
// /var/www/public/frontend/pages/components/nav.php
// Navigatiecomponent voor het Drone Vluchtvoorbereidingssysteem

// Globale variabelen
global $config, $userName, $org;

// Menu-items
$menuItems = [
    ['url' => '/frontend/pages/dashboard.php', 'icon' => 'fa-chart-line', 'text' => 'Dashboard'],
    ['url' => '/frontend/pages/flight-planning-step1.php', 'icon' => 'fa-map-marked-alt', 'text' => 'Vluchtplanning'],
    ['url' => '/frontend/pages/monitoring.php', 'icon' => 'fa-chart-bar', 'text' => 'Monitoring'],
    ['url' => '/frontend/pages/resources_drones.php', 'icon' => 'fa-folder-open', 'text' => 'Resources'],
    ['url' => '/frontend/pages/teams.php', 'icon' => 'fa-users-cog', 'text' => 'Teambeheer']
];
?>

<nav class="flex-1 flex flex-col px-4 py-6 space-y-2">
    <?php foreach ($menuItems as $item): ?>
        <?php 
            $isActive = $_SERVER['REQUEST_URI'] === $item['url'] ? 'active-menu' : 'text-gray-300 hover:bg-gray-700/50';
        ?>
        <a href="<?php echo htmlspecialchars($item['url']); ?>" 
           class="w-full flex items-center space-x-4 p-4 rounded-lg transition-colors duration-300 <?php echo $isActive; ?>">
            <i class="fa-solid <?php echo $item['icon']; ?> ml-8 text-xl w-6 text-center"></i>
            <span class="text-base font-medium"><?php echo htmlspecialchars($item['text']); ?></span>
        </a>
    <?php endforeach; ?>
</nav>

<div class="sm:hidden absolute bottom-0 w-full bg-gray-900 border-t border-gray-700">
    <div class="flex justify-around items-center h-16">
        <?php foreach (array_slice($menuItems, 0, 4) as $item): ?>
            <a href="<?php echo htmlspecialchars($item['url']); ?>" class="p-3 text-gray-300 hover:text-blue-500 transition-colors">
                <i class="fa-solid <?php echo $item['icon']; ?> text-2xl"></i>
            </a>
        <?php endforeach; ?>
    </div>
</div>