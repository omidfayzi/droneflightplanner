<?php
// =================================================================
// VERZEKERINGEN BEHEER PAGINA
// =================================================================
// Dit bestand toont alle verzekeringen in een tabel met filter/zoek mogelijkheden.
// Gebruikers kunnen verzekeringen bekijken, toevoegen en bewerken via modals.

// =================================================================

// STAP 1: SESSIE EN CONFIGURATIE LADEN
// ---------------------------------------------------------------------
// Een sessie is nodig om gebruikersgegevens tussen pagina's te bewaren
if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Start de sessie als deze nog niet actief is
}

// Laad de configuratiebestanden (bevat o.a. de API URL)
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../functions.php';

// =================================================================
// STAP 2: DATA OPHALEN VAN DE API
// =================================================================
// We halen verzekeringdata op van een externe API server

// Bepaal de API URL (gebruik config of fallback)
$apiBaseUrl = defined('API_BASE_URL') ? API_BASE_URL : "http://devserv01.holdingthedrones.com:4539";

// Maak de complete URL voor het verzekeringen endpoint
$verzekeringenUrl = rtrim($apiBaseUrl, '/') . "/verzekeringen";

// Haal de data op van de API
// De @ voorkomt dat PHP waarschuwingen toont als de server offline is
$verzekeringenResponse = @file_get_contents($verzekeringenUrl);

// =================================================================
// STAP 3: JSON DATA VERWERKEN
// =================================================================
// De API geeft JSON terug, dit moeten we omzetten naar PHP array

// Converteer JSON naar PHP array (true = associatieve array)
$verzekeringen = $verzekeringenResponse ? json_decode($verzekeringenResponse, true) : [];

// Sommige APIs stoppen de data in een 'data' wrapper, check dit
if (isset($verzekeringen['data']) && is_array($verzekeringen['data'])) {
    $verzekeringen = $verzekeringen['data']; // Pak de werkelijke data
}

// Controleer of de JSON geldig was
if ($verzekeringenResponse && json_last_error() !== JSON_ERROR_NONE) {
    // Log fout voor developers, maar laat pagina niet crashen
    error_log("JSON Decode Error (verzekeringen): " . json_last_error_msg());
    $verzekeringen = []; // Maak lege array om fouten te voorkomen
}

// =================================================================
// STAP 4: KOLOMMEN EN FILTEROPTIES VOORBEREIDEN
// =================================================================

// Verzamel alle unieke kolomnamen uit de data
// Dit maakt de tabel flexibel voor verschillende data structuren
$kolommen = [];
if (!empty($verzekeringen)) {
    // Pak de eerste record en gebruik die kolommen
    $kolommen = array_keys($verzekeringen[0]);
}

// Verzamel unieke waarden voor de filter dropdown
$uniqueMaatschappijen = [];
foreach ($verzekeringen as $verzekering) {
    $maatschappij = $verzekering['maatschappij'] ?? '';
    if ($maatschappij && !in_array($maatschappij, $uniqueMaatschappijen)) {
        $uniqueMaatschappijen[] = $maatschappij;
    }
}
sort($uniqueMaatschappijen); // Sorteer alfabetisch

// =================================================================
// STAP 5: HTML OUTPUT STARTEN
// =================================================================
// We gebruiken output buffering om de HTML in een variabele te krijgen
ob_start();
?>

<!-- Laad het specifieke CSS bestand voor deze pagina -->
<link rel="stylesheet" href="/app/assets/styles/custom_styling.scss">

