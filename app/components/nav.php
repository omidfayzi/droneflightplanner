<?php
// /var/www/public/app/views/components/nav.php
// Navigatiecomponent voor het Drone Vluchtvoorbereidingssysteem

// Globale variabelen
global $config, $userName, $org;

// Hoofdmenu-items
$menuItems = [
    ['url' => '/app/views/dashboard.php', 'icon' => 'fa-chart-line', 'text' => 'Vluchoverzicht'],
    ['url' => '/app/views/flight-planning/step1.php', 'icon' => 'fa-map-marked-alt', 'text' => 'Vluchtplanning'],
    ['url' => '/app/views/monitoring.php', 'icon' => 'fa-chart-bar', 'text' => 'Monitoring'],
    [
        'icon' => 'fa-folder-open',
        'text' => 'Assets',
        'submenu' => [
            ['url' => '/app/views/assets/drones.php', 'text' => 'Drones'],
            ['url' => '/app/views/assets/addons.php', 'text' => 'Add Ons'],
            ['url' => '/app/views/assets/employees.php', 'text' => 'Personeel'],
            ['url' => '/app/views/assets/verzekeringen.php', 'text' => 'Verzekeringen'],
        ]
    ],
    [
        'icon' => 'fa-file-text',
        'text' => 'Reports',
        'submenu' => [
            ['url' => '/app/views/reports/incidents.php', 'text' => 'Incidenten'],
            ['url' => '/app/views/reports/flight-logs.php', 'text' => 'Vluchten logboek'],
        ]
    ],
    ['url' => '/app/views/organisatie.php', 'icon' => 'fa-solid fa-building', 'text' => 'Organisatie']
];

// Hulpfunctie om te controleren of een URL actief is
function isActiveUrl($url)
{
    return $_SERVER['REQUEST_URI'] === $url;
}

// Hulpfunctie om te controleren of een submenu actief is
function isSubmenuActive($submenu)
{
    foreach ($submenu as $item) {
        if (isActiveUrl($item['url'])) {
            return true;
        }
    }
    return false;
}
?>

<nav class="flex-1 flex flex-col px-4 py-6 space-y-1" id="main-nav">
    <?php foreach ($menuItems as $item): ?>
        <?php if (isset($item['submenu'])): ?>
            <?php
            $isActive = isSubmenuActive($item['submenu']) ? 'active-menu' : 'text-gray-300 hover:bg-gray-700/50';
            $submenuOpen = isSubmenuActive($item['submenu']) ? 'open' : '';
            ?>
            <div class="nav-group <?php echo $submenuOpen; ?>">
                <button class="nav-header w-full flex items-center justify-between p-4 rounded-lg transition-colors duration-300 <?php echo $isActive; ?>">
                    <div class="flex items-center space-x-4">
                        <i class="fa-solid <?php echo $item['icon']; ?> text-xl w-6 text-center"></i>
                        <span class="text-base font-medium"><?php echo htmlspecialchars($item['text']); ?></span>
                    </div>
                    <i class="fa-solid fa-chevron-down text-xs transform transition-transform duration-300 nav-arrow"></i>
                </button>

                <div class="nav-submenu mt-1 ml-6 border-l border-gray-700 pl-3">
                    <?php foreach ($item['submenu'] as $subItem): ?>
                        <?php
                        $isActiveSub = isActiveUrl($subItem['url']) ? 'active-menu' : 'text-gray-300';
                        ?>
                        <a href="<?php echo htmlspecialchars($subItem['url']); ?>"
                            class="w-full flex items-center p-3 pl-4 rounded-lg transition-colors duration-300 mb-1 last:mb-0 <?php echo $isActiveSub; ?> hover:bg-gray-700/50">
                            <span class="text-sm font-medium"><?php echo htmlspecialchars($subItem['text']); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <?php
            $isActive = isActiveUrl($item['url']) ? 'active-menu' : 'text-gray-300 hover:bg-gray-700/50';
            ?>
            <a href="<?php echo htmlspecialchars($item['url']); ?>"
                class="nav-item w-full flex items-center space-x-4 p-4 rounded-lg transition-colors duration-300 <?php echo $isActive; ?>">
                <i class="fa-solid <?php echo $item['icon']; ?> text-xl w-6 text-center"></i>
                <span class="text-base font-medium"><?php echo htmlspecialchars($item['text']); ?></span>
            </a>
        <?php endif; ?>
    <?php endforeach; ?>
</nav>

<div class="sm:hidden absolute bottom-0 w-full bg-gray-900 border-t border-gray-700">
    <div class="flex justify-around items-center h-16">
        <?php
        $mobileItems = array_slice($menuItems, 0, 4);
        foreach ($mobileItems as $item):
            $url = $item['url'] ?? ($item['submenu'][0]['url'] ?? '#');
        ?>
            <a href="<?php echo htmlspecialchars($url); ?>" class="p-3 text-gray-300 hover:text-blue-500 transition-colors">
                <i class="fa-solid <?php echo $item['icon']; ?> text-2xl"></i>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const navGroups = document.querySelectorAll('.nav-group');

        navGroups.forEach(group => {
            const header = group.querySelector('.nav-header');
            const submenu = group.querySelector('.nav-submenu');
            const arrow = group.querySelector('.nav-arrow');

            header.addEventListener('click', function(e) {
                // Sluit alle andere submenu's
                navGroups.forEach(otherGroup => {
                    if (otherGroup !== group) {
                        otherGroup.classList.remove('open');
                        otherGroup.querySelector('.nav-submenu').style.maxHeight = '0';
                        otherGroup.querySelector('.nav-arrow').classList.remove('rotate-180');
                    }
                });

                // Toggle huidige submenu
                group.classList.toggle('open');

                if (group.classList.contains('open')) {
                    submenu.style.maxHeight = submenu.scrollHeight + 'px';
                    arrow.classList.add('rotate-180');
                } else {
                    submenu.style.maxHeight = '0';
                    arrow.classList.remove('rotate-180');
                }
            });

            // Open submenu als het een actief item bevat
            if (group.classList.contains('open')) {
                submenu.style.maxHeight = submenu.scrollHeight + 'px';
                arrow.classList.add('rotate-180');
            } else {
                submenu.style.maxHeight = '0';
            }
        });
    });
</script>

<style>
    .nav-group {
        transition: all 0.3s ease;
    }

    .nav-submenu {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease;
    }

    .nav-group.open .nav-submenu {
        max-height: 500px;
    }

    .nav-arrow {
        transition: transform 0.3s ease;
    }

    .rotate-180 {
        transform: rotate(180deg);
    }

    .nav-header,
    .nav-item {
        transition: background-color 0.2s ease;
    }

    .active-menu {
        background-color: rgba(59, 130, 246, 0.15);
        color: #fff;
    }

    .nav-submenu a {
        position: relative;
    }

    .nav-submenu a.active-menu::before {
        content: '';
        position: absolute;
        left: -7px;
        top: 50%;
        transform: translateY(-50%);
        height: 60%;
        width: 3px;
        background-color: #3b82f6;
        border-radius: 2px;
    }
</style>