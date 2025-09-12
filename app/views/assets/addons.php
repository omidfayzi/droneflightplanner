<?php
// =================================================================
// OVERIGE ASSETS BEHEER PAGINA
// (zelfde structuur/styling/UX als Verzekeringen)
// =================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../functions.php';

// =================================================================
// DATA OPHALEN VAN DE API
// =================================================================
$apiBaseUrl = defined('API_BASE_URL') ? API_BASE_URL : "http://devserv01.holdingthedrones.com:4539";
$assetsUrl  = rtrim($apiBaseUrl, '/') . "/overige_assets";

// @ om warnings te onderdrukken indien API niet bereikbaar is
$assetsResponse = @file_get_contents($assetsUrl);

// JSON → array
$assets = $assetsResponse ? json_decode($assetsResponse, true) : [];

// Sommige API’s wrapperen in 'data'
if (isset($assets['data']) && is_array($assets['data'])) {
    $assets = $assets['data'];
}

// JSON validatie
if ($assetsResponse && json_last_error() !== JSON_ERROR_NONE) {
    error_log("JSON Decode Error (overige_assets): " . json_last_error_msg());
    $assets = [];
}

// =================================================================
// KOLOMMEN + FILTEROPTIES
// =================================================================
// Dynamische kolommen (zelfde aanpak als Verzekeringen)
$kolommen = [];
if (!empty($assets) && is_array($assets)) {
    $kolommen = array_keys($assets[0]);
}

// Unieke filterwaarden verzamelen
$uniqueStatuses   = [];
$uniqueTypes      = [];
$uniqueCategories = [];
$uniqueDepartments = [];
$uniqueLocations  = [];

foreach ($assets as $a) {
    $s = $a['status'] ?? '';
    $t = $a['type'] ?? ($a['assetType'] ?? '');
    $c = $a['categorie'] ?? ($a['category'] ?? '');
    $d = $a['afdeling'] ?? ($a['department'] ?? '');
    $l = $a['locatie']  ?? ($a['location'] ?? '');

    if ($s !== '' && !in_array($s, $uniqueStatuses, true))    $uniqueStatuses[] = $s;
    if ($t !== '' && !in_array($t, $uniqueTypes, true))        $uniqueTypes[] = $t;
    if ($c !== '' && !in_array($c, $uniqueCategories, true))   $uniqueCategories[] = $c;
    if ($d !== '' && !in_array($d, $uniqueDepartments, true))  $uniqueDepartments[] = $d;
    if ($l !== '' && !in_array($l, $uniqueLocations, true))    $uniqueLocations[] = $l;
}

sort($uniqueStatuses);
sort($uniqueTypes);
sort($uniqueCategories);
sort($uniqueDepartments);
sort($uniqueLocations);

// =================================================================
// HELPERS
// =================================================================
function nlDate($val)
{
    if (!$val || $val === '0000-00-00' || $val === '0000-00-00 00:00:00') return 'N/B';
    try {
        return (new DateTime($val))->format('d-m-Y');
    } catch (Exception $e) {
        return htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
    }
}
function slug($s)
{
    return strtolower(str_replace(' ', '-', (string)$s));
}

// =================================================================
// HTML OUTPUT
// =================================================================
ob_start();
?>

<!-- Zelfde stylesheet als Verzekeringen -->
<link rel="stylesheet" href="/app/assets/styles/custom_styling.scss">