<div class="main bg-gray-100 shadow-md rounded-tl-xl w-full flex flex-col">

    <!-- =================================================================
         NAVIGATIEBALK
         Toont tabs voor verschillende asset-pagina's
         ================================================================= -->
    <div class="p-6 bg-white flex justify-between items-center border-b border-gray-200 flex-shrink-0">
        <div class="flex space-x-6 text-sm font-medium">
            <a href="drones.php" class="text-gray-600 hover:text-gray-900">Drones</a>
            <a href="employees.php" class="text-gray-600 hover:text-gray-900">Personeel</a>
            <a href="addons.php" class="text-gray-600 hover:text-gray-900">Add-ons</a>
            <a href="verzekeringen.php" class="text-gray-900 border-b-2 border-black pb-2">Verzekeringen</a>
        </div>
        <!-- Knop om nieuwe verzekering toe te voegen -->
        <button onclick="openAddModal()" class="btn-primary text-sm flex items-center gap-2">
            <i class="fa-solid fa-plus-circle"></i> Nieuwe Verzekering
        </button>
    </div>

    <!-- =================================================================
         FILTERBALK
         Bevat dropdown filter en zoekveld
         ================================================================= -->
    <div class="px-6 pt-4">
        <div class="filter-bar">
            <!-- Dropdown filter voor maatschappij -->
            <div class="filter-group">
                <span class="filter-label">Maatschappij:</span>
                <select id="maatschappijFilter" class="filter-select" onchange="filterTable()">
                    <option value="">Alle maatschappijen</option>
                    <?php foreach ($uniqueMaatschappijen as $maatschappij): ?>
                        <!-- htmlspecialchars voorkomt XSS aanvallen door speciale karakters te escapen -->
                        <option value="<?= htmlspecialchars($maatschappij, ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($maatschappij, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Zoekveld voor vrije tekst zoeken -->
            <div class="filter-group flex-grow">
                <input id="searchInput"
                    type="text"
                    placeholder="Zoek verzekering..."
                    class="filter-search"
                    oninput="filterTable()">
            </div>
        </div>
    </div>

    <!-- =================================================================
         DATA TABEL
         Toont alle verzekeringen in een overzichtelijke tabel
         ================================================================= -->
    <div class="p-6 overflow-y-auto flex-grow">
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table id="verzekeringenTable" class="w-full">
                    <!-- Tabel header met kolomnamen -->
                    <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                        <tr>
                            <?php foreach ($kolommen as $kolom): ?>
                                <th class="px-4 py-3 text-left">
                                    <?= htmlspecialchars($kolom, ENT_QUOTES, 'UTF-8') ?>
                                </th>
                            <?php endforeach; ?>
                            <th class="px-4 py-3 text-left">Acties</th>
                        </tr>
                    </thead>

                    <!-- Tabel body met de data -->
                    <tbody class="divide-y divide-gray-200 text-sm">
                        <?php if (!empty($verzekeringen)): ?>
                            <?php foreach ($verzekeringen as $verzekering): ?>
                                <?php
                                // Bereid JSON voor JavaScript (veilig escapen)
                                $verzekeringJson = json_encode(
                                    $verzekering,
                                    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
                                );

                                // Data attributen voor filter (kleine letters voor consistentie)
                                $maatschappijLower = strtolower($verzekering['maatschappij'] ?? '');
                                ?>
                                <tr class="hover:bg-gray-50 transition verzekering-row"
                                    data-maatschappij="<?= htmlspecialchars($maatschappijLower, ENT_QUOTES, 'UTF-8') ?>">

                                    <?php foreach ($kolommen as $kolom): ?>
                                        <?php
                                        // Haal waarde op, gebruik lege string als default
                                        $waarde = $verzekering[$kolom] ?? '';

                                        // Speciale formatting voor bepaalde kolommen
                                        if ($kolom === 'premie' && is_numeric($waarde)) {
                                            // Toon premie als euro bedrag
                                            $waarde = '€' . number_format($waarde, 2, ',', '.');
                                        } elseif ($kolom === 'status') {
                                            // Maak een badge voor de status
                                            $statusClass = 'status-' . strtolower(str_replace(' ', '-', $waarde));
                                            $waarde = '<span class="status-badge ' . $statusClass . '">' .
                                                htmlspecialchars($waarde, ENT_QUOTES, 'UTF-8') . '</span>';
                                        } elseif (in_array($kolom, ['startdatum', 'einddatum']) && $waarde) {
                                            // Format datums naar Nederlandse notatie
                                            try {
                                                $datum = new DateTime($waarde);
                                                $waarde = $datum->format('d-m-Y');
                                            } catch (Exception $e) {
                                                // Bij fout, toon originele waarde
                                                $waarde = htmlspecialchars($waarde, ENT_QUOTES, 'UTF-8');
                                            }
                                        } else {
                                            // Standaard: escape voor veiligheid
                                            $waarde = htmlspecialchars($waarde, ENT_QUOTES, 'UTF-8');
                                        }
                                        ?>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <?= $waarde ?>
                                        </td>
                                    <?php endforeach; ?>

                                    <!-- Actieknoppen per rij -->
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <button onclick='showDetails(<?= $verzekeringJson ?>)'
                                            class="text-blue-600 hover:text-blue-800 mr-2"
                                            title="Details bekijken">
                                            <i class="fa-regular fa-file-lines"></i>
                                        </button>
                                        <button onclick='showEdit(<?= $verzekeringJson ?>)'
                                            class="text-green-600 hover:text-green-800"
                                            title="Bewerken">
                                            <i class="fa-solid fa-edit"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <!-- Toon melding als er geen data is -->
                            <tr>
                                <td colspan="<?= count($kolommen) + 1 ?>" class="text-center text-gray-500 py-10">
                                    Geen verzekeringen gevonden of data kon niet worden geladen.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- =================================================================
     MODAL: NIEUWE VERZEKERING TOEVOEGEN
     Popup formulier voor het aanmaken van een nieuwe verzekering
     ================================================================= -->
