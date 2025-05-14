<?php
// /var/www/public/frontend/pages/components/header.php

// Zorg dat er nooit null naar htmlspecialchars gaat:
$title   = isset($headTitle) && $headTitle !== null ? $headTitle : 'Dashboard';
$orgName = isset($org)       && $org       !== null ? $org       : 'Organisatie A';
?>
<div class="h-[8vh] ml-auto mt-[15px] mr-[30px] bg-white shadow-md rounded-t-xl overflow-y-hidden w-[83.5vw] flex justify-between items-center">
    <div class="pl-[30px]">
        <h1 class="text-2xl font-bold text-gray-900">
            <?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>
        </h1>
        <p class="text-sm text-gray-500">
            Laatste update: 15 minuten geleden, <?php echo htmlspecialchars($orgName, ENT_QUOTES, 'UTF-8'); ?>
        </p>
    </div>
    <div class="flex items-center space-x-4 pr-[30px]">
        <div class="relative">
            <i class="fa-solid fa-bell text-gray-600 hover:text-gray-800 cursor-pointer"></i>
            <span class="absolute -top-1 -right-1 bg-red-600 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center">
                3
            </span>
        </div>
        <a href="/profile.php" class="text-gray-600 hover:text-gray-800">
            <i class="fa-solid fa-user text-xl"></i>
        </a>
    </div>
</div>