<div class="main bg-gray-100 shadow-md rounded-tl-xl w-full flex flex-col">

    <!-- Navigatie -->
    <div class="p-6 bg-white flex justify-between items-center border-b border-gray-200 flex-shrink-0">
        <div class="flex space-x-6 text-sm font-medium">
            <a href="drones.php" class="text-gray-600 hover:text-gray-900">Drones</a>
            <a href="employees.php" class="text-gray-600 hover:text-gray-900">Personeel</a>
            <a href="overigeassets.php" class="text-gray-900 border-b-2 border-black pb-2">Add-Ons</a>
            <a href="verzekeringen.php" class="text-gray-600 hover:text-gray-900">Verzekeringen</a>
        </div>
        <button onclick="openAddModal()" class="btn-primary text-sm flex items-center gap-2">
            <i class="fa-solid fa-plus-circle"></i> Nieuw Asset
        </button>
    </div>

    <!-- Filterbalk (asset-specifiek) -->
    <div class="px-6 pt-4">
        <div class="filter-bar">
            <div class="filter-group">
                <span class="filter-label">Status:</span>
                <select id="statusFilter" class="filter-select" onchange="filterTable()">
                    <option value="">Alle statussen</option>
                    <?php foreach ($uniqueStatuses as $s): ?>
                        <option value="<?= htmlspecialchars(strtolower($s), ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($s, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if (!empty($uniqueTypes)): ?>
                <div class="filter-group">
                    <span class="filter-label">Type:</span>
                    <select id="typeFilter" class="filter-select" onchange="filterTable()">
                        <option value="">Alle types</option>
                        <?php foreach ($uniqueTypes as $t): ?>
                            <option value="<?= htmlspecialchars(strtolower($t), ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($t, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <?php if (!empty($uniqueCategories)): ?>
                <div class="filter-group">
                    <span class="filter-label">Categorie:</span>
                    <select id="categorieFilter" class="filter-select" onchange="filterTable()">
                        <option value="">Alle categorieën</option>
                        <?php foreach ($uniqueCategories as $c): ?>
                            <option value="<?= htmlspecialchars(strtolower($c), ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($c, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <?php if (!empty($uniqueDepartments)): ?>
                <div class="filter-group">
                    <span class="filter-label">Afdeling:</span>
                    <select id="afdelingFilter" class="filter-select" onchange="filterTable()">
                        <option value="">Alle afdelingen</option>
                        <?php foreach ($uniqueDepartments as $d): ?>
                            <option value="<?= htmlspecialchars(strtolower($d), ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($d, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <?php if (!empty($uniqueLocations)): ?>
                <div class="filter-group">
                    <span class="filter-label">Locatie:</span>
                    <select id="locatieFilter" class="filter-select" onchange="filterTable()">
                        <option value="">Alle locaties</option>
                        <?php foreach ($uniqueLocations as $l): ?>
                            <option value="<?= htmlspecialchars(strtolower($l), ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($l, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="filter-group flex-grow">
                <input id="searchInput" type="text" placeholder="Zoek asset..." class="filter-search" oninput="filterTable()">
            </div>
        </div>
    </div>

    <!-- Tabel -->
    <div class="p-6 overflow-y-auto flex-grow">
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table id="assetsTable" class="w-full">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                        <tr>
                            <?php foreach ($kolommen as $kolom): ?>
                                <th class="px-4 py-3 text-left"><?= htmlspecialchars($kolom, ENT_QUOTES, 'UTF-8') ?></th>
                            <?php endforeach; ?>
                            <th class="px-4 py-3 text-left">Acties</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 text-sm">
                        <?php if (!empty($assets)): ?>
                            <?php foreach ($assets as $asset): ?>
                                <?php
                                // JSON voor JS
                                $assetJson = json_encode($asset, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

                                // data-attributen voor filters
                                $statusLower   = strtolower($asset['status'] ?? '');
                                $typeLower     = strtolower($asset['type'] ?? ($asset['assetType'] ?? ''));
                                $catLower      = strtolower($asset['categorie'] ?? ($asset['category'] ?? ''));
                                $afdLower      = strtolower($asset['afdeling'] ?? ($asset['department'] ?? ''));
                                $locLower      = strtolower($asset['locatie'] ?? ($asset['location'] ?? ''));
                                ?>
                                <tr class="hover:bg-gray-50 transition asset-row"
                                    data-status="<?= htmlspecialchars($statusLower, ENT_QUOTES, 'UTF-8') ?>"
                                    data-type="<?= htmlspecialchars($typeLower, ENT_QUOTES, 'UTF-8') ?>"
                                    data-categorie="<?= htmlspecialchars($catLower, ENT_QUOTES, 'UTF-8') ?>"
                                    data-afdeling="<?= htmlspecialchars($afdLower, ENT_QUOTES, 'UTF-8') ?>"
                                    data-locatie="<?= htmlspecialchars($locLower, ENT_QUOTES, 'UTF-8') ?>">

                                    <?php foreach ($kolommen as $kolom): ?>
                                        <?php
                                        $waarde = $asset[$kolom] ?? '';

                                        if ($kolom === 'status') {
                                            $waarde = '<span class="status-badge status-' . htmlspecialchars(slug($waarde), ENT_QUOTES, 'UTF-8') . '">' .
                                                htmlspecialchars((string)$waarde !== '' ? (string)$waarde : 'Onbekend', ENT_QUOTES, 'UTF-8') .
                                                '</span>';
                                        } elseif ($kolom === 'aanschafdatum') {
                                            $waarde = nlDate($waarde);
                                        } else {
                                            $waarde = htmlspecialchars((string)$waarde !== '' ? (string)$waarde : 'N/B', ENT_QUOTES, 'UTF-8');
                                        }
                                        ?>
                                        <td class="px-4 py-3 whitespace-nowrap"><?= $waarde ?></td>
                                    <?php endforeach; ?>

                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <button onclick='showDetails(<?= $assetJson ?>)' class="text-blue-600 hover:text-blue-800 mr-2" title="Details">
                                            <i class="fa-regular fa-file-lines"></i>
                                        </button>
                                        <button onclick='showEdit(<?= $assetJson ?>)' class="text-green-600 hover:text-green-800" title="Bewerken">
                                            <i class="fa-solid fa-edit"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?= count($kolommen) + 1 ?>" class="text-center text-gray-500 py-10">
                                    Geen assets gevonden of data kon niet worden geladen.
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
     MODALS (zelfde structuur als Verzekeringen)
     ================================================================= -->

<!-- ADD -->
<div id="addModal" class="modal-overlay">
    <div class="modal-content">
        <button class="modal-close-btn" onclick="closeAddModal()">×</button>
        <h3 class="modal-title"><i class="fa-solid fa-box"></i> Nieuw Asset</h3>

        <form action="save_asset.php" method="POST" class="space-y-4">
            <div class="form-grid">
                <div class="form-group">
                    <label>Naam <span class="required-star">*</span></label>
                    <input type="text" name="naam" required>
                </div>

                <div class="form-group">
                    <label>Type <span class="required-star">*</span></label>
                    <select name="type" required>
                        <option value="">Selecteer…</option>
                        <?php foreach ($uniqueTypes as $t): ?>
                            <option value="<?= htmlspecialchars($t, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($t, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Categorie</label>
                    <select name="categorie">
                        <option value="">Selecteer…</option>
                        <?php foreach ($uniqueCategories as $c): ?>
                            <option value="<?= htmlspecialchars($c, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($c, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Serienummer</label>
                    <input type="text" name="serienummer">
                </div>

                <div class="form-group">
                    <label>Status <span class="required-star">*</span></label>
                    <select name="status" required>
                        <option value="">Selecteer…</option>
                        <?php foreach ($uniqueStatuses as $s): ?>
                            <option value="<?= htmlspecialchars($s, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($s, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Afdeling</label>
                    <select name="afdeling">
                        <option value="">Selecteer…</option>
                        <?php foreach ($uniqueDepartments as $d): ?>
                            <option value="<?= htmlspecialchars($d, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($d, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Locatie</label>
                    <select name="locatie">
                        <option value="">Selecteer…</option>
                        <?php foreach ($uniqueLocations as $l): ?>
                            <option value="<?= htmlspecialchars($l, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($l, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Aanschafdatum</label>
                    <input type="date" name="aanschafdatum">
                </div>
            </div>

            <div class="form-group">
                <label>Notities</label>
                <textarea name="notities" rows="3"></textarea>
            </div>

            <div class="pt-4 flex justify-end gap-3">
                <button type="button" onclick="closeAddModal()" class="btn-secondary">Annuleren</button>
                <button type="submit" class="btn-primary">
                    <i class="fa-solid fa-save mr-2"></i> Asset Opslaan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- DETAILS -->
<div id="detailModal" class="modal-overlay">
    <div class="modal-content">
        <button class="modal-close-btn" onclick="closeDetailModal()">×</button>
        <h3 class="modal-title"><i class="fa-solid fa-box"></i> Asset Details</h3>
        <div id="detailContent" class="detail-grid"></div>
    </div>
</div>

<!-- EDIT -->
<div id="editModal" class="modal-overlay">
    <div class="modal-content">
        <button class="modal-close-btn" onclick="closeEditModal()">×</button>
        <h3 class="modal-title"><i class="fa-solid fa-pen"></i> Asset Bewerken</h3>

        <form action="update_asset.php" method="POST" class="space-y-4">
            <input type="hidden" name="assetId" id="edit_id">
            <div class="form-grid">
                <div class="form-group">
                    <label>Naam <span class="required-star">*</span></label>
                    <input type="text" name="naam" id="edit_naam" required>
                </div>

                <div class="form-group">
                    <label>Type <span class="required-star">*</span></label>
                    <select name="type" id="edit_type" required>
                        <option value="">Selecteer…</option>
                        <?php foreach ($uniqueTypes as $t): ?>
                            <option value="<?= htmlspecialchars($t, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($t, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Categorie</label>
                    <select name="categorie" id="edit_categorie">
                        <option value="">Selecteer…</option>
                        <?php foreach ($uniqueCategories as $c): ?>
                            <option value="<?= htmlspecialchars($c, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($c, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Serienummer</label>
                    <input type="text" name="serienummer" id="edit_serienummer">
                </div>

                <div class="form-group">
                    <label>Status <span class="required-star">*</span></label>
                    <select name="status" id="edit_status" required>
                        <option value="">Selecteer…</option>
                        <?php foreach ($uniqueStatuses as $s): ?>
                            <option value="<?= htmlspecialchars($s, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($s, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Afdeling</label>
                    <select name="afdeling" id="edit_afdeling">
                        <option value="">Selecteer…</option>
                        <?php foreach ($uniqueDepartments as $d): ?>
                            <option value="<?= htmlspecialchars($d, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($d, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Locatie</label>
                    <select name="locatie" id="edit_locatie">
                        <option value="">Selecteer…</option>
                        <?php foreach ($uniqueLocations as $l): ?>
                            <option value="<?= htmlspecialchars($l, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($l, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Aanschafdatum</label>
                    <input type="date" name="aanschafdatum" id="edit_aanschafdatum">
                </div>
            </div>

            <div class="form-group">
                <label>Notities</label>
                <textarea name="notities" id="edit_notities" rows="3"></textarea>
            </div>

            <div class="pt-4 flex justify-end gap-3">
                <button type="button" onclick="closeEditModal()" class="btn-secondary">Annuleren</button>
                <button type="submit" class="btn-primary">
                    <i class="fa-solid fa-save mr-2"></i> Wijzigingen Opslaan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- =================================================================
     JAVASCRIPT (zelfde gedrag als Verzekeringen)
     ================================================================= -->
<script>
    // MODAL helpers
    function openAddModal() {
        document.getElementById('addModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeAddModal() {
        document.getElementById('addModal').classList.remove('active');
        document.body.style.overflow = '';
        document.querySelector('#addModal form')?.reset();
    }

    function closeDetailModal() {
        document.getElementById('detailModal').classList.remove('active');
        document.body.style.overflow = '';
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.remove('active');
        document.body.style.overflow = '';
    }

    // DETAILS
    function showDetails(data) {
        const velden = {
            'Asset ID': ['assetId', 'id'],
            'Naam': 'naam',
            'Type': ['type', 'assetType'],
            'Categorie': ['categorie', 'category'],
            'Serienummer': 'serienummer',
            'Status': 'status',
            'Afdeling': ['afdeling', 'department'],
            'Locatie': ['locatie', 'location'],
            'Aanschafdatum': 'aanschafdatum',
            'Notities': 'notities'
        };

        let html = '';
        const toDate = v => {
            if (!v || v === '0000-00-00' || v === '0000-00-00 00:00:00') return 'N/B';
            try {
                return new Date(v).toLocaleDateString('nl-NL');
            } catch (e) {
                return v;
            }
        };

        for (const [label, keyOrKeys] of Object.entries(velden)) {
            let val = '-';
            if (Array.isArray(keyOrKeys)) {
                for (const k of keyOrKeys) {
                    if (data[k]) {
                        val = data[k];
                        break;
                    }
                }
            } else {
                val = data[keyOrKeys] ?? '-';
            }

            if (label === 'Aanschafdatum' && val !== '-') {
                val = toDate(val);
            }
            if (label === 'Status') {
                const slug = String(val).toLowerCase().replace(/\s+/g, '-');
                val = `<span class="status-badge status-${slug}">${val||'Onbekend'}</span>`;
            } else {
                val = (val !== '' && val !== '-') ? val : 'N/B';
            }

            html += `
                <div class="detail-group">
                    <div class="detail-label">${label}</div>
                    <div class="detail-value">${val}</div>
                </div>`;
        }

        document.getElementById('detailContent').innerHTML = `<div class="detail-grid">${html}</div>`;
        document.getElementById('detailModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    // EDIT
    function showEdit(d) {
        document.getElementById('edit_id').value = d.assetId || d.id || '';
        document.getElementById('edit_naam').value = d.naam || '';
        document.getElementById('edit_type').value = d.type || d.assetType || '';
        document.getElementById('edit_categorie').value = d.categorie || d.category || '';
        document.getElementById('edit_serienummer').value = d.serienummer || '';
        document.getElementById('edit_status').value = d.status || '';
        document.getElementById('edit_afdeling').value = d.afdeling || d.department || '';
        document.getElementById('edit_locatie').value = d.locatie || d.location || '';
        document.getElementById('edit_aanschafdatum').value = d.aanschafdatum ? new Date(d.aanschafdatum).toISOString().split('T')[0] : '';
        document.getElementById('edit_notities').value = d.notities || '';

        document.getElementById('editModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    // FILTER
    function filterTable() {
        const zoekterm = (document.getElementById('searchInput')?.value || '').toLowerCase();
        const status = (document.getElementById('statusFilter')?.value || '').toLowerCase();
        const type = (document.getElementById('typeFilter')?.value || '').toLowerCase();
        const categorie = (document.getElementById('categorieFilter')?.value || '').toLowerCase();
        const afdeling = (document.getElementById('afdelingFilter')?.value || '').toLowerCase();
        const locatie = (document.getElementById('locatieFilter')?.value || '').toLowerCase();

        document.querySelectorAll('.asset-row').forEach(rij => {
            const text = rij.textContent.toLowerCase();
            const rSt = rij.dataset.status || '';
            const rType = rij.dataset.type || '';
            const rCat = rij.dataset.categorie || '';
            const rAfd = rij.dataset.afdeling || '';
            const rLoc = rij.dataset.locatie || '';

            const okQ = text.includes(zoekterm);
            const okSt = !status || rSt === status;
            const okType = !type || rType === type;
            const okCat = !categorie || rCat === categorie;
            const okAfd = !afdeling || rAfd === afdeling;
            const okLoc = !locatie || rLoc === locatie;

            rij.style.display = (okQ && okSt && okType && okCat && okAfd && okLoc) ? '' : 'none';
        });
    }

    // Klik op overlay sluit modals (zelfde UX)
    document.addEventListener('DOMContentLoaded', function() {
        ['addModal', 'detailModal', 'editModal'].forEach(id => {
            const m = document.getElementById(id);
            if (!m) return;
            m.addEventListener('click', e => {
                if (e.target === m) {
                    m.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
        });
    });
</script>

<?php
$bodyContent = ob_get_clean();

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../layouts/template.php';