<div id="addModal" class="modal-overlay">
    <div class="modal-content">
        <button class="modal-close-btn" onclick="closeAddModal()">×</button>
        <h3 class="modal-title">
            <i class="fa-solid fa-file-contract"></i> Nieuwe Verzekering
        </h3>

        <form action="save_verzekering.php" method="POST" class="space-y-4">
            <div class="form-grid">
                <!-- Basis velden -->
                <div class="form-group">
                    <label>Naam <span class="required-star">*</span></label>
                    <input type="text" name="naam" required>
                </div>

                <div class="form-group">
                    <label>Type <span class="required-star">*</span></label>
                    <select name="type" required>
                        <option value="">Selecteer...</option>
                        <option value="WA">WA</option>
                        <option value="Allrisk">Allrisk</option>
                        <option value="Casco">Casco</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Maatschappij <span class="required-star">*</span></label>
                    <select name="maatschappij" required>
                        <option value="">Selecteer...</option>
                        <option value="Unive">Unive</option>
                        <option value="Aon">Aon</option>
                        <option value="Allianz">Allianz</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Polisnummer <span class="required-star">*</span></label>
                    <input type="text" name="polisnummer" required>
                </div>

                <div class="form-group">
                    <label>Premie (€) <span class="required-star">*</span></label>
                    <input type="number" name="premie" step="0.01" min="0" required>
                </div>

                <div class="form-group">
                    <label>Startdatum <span class="required-star">*</span></label>
                    <input type="date" name="startdatum" required>
                </div>

                <div class="form-group">
                    <label>Einddatum <span class="required-star">*</span></label>
                    <input type="date" name="einddatum" required>
                </div>

                <div class="form-group">
                    <label>Status <span class="required-star">*</span></label>
                    <select name="status" required>
                        <option value="Actief">Actief</option>
                        <option value="Verlopen">Verlopen</option>
                        <option value="In behandeling">In behandeling</option>
                    </select>
                </div>
            </div>

            <!-- Actieknoppen -->
            <div class="pt-4 flex justify-end gap-3">
                <button type="button" onclick="closeAddModal()" class="btn-secondary">
                    Annuleren
                </button>
                <button type="submit" class="btn-primary">
                    <i class="fa-solid fa-save mr-2"></i> Opslaan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- =================================================================
     MODAL: DETAILS BEKIJKEN
     Toont alle informatie van een verzekering (alleen-lezen)
     ================================================================= -->
<div id="detailModal" class="modal-overlay">
    <div class="modal-content">
        <button class="modal-close-btn" onclick="closeDetailModal()">×</button>
        <h3 class="modal-title">
            <i class="fa-solid fa-file-contract"></i> Verzekering Details
        </h3>
        <div id="detailContent" class="detail-grid">
            <!-- Wordt gevuld door JavaScript -->
        </div>
    </div>
</div>

<!-- =================================================================
     MODAL: VERZEKERING BEWERKEN
     Formulier om bestaande verzekering aan te passen
     ================================================================= -->
