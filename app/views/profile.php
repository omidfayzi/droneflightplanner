<?php
// src/app/views/profile.php

// Include core functions
include __DIR__ . '/../../functions.php';
login();

// Retrieve current user
$user = $_SESSION['user'] ?? [];
$userName = htmlspecialchars($user['first_name'] ?? '', ENT_QUOTES, 'UTF-8');

// Page configurations
$headTitle = fetchPropPrefTxt(19) ?: 'Profiel';
$txt = [
    'language_select' => fetchPropPrefTxt(22) ?: 'Selecteer taal',
    'language_nl'     => fetchPropPrefTxt(20) ?: 'Nederlands',
    'language_en'     => fetchPropPrefTxt(21) ?: 'English',
    'logout'          => fetchPropPrefTxt(13) ?: 'Uitloggen',
    'idin_start'      => fetchPropPrefTxt(23) ?: 'Start iDIN',
    'idin_verify'     => fetchPropPrefTxt(24) ?: 'Verifieer identiteit',
    'idin_verified'   => fetchPropPrefTxt(10) ?: 'Geverifieerd',
    'language_saved'  => fetchPropPrefTxt(25) ?: 'Taal opgeslagen',
];
?>
<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($headTitle, ENT_QUOTES) ?></title>
    <!-- Tailwind CSS -->
    <link href="/css/tailwind.css" rel="stylesheet">
    <!-- Custom styles/scripts -->
    <script src="/js/global.js" defer></script>
</head>

<body class="bg-gray-100">

    <header class="bg-white shadow">
        <div class="container mx-auto px-4 py-6 flex justify-between items-center">
            <h1 class="text-2xl font-bold text-gray-800"><?= htmlspecialchars($headTitle, ENT_QUOTES) ?></h1>
            <div class="flex items-center space-x-4">
                <span class="text-gray-600">Welkom, <?= $userName ?></span>
                <a href="/logout.php" class="text-red-600 hover:underline"><?= htmlspecialchars($txt['logout'], ENT_QUOTES) ?></a>
            </div>
        </div>
    </header>

    <main class="container mx-auto px-4 py-8">
        <!-- iDIN Verification Status -->
        <section id="verification-status" class="bg-white rounded-lg shadow p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">iDIN</h2>
            <div id="idin-container" class="text-center"></div>
        </section>

        <!-- Percelen Section -->
        <section class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">Percelen</h2>
            <div class="grid sm:grid-cols-4 gap-4">
                <select id="languageSelect" class="rounded p-2 border">
                    <option value="" disabled selected><?= htmlspecialchars($txt['language_select'], ENT_QUOTES) ?></option>
                    <option value="PropPrefTxt_Nl"><?= htmlspecialchars($txt['language_nl'], ENT_QUOTES) ?></option>
                    <option value="PropPrefTxt_En"><?= htmlspecialchars($txt['language_en'], ENT_QUOTES) ?></option>
                </select>
                <select id="mySelect" class="rounded p-2 border">
                    <option value="" disabled selected><?= htmlspecialchars($txt['language_select'], ENT_QUOTES) ?></option>
                </select>
                <button id="confirmOrgBtn" onclick="confirmOrg()" class="bg-blue-600 text-white px-4 py-2 rounded">Bevestig</button>
            </div>
        </section>
    </main>

    <script>
        // Immediately invoked function scope
        (async function() {
            const keycloakId = "<?= addslashes($user['id'] ?? '') ?>";
            const idinUrl = getUsersWithKeycloak + keycloakId;
            const idinContainer = document.getElementById('idin-container');

            try {
                const res = await fetch(idinUrl);
                if (!res.ok) throw new Error(res.statusText);
                const data = await res.json();
                const users = data.users || [];
                if (users.length) {
                    const last = users.pop();
                    const verified = last.PropPrefUser_IdinCheck === 1;
                    idinContainer.innerHTML = verified ?
                        `<p class="text-green-600 font-medium mb-4"><?= addslashes($txt['idin_verified']) ?></p><img src="/images/idin-logo.svg" alt="iDIN Logo" class="mx-auto">` :
                        `<p class="text-yellow-600 font-medium mb-4"><?= addslashes($txt['idin_verify']) ?></p><button onclick="startIdin()" class="bg-blue-600 text-white px-4 py-2 rounded"><?= addslashes($txt['idin_start']) ?></button>`;
                }
            } catch (err) {
                console.error('iDIN error:', err);
                idinContainer.textContent = 'Kan status niet laden.';
            }

            window.startIdin = async () => {
                try {
                    await processTransactionRequest();
                } catch (err) {
                    console.error('Start iDIN falied:', err);
                }
            };

            // Language selector
            const langSelect = document.getElementById('languageSelect');
            langSelect.addEventListener('change', () => {
                document.cookie = `language_id=${langSelect.value}; path=/; max-age=${100*365*24*60*60}`;
                showPopup("<?= addslashes($txt['language_saved']) ?>", 'success');
            });
            const savedLang = document.cookie.match(/(?:^|;)\s*language_id=([^;]+)/);
            if (savedLang) langSelect.value = savedLang[1];

            // Load organizations
            const orgIds = [];
            try {
                const res = await fetch(cors + userOrgDatabaseUser, {
                    headers: {
                        'Authorization': `Bearer ${userOrgDatabaseBearerToken}`
                    }
                });
                const list = await res.json();
                list.filter(o => o.USR_Keycloak_ID === keycloakId).forEach(o => orgIds.push(o.ORG_ID));
                const res2 = await fetch(cors + userOrgDatabaseOrg, {
                    headers: {
                        'Authorization': `Bearer ${userOrgDatabaseBearerToken}`
                    }
                });
                const orgs = await res2.json();
                const sel = document.getElementById('mySelect');
                orgs.filter(o => orgIds.includes(o.ORG_ID)).forEach(o => {
                    const opt = document.createElement('option');
                    opt.value = o.ORG_ID;
                    opt.textContent = o.ORG_FullName;
                    sel.appendChild(opt);
                });
            } catch (err) {
                console.error('Organisaties laden faalde:', err);
            }

            window.confirmOrg = function() {
                const sel = document.getElementById('mySelect');
                if (!sel.value) return;
                document.cookie = `org_id=${sel.options[sel.selectedIndex].text}; path=/; max-age=${100*365*24*60*60}`;
                location.href = sel.value === 'gebruiker' ? './usr-dashboard.php' : './org-dashboard.php';
            };
        })();
    </script>

</body>

</html>