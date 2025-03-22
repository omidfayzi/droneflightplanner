<?php
// /var/www/public/frontend/pages/landing-page.php (of login.php)

// Start de sessie als deze nog niet gestart is
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Laad de configuratie
require_once __DIR__ . '/../../config/config.php';

// Laad backend-functies expliciet
require_once __DIR__ . '/../../functions.php';

// Roep de login-functie aan
login();

// Stel variabelen in voor template.php
$showHeader = 1; // Navigatiemenu tonen
$headTitle = fetchPropPrefTxt(46); // Dynamische titel
$userName = $_SESSION['user']['first_name'] ?? 'Onbekend';
$org = isset($organisation) ? $organisation : 'Organisatie A'; // Dynamisch uit config.php, fallback naar Organisatie A
$gobackUrl = 1; // Toon terug-knop
$rightAttributes = 0; // Geen extra knoppen

// Body met de organisatie-selectie
$bodyContent = "
    <div class='fixed inset-0 bg-black bg-opacity-90 flex items-center justify-center z-50'>
        <div class='w-[90%] bg-white rounded-xl p-5 max-w-md'>
            <h1 class='pb-2'>Selecteer organisatie</h1>
            <select id='mySelect' class='rounded-xl w-full mb-2' style='padding: 10px; background-color: #D9D9D9;'>
                <option value='' disabled selected>" . fetchPropPrefTxt(22) . "</option>
                <option value='gebruiker'>Als gebruiker inloggen</option>
                <option value='org1'>Fictieve Organisatie</option>
            </select>
            <input
                type='button'
                value='Bevestigen'
                onclick='confirmOrg()'
                class='text-white bg-blue-500 hover:bg-blue-700 rounded-xl w-full'
                style='padding: 10px; cursor: pointer;'
            >
        </div>
    </div>
";

// Inclusie van template.php voor de volledige lay-out
require_once __DIR__ . '/layouts/template.php'; // Aangepast pad
?>

<script>
    // Variabelen die mogelijk nodig zijn (commentaar als ze niet essentieel zijn)
    // const cors = '<?php echo $cors ?? ''; ?>';
    // const user = '<?php echo $userOrgDatabaseUser ?? ''; ?>';
    // const org = '<?php echo $userOrgDatabaseOrg ?? ''; ?>';
    // const tokenBearerUserOrg = '<?php echo $userOrgDatabaseBearerToken ?? ''; ?>';

    // Overige variabelen en testcode
    let i = 1; // Voorbeeld, kan worden verwijderd als niet gebruikt
    const orgArray = [];

    // Eventuele functies voor token etc...
    async function set() {
        // Voorbeeld: check sessie-tijd, etc.
    }

    // Ophalen van pref-lists, etc. (indien je dit nog echt nodig hebt)
    async function fetchAllPrefLists() {
        try {
            set();
            // ...
        } catch (error) {
            console.error('Error fetching all preference lists:', error);
        }
    }

    // Ophalen van organisatie-data (indien nodig)
    async function fetchOrganisationData() {
        // ...
    }

    /**
     * confirmOrg()
     * Wordt aangeroepen wanneer de gebruiker op 'Bevestigen' klikt.
     * Als de waarde 'gebruiker' is, log in als gewone gebruiker
     * Anders 'org1' => ga naar de dashboard voor org.
     */
    function confirmOrg() {
        const selectBox = document.getElementById("mySelect");
        const selectedValue = selectBox.value;

        if (selectedValue === "gebruiker") {
            // Sla eventueel cookies op
            document.cookie = `usr_type=usr; path=/; max-age=${100 * 365 * 24 * 60 * 60};`;
            // Ga naar dashboard.php in dezelfde map
            window.location.href = "dashboard.php";
        } else if (selectedValue === "org1") {
            // Sla cookies op voor organisatie
            document.cookie = `org_id=org1; path=/; max-age=${100 * 365 * 24 * 60 * 60};`;
            document.cookie = `usr_type=org; path=/; max-age=${100 * 365 * 24 * 60 * 60};`;
            // Eveneens doorsturen naar dashboard.php (organisatie-dashboard)
            window.location.href = "dashboard.php";
        } else {
            alert("Selecteer eerst een optie.");
        }
    }

    // Eventueel direct aanroepen
    // fetchAllPrefLists();
</script>