<div id="editModal" class="modal-overlay">
    <div class="modal-content">
        <button class="modal-close-btn" onclick="closeEditModal()">×</button>
        <h3 class="modal-title">
            <i class="fa-solid fa-pen"></i> Verzekering Bewerken
        </h3>

        <form action="update_verzekering.php" method="POST" class="space-y-4">
            <!-- Verborgen ID veld (nodig voor update) -->
            <input type="hidden" name="verzekeringId" id="edit_id">

            <div class="form-grid">
                <!-- Formulier velden (zelfde als toevoegen) -->
                <div class="form-group">
                    <label>Naam <span class="required-star">*</span></label>
                    <input type="text" name="naam" id="edit_naam" required>
                </div>

                <div class="form-group">
                    <label>Type <span class="required-star">*</span></label>
                    <select name="type" id="edit_type" required>
                        <option value="WA">WA</option>
                        <option value="Allrisk">Allrisk</option>
                        <option value="Casco">Casco</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Maatschappij <span class="required-star">*</span></label>
                    <select name="maatschappij" id="edit_maatschappij" required>
                        <option value="Unive">Unive</option>
                        <option value="Aon">Aon</option>
                        <option value="Allianz">Allianz</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Polisnummer <span class="required-star">*</span></label>
                    <input type="text" name="polisnummer" id="edit_polisnummer" required>
                </div>

                <div class="form-group">
                    <label>Premie (€) <span class="required-star">*</span></label>
                    <input type="number" name="premie" id="edit_premie" step="0.01" min="0" required>
                </div>

                <div class="form-group">
                    <label>Startdatum <span class="required-star">*</span></label>
                    <input type="date" name="startdatum" id="edit_startdatum" required>
                </div>

                <div class="form-group">
                    <label>Einddatum <span class="required-star">*</span></label>
                    <input type="date" name="einddatum" id="edit_einddatum" required>
                </div>

                <div class="form-group">
                    <label>Status <span class="required-star">*</span></label>
                    <select name="status" id="edit_status" required>
                        <option value="Actief">Actief</option>
                        <option value="Verlopen">Verlopen</option>
                        <option value="In behandeling">In behandeling</option>
                    </select>
                </div>
            </div>

            <!-- Actieknoppen -->
            <div class="pt-4 flex justify-end gap-3">
                <button type="button" onclick="closeEditModal()" class="btn-secondary">
                    Annuleren
                </button>
                <button type="submit" class="btn-primary">
                    <i class="fa-solid fa-save mr-2"></i> Wijzigingen Opslaan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- =================================================================
     JAVASCRIPT SECTIE
     Alle interactiviteit van de pagina
     ================================================================= -->
