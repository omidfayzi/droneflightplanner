<?php
// /var/www/public/frontend/pages/components/header.php

// Veiligheid bij null
$title   = isset($headTitle) && $headTitle !== null ? $headTitle : 'Dashboard';
?>
<!--
    - sm:ml-64 zorgt dat de header vanaf sm naast de sidebar staat
    - pl-6 voor extra witruimte tussen sidebar en header-content
-->
<header class="sm:ml-72 bg-white rounded-t-2xl shadow-md h-[12vh] min-h-[72px] flex items-center justify-between px-4 sm:px-8 py-3 select-none transition-all duration-200"
    style="font-family: 'Montserrat', sans-serif;">
    <!-- Optioneel: extra witruimte (alleen desktop) -->
    <div class="pl-0 sm:pl-2 w-full flex flex-col justify-center">
        <h1 class="text-2xl font-extrabold tracking-tight text-gray-900 mb-1"
            style="font-family: 'Montserrat', sans-serif;">
            <?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>
        </h1>
        <p class="text-sm text-gray-500" style="font-family: 'Open Sans', sans-serif;">
            Laatste update: <span class="font-semibold text-gray-700">15 minuten geleden</span>,
        </p>
    </div>

    <!-- Notificatie & Gebruiker -->
    <div class="flex items-center gap-6 pr-2 sm:pr-6">
        <!-- Notificatie (Bell) -->
        <div class="relative group">
            <i class="fa-solid fa-bell text-gray-400 group-hover:text-blue-500 text-xl cursor-pointer transition-all duration-200"></i>
            <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center shadow"
                style="font-family: 'Montserrat', sans-serif; letter-spacing: 0.2px;">
                3
            </span>
        </div>
        <!-- Gebruiker -->
        <a href="/profile.php" class="flex items-center gap-2 group">
            <i class="fa-solid fa-user text-gray-400 group-hover:text-blue-500 text-2xl transition-all duration-200"></i>
            <span class="hidden md:inline text-sm text-gray-700 font-semibold"
                style="font-family: 'Montserrat', sans-serif;">
                Profiel
            </span>
        </a>
    </div>
</header>