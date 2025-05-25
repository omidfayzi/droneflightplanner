<?php
// app/views/profile.php
require_once __DIR__ . '/../../functions.php';
login();

$user = $_SESSION['user'] ?? [];
$userName = htmlspecialchars($user['first_name'] ?? 'Onbekend', ENT_QUOTES, 'UTF-8');

$txt = [
    'title' => fetchPropPrefTxt(19) ?: 'Profiel',
    'language' => fetchPropPrefTxt(22) ?: 'Taal',
    'language_nl' => fetchPropPrefTxt(20) ?: 'Nederlands',
    'language_en' => fetchPropPrefTxt(21) ?: 'Engels',
    'logout' => fetchPropPrefTxt(13) ?: 'Uitloggen',
    'idin_start' => fetchPropPrefTxt(23) ?: 'Start verificatie',
    'idin_unverified' => fetchPropPrefTxt(24) ?: 'Identiteit niet geverifieerd',
    'idin_verified' => fetchPropPrefTxt(10) ?: 'Geverifieerd',
    'organization' => fetchPropPrefTxt(26) ?: 'Organisatie',
    'verify_id' => fetchPropPrefTxt(29) ?: 'Identiteitsverificatie',
    'save' => fetchPropPrefTxt(25) ?: 'Opgeslagen'
];

$headTitle = $txt['title'];
$showHeader = 1;
$gobackUrl = 1;

// Header includen
$headerPath = file_exists(__DIR__ . '/../components/header.php')
    ? __DIR__ . '/../components/header.php'
    : __DIR__ . '/../../components/header.php';

if (!file_exists($headerPath)) {
    die('<div class="error">Header component niet gevonden</div>');
}
?>
<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $headTitle ?></title>
    <style>
        /* Basisstijlen */
        :root {
            --primary: #313234;
            --secondary: #2563EB;
            --background: #F3F4F6;
            --surface: #FFFFFF;
            --alt-surface: #F9FAFB;
        }

        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: var(--background);
            color: var(--primary);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        /* Profielkaart */
        .profile-card {
            background: var(--surface);
            border-radius: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        /* Profielheader */
        .profile-header {
            background: var(--primary);
            padding: 1.5rem;
            color: white;
        }

        .profile-header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }

        .avatar {
            width: 3.5rem;
            height: 3.5rem;
            border-radius: 50%;
            background: var(--secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: bold;
        }

        /* Instellingen */
        .settings-grid {
            display: grid;
            gap: 2rem;
            padding: 2rem;
        }

        @media (min-width: 768px) {
            .settings-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .setting-group {
            margin-bottom: 1.5rem;
        }

        .setting-group h2 {
            margin: 0 0 1rem 0;
            font-size: 1.125rem;
            font-weight: 600;
        }

        select,
        button {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #E5E7EB;
            border-radius: 0.5rem;
            background: var(--alt-surface);
            font-size: 1rem;
            transition: all 0.2s ease;
        }

        select:focus,
        button:focus {
            outline: none;
            box-shadow: 0 0 0 2px var(--secondary);
        }

        button {
            background: var(--secondary);
            color: white;
            border: none;
            cursor: pointer;
            font-weight: 500;
        }

        button:hover {
            background: #1D4ED8;
        }

        /* Verificatiesectie */
        .verification-section {
            padding: 2rem;
            background: var(--alt-surface);
            border-radius: 0.75rem;
            margin-top: 2rem;
        }

        #idinStatus {
            min-height: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .loading {
            color: var(--primary);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
            }
        }

        /* Logout knop */
        .logout-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            text-decoration: none;
        }

        .logout-button svg {
            width: 1.25rem;
            height: 1.25rem;
        }

        /* Foutmeldingen */
        .error {
            color: #DC2626;
            padding: 1rem;
            background: #FEE2E2;
            border-radius: 0.5rem;
            margin: 1rem 0;
        }
    </style>
</head>

<body>
    <?php include $headerPath; ?>

    <div class="container">
        <div class="profile-card">
            <!-- Profielheader -->
            <div class="profile-header">
                <div class="profile-header-content">
                    <div class="flex items-center gap-4">
                        <div class="avatar">
                            <?= strtoupper(substr($userName, 0, 1)) ?>
                        </div>
                        <div>
                            <h1><?= $userName ?></h1>
                            <p><?= $user['email'] ?? '' ?></p>
                        </div>
                    </div>
                    <a href="/logout.php" class="logout-button">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        <span><?= $txt['logout'] ?></span>
                    </a>
                </div>
            </div>

            <!-- Instellingen -->
            <div class="settings-grid">
                <div class="setting-group">
                    <h2><?= $txt['language'] ?></h2>
                    <select id="languageSelect">
                        <option value="" disabled selected><?= $txt['language'] ?></option>
                        <option value="PropPrefTxt_Nl"><?= $txt['language_nl'] ?></option>
                        <option value="PropPrefTxt_En"><?= $txt['language_en'] ?></option>
                    </select>
                </div>

                <div class="setting-group">
                    <h2><?= $txt['organization'] ?></h2>
                    <select id="orgSelect">
                        <option value="" disabled selected><?= $txt['organization'] ?></option>
                    </select>
                    <button onclick="confirmOrg()"><?= $txt['save'] ?></button>
                </div>
            </div>

            <!-- Verificatie -->
            <div class="verification-section">
                <h2><?= $txt['verify_id'] ?></h2>
                <div id="idinStatus">
                    <div class="loading"><?= $txt['idin_unverified'] ?>...</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Zelfde JavaScript als vorige implementatie
        document.addEventListener('DOMContentLoaded', async () => {
            // ... (zelfde JavaScript code als vorige antwoord)
        });
    </script>
</body>

</html>