<script>
    // =================================================================
    // MODAL FUNCTIES
    // Functies om de popup vensters te openen en sluiten
    // =================================================================

    // Functie: Open de 'Nieuwe Verzekering' modal
    function openAddModal() {
        // Toon de modal door 'active' class toe te voegen
        document.getElementById('addModal').classList.add('active');
        // Voorkom scrollen van de achtergrond
        document.body.style.overflow = 'hidden';
    }

    // Functie: Sluit de 'Nieuwe Verzekering' modal
    function closeAddModal() {
        // Verberg de modal door 'active' class te verwijderen
        document.getElementById('addModal').classList.remove('active');
        // Herstel scrollen
        document.body.style.overflow = '';
        // Reset het formulier voor volgende keer
        document.querySelector('#addModal form').reset();
    }

    // Functie: Sluit de 'Details' modal
    function closeDetailModal() {
        document.getElementById('detailModal').classList.remove('active');
        document.body.style.overflow = '';
    }

    // Functie: Sluit de 'Bewerken' modal
    function closeEditModal() {
        document.getElementById('editModal').classList.remove('active');
        document.body.style.overflow = '';
    }

    // =================================================================
    // DATA WEERGAVE FUNCTIES
    // Functies om verzekering data te tonen
    // =================================================================

    // Functie: Toon details van een verzekering
    function showDetails(data) {
        // Velden die we willen tonen met hun labels
        const velden = {
            'Verzekering ID': 'verzekeringId',
            'Naam': 'naam',
            'Type': 'type',
            'Maatschappij': 'maatschappij',
            'Polisnummer': 'polisnummer',
            'Premie': 'premie',
            'Startdatum': 'startdatum',
            'Einddatum': 'einddatum',
            'Status': 'status'
        };

        // Bouw HTML op voor de detail weergave
        let html = '';
        for (const [label, key] of Object.entries(velden)) {
            let waarde = data[key] || '-';

            // Format premie als euro bedrag
            if (key === 'premie' && waarde !== '-') {
                waarde = '€' + parseFloat(waarde).toFixed(2).replace('.', ',');
            }

            // Format datums naar Nederlandse notatie
            if ((key === 'startdatum' || key === 'einddatum') && waarde !== '-') {
                const datum = new Date(waarde);
                waarde = datum.toLocaleDateString('nl-NL');
            }

            // Voeg status badge toe
            if (key === 'status') {
                const statusClass = 'status-' + waarde.toLowerCase().replace(' ', '-');
                waarde = `<span class="status-badge ${statusClass}">${waarde}</span>`;
            }

            // Voeg veld toe aan HTML
            html += `
            <div class="detail-group">
                <div class="detail-label">${label}</div>
                <div class="detail-value">${waarde}</div>
            </div>
        `;
        }

        // Plaats HTML in de modal
        document.getElementById('detailContent').innerHTML = html;

        // Open de modal
        document.getElementById('detailModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    // Functie: Open edit modal met bestaande data
    function showEdit(data) {
        // Vul alle formuliervelden met bestaande waarden
        document.getElementById('edit_id').value = data.verzekeringId || '';
        document.getElementById('edit_naam').value = data.naam || '';
        document.getElementById('edit_type').value = data.type || '';
        document.getElementById('edit_maatschappij').value = data.maatschappij || '';
        document.getElementById('edit_polisnummer').value = data.polisnummer || '';
        document.getElementById('edit_premie').value = data.premie || '';
        document.getElementById('edit_startdatum').value = data.startdatum || '';
        document.getElementById('edit_einddatum').value = data.einddatum || '';
        document.getElementById('edit_status').value = data.status || 'Actief';

        // Open de modal
        document.getElementById('editModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    // =================================================================
    // FILTER FUNCTIE
    // Filter de tabel op basis van zoekterm en geselecteerde maatschappij
    // =================================================================
    function filterTable() {
        // Haal filterwaarden op
        const zoekterm = document.getElementById('searchInput').value.toLowerCase();
        const maatschappij = document.getElementById('maatschappijFilter').value.toLowerCase();

        // Haal alle tabelrijen op
        const rijen = document.querySelectorAll('.verzekering-row');

        // Loop door elke rij
        rijen.forEach(rij => {
            // Haal de tekst van de hele rij
            const rijTekst = rij.textContent.toLowerCase();
            // Haal de maatschappij data-attribuut
            const rijMaatschappij = rij.dataset.maatschappij || '';

            // Check of rij voldoet aan beide filters
            const matchZoekterm = rijTekst.includes(zoekterm);
            const matchMaatschappij = !maatschappij || rijMaatschappij === maatschappij;

            // Toon of verberg de rij
            if (matchZoekterm && matchMaatschappij) {
                rij.style.display = ''; // Toon rij
            } else {
                rij.style.display = 'none'; // Verberg rij
            }
        });
    }

    // =================================================================
    // EVENT LISTENERS
    // Klik buiten modal om te sluiten
    // =================================================================
    document.addEventListener('DOMContentLoaded', function() {
        // Voor elke modal: klik op achtergrond sluit modal
        const modals = ['addModal', 'detailModal', 'editModal'];

        modals.forEach(modalId => {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.addEventListener('click', function(e) {
                    // Alleen sluiten als op de achtergrond (overlay) geklikt wordt
                    if (e.target === modal) {
                        modal.classList.remove('active');
                        document.body.style.overflow = '';
                    }
                });
            }
        });
    });
</script>

<?php
// Stop output buffering en sla HTML op
$bodyContent = ob_get_clean();

// Laad de template bestanden
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../layouts/template.php';
?>