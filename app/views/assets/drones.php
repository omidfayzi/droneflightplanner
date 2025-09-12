<?php
// =================================================================
// DRONE INVENTARIS PAGINA
// =================================================================
// Zelfde patroon: dynamische kolommen, filters, zoekbalk, modals.

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../functions.php';

// =================================================================
// API DATA OPHALEN
// =================================================================
$apiBaseUrl = defined('API_BASE_URL') ? API_BASE_URL : "http://devserv01.holdingthedrones.com:4539";
$dronesUrl  = rtrim($apiBaseUrl, '/') . "/drones";

$dronesResponse = @file_get_contents($dronesUrl);
$drones = $dronesResponse ? json_decode($dronesResponse, true) : [];

if (isset($drones['data']) && is_array($drones['data'])) {
    $drones = $drones['data'];
}

if ($dronesResponse && json_last_error() !== JSON_ERROR_NONE) {
    error_log("JSON Decode Error (drones): " . json_last_error_msg());
    $drones = [];
}

// =================================================================
// DYNAMISCHE KOLOMMEN + FILTERS
// =================================================================
$kolommen = [];
if (!empty($drones) && is_array($drones)) {
    $kolommen = array_keys($drones[0]);
}

$uniqueStatuses = [];
$uniqueFabrikanten = [];
foreach ($drones as $dr) {
    $s = $dr['status'] ?? '';
    $f = $dr['fabrikant'] ?? ($dr['manufacturer'] ?? '');
    if ($s !== '' && !in_array($s, $uniqueStatuses, true)) $uniqueStatuses[] = $s;
    if ($f !== '' && !in_array($f, $uniqueFabrikanten, true)) $uniqueFabrikanten[] = $f;
}
sort($uniqueStatuses);
sort($uniqueFabrikanten);

// Helpers
function nlDateDrone($v)
{
    if (!$v || $v === '0000-00-00' || $v === '0000-00-00 00:00:00') return 'N/B';
    try {
        return (new DateTime($v))->format('d-m-Y');
    } catch (Exception $e) {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}
function slug($s)
{
    return strtolower(preg_replace('/\s+/', '-', (string)$s));
}

// =================================================================
// HEAD/TEMPLATE VARS
// =================================================================
$showHeader = 1;
$userName   = $_SESSION['user']['first_name'] ?? 'Onbekend';
$headTitle  = "Drone Inventaris";
$gobackUrl  = 0;
$rightAttributes = 0;

// =================================================================
// HTML OUTPUT
// =================================================================
ob_start();
?>
<link rel="stylesheet" href="/app/assets/styles/custom_styling.scss">

<div class="main bg-gray-100 shadow-md rounded-tl-xl w-full flex flex-col">
    <!-- Navigatie -->
    <div class="p-6 bg-white flex justify-between items-center border-b border-gray-200 flex-shrink-0">
        <div class="flex space-x-6 text-sm font-medium">
            <a href="drones.php" class="text-gray-900 border-b-2 border-black pb-2">Drones</a>
            <a href="employees.php" class="text-gray-600 hover:text-gray-900">Personeel</a>
            <a href="addons.php" class="text-gray-600 hover:text-gray-900">Add-ons</a>
            <a href="verzekeringen.php" class="text-gray-600 hover:text-gray-900">Verzekeringen</a>
        </div>
        <button onclick="openAddModal()" class="btn-primary text-sm flex items-center gap-2">
            <i class="fa-solid fa-plus"></i> Nieuwe Drone
        </button>
    </div>

    <!-- Filter balk -->
    <div class="px-6 pt-4">
        <div class="filter-bar">
            <div class="filter-group">
                <span class="filter-label">Status:</span>
                <select id="statusFilter" class="filter-select" onchange="filterTable()">
                    <option value="">Alle statussen</option>
                    <?php foreach ($uniqueStatuses as $s): ?>
                        <option value="<?= htmlspecialchars(strtolower($s)) ?>"><?= htmlspecialchars($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <span class="filter-label">Fabrikant:</span>
                <select id="fabrikantFilter" class="filter-select" onchange="filterTable()">
                    <option value="">Alle fabrikanten</option>
                    <?php foreach ($uniqueFabrikanten as $f): ?>
                        <option value="<?= htmlspecialchars(strtolower($f)) ?>"><?= htmlspecialchars($f) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group flex-grow">
                <input id="searchInput" type="text" class="filter-search" placeholder="Zoek drones..." oninput="filterTable()">
            </div>
        </div>
    </div>

    <!-- Tabel -->
    <div class="p-6 overflow-y-auto flex-grow">
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table id="dronesTable" class="w-full">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                        <tr>
                            <?php foreach ($kolommen as $kolom): ?>
                                <th class="px-4 py-3 text-left"><?= htmlspecialchars($kolom) ?></th>
                            <?php endforeach; ?>
                            <th class="px-4 py-3 text-left">Acties</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 text-sm">
                        <?php if (!empty($drones)): ?>
                            <?php foreach ($drones as $drone): ?>
                                <?php
                                $drJson = json_encode($drone, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
                                $statusLower = strtolower($drone['status'] ?? '');
                                $fabLower    = strtolower($drone['fabrikant'] ?? ($drone['manufacturer'] ?? ''));
                                ?>
                                <tr class="hover:bg-gray-50 transition drone-row"
                                    data-status="<?= htmlspecialchars($statusLower) ?>"
                                    data-fabrikant="<?= htmlspecialchars($fabLower) ?>">

                                    <?php foreach ($kolommen as $kolom): ?>
                                        <?php
                                        $val = $drone[$kolom] ?? '';
                                        $kLower = strtolower($kolom);
                                        $isDate = (
                                            $kLower === 'laatste_onderhoud' ||
                                            $kLower === 'volgende_onderhoud' ||
                                            $kLower === 'verzekering_geldig_tot' ||
                                            $kLower === 'aankoopdatum' ||
                                            strpos($kLower, 'datum') !== false ||
                                            strpos($kLower, 'date') !== false
                                        );

                                        if ($kolom === 'status') {
                                            $val = '<span class="status-badge status-' . htmlspecialchars(slug($drone['status'] ?? 'onbekend')) . '">' .
                                                htmlspecialchars($drone['status'] ?? 'Onbekend') . '</span>';
                                        } elseif ($isDate) {
                                            $val = nlDateDrone($val);
                                        } else {
                                            $val = ($val !== '' ? htmlspecialchars((string)$val) : 'N/B');
                                        }
                                        ?>
                                        <td class="px-4 py-3 whitespace-nowrap"><?= $val ?></td>
                                    <?php endforeach; ?>

                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <button class="text-blue-600 hover:text-blue-800 mr-2" title="Details" onclick='showDetails(<?= $drJson ?>)'>
                                            <i class="fa-solid fa-info-circle"></i>
                                        </button>
                                        <button class="text-green-600 hover:text-green-800" title="Bewerken" onclick='showEdit(<?= $drJson ?>)'>
                                            <i class="fa-solid fa-edit"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?= count($kolommen) + 1 ?>" class="text-center text-gray-500 py-10">
                                    Geen drones gevonden of data kon niet worden geladen.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- MODALS -->
<!-- ADD -->
<div id="addModal" class="modal-overlay">
    <div class="modal-content">
        <button class="modal-close-btn" onclick="closeAddModal()">×</button>
        <h3 class="modal-title"><i class="fa-solid fa-drone"></i> Nieuwe Drone</h3>

        <form action="save_drone.php" method="POST" class="space-y-4">
            <div class="form-grid">
                <div class="form-group">
                    <label>Model (droneNaam) <span class="required-star">*</span></label>
                    <input type="text" name="droneNaam" required placeholder="bv. DJI Mavic 3">
                </div>
                <div class="form-group">
                    <label>Naam/Omschrijving</label>
                    <input type="text" name="naam" placeholder="bv. Inspectie Drone West">
                </div>
                <div class="form-group">
                    <label>Serienummer <span class="required-star">*</span></label>
                    <input type="text" name="serienummer" required>
                </div>
                <div class="form-group">
                    <label>Fabrikant</label>
                    <input type="text" name="fabrikant" list="fabOptions">
                    <datalist id="fabOptions">
                        <?php foreach ($uniqueFabrikanten as $f): ?>
                            <option value="<?= htmlspecialchars($f) ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div class="form-group">
                    <label>Verzekering</label>
                    <input type="text" name="verzekering">
                </div>
                <div class="form-group">
                    <label>Verzekering geldig tot</label>
                    <input type="date" name="verzekering_geldig_tot">
                </div>
                <div class="form-group">
                    <label>Registratie autoriteit</label>
                    <input type="text" name="registratie_autoriteit">
                </div>
                <div class="form-group">
                    <label>Certificaat</label>
                    <input type="text" name="certificaat">
                </div>
                <div class="form-group">
                    <label>Laatste onderhoud</label>
                    <input type="date" name="laatste_onderhoud">
                </div>
                <div class="form-group">
                    <label>Volgende onderhoud</label>
                    <input type="date" name="volgende_onderhoud">
                </div>
                <div class="form-group">
                    <label>Status <span class="required-star">*</span></label>
                    <select name="status" required>
                        <option value="">Selecteer…</option>
                        <?php foreach ($uniqueStatuses as $s): ?>
                            <option value="<?= htmlspecialchars($s) ?>"><?= htmlspecialchars($s) ?></option>
                        <?php endforeach; ?>
                        <option value="Actief">Actief</option>
                        <option value="In onderhoud">In onderhoud</option>
                        <option value="Inactief">Inactief</option>
                        <option value="Verkocht">Verkocht</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Aankoopdatum</label>
                    <input type="date" name="aankoopdatum">
                </div>

                <!-- Optionele referenties/IDs -->
                <div class="form-group">
                    <label>Model ID</label>
                    <input type="text" name="droneModelId">
                </div>
                <div class="form-group">
                    <label>Categorie ID</label>
                    <input type="text" name="droneCategorieId">
                </div>
                <div class="form-group">
                    <label>EASA Klasse ID</label>
                    <input type="text" name="easaKlasseId">
                </div>
                <div class="form-group">
                    <label>Organisatie ID</label>
                    <input type="text" name="organisatieId">
                </div>
            </div>

            <div class="form-group">
                <label>Notities</label>
                <textarea name="notes" rows="3"></textarea>
            </div>

            <div class="pt-4 flex justify-end gap-3">
                <button type="button" class="btn-secondary" onclick="closeAddModal()">Annuleren</button>
                <button type="submit" class="btn-primary"><i class="fa-solid fa-save mr-2"></i> Drone Opslaan</button>
            </div>
        </form>
    </div>
</div>

<!-- DETAILS -->
<div id="detailModal" class="modal-overlay">
    <div class="modal-content">
        <button class="modal-close-btn" onclick="closeDetailModal()">×</button>
        <h3 class="modal-title"><i class="fa-solid fa-drone"></i> Drone Details</h3>
        <div id="detailContent" class="detail-grid"></div>
    </div>
</div>

<!-- EDIT -->
<div id="editModal" class="modal-overlay">
    <div class="modal-content">
        <button class="modal-close-btn" onclick="closeEditModal()">×</button>
        <h3 class="modal-title"><i class="fa-solid fa-pen"></i> Drone Bewerken</h3>

        <form action="update_drone.php" method="POST" class="space-y-4">
            <input type="hidden" name="droneId" id="edit_id">
            <div class="form-grid">
                <div class="form-group">
                    <label>Model (droneNaam) <span class="required-star">*</span></label>
                    <input type="text" name="droneNaam" id="edit_droneNaam" required>
                </div>
                <div class="form-group">
                    <label>Naam/Omschrijving</label>
                    <input type="text" name="naam" id="edit_naam">
                </div>
                <div class="form-group">
                    <label>Serienummer <span class="required-star">*</span></label>
                    <input type="text" name="serienummer" id="edit_serienummer" required>
                </div>
                <div class="form-group">
                    <label>Fabrikant</label>
                    <input type="text" name="fabrikant" id="edit_fabrikant" list="fabOptions">
                </div>
                <div class="form-group">
                    <label>Verzekering</label>
                    <input type="text" name="verzekering" id="edit_verzekering">
                </div>
                <div class="form-group">
                    <label>Verzekering geldig tot</label>
                    <input type="date" name="verzekering_geldig_tot" id="edit_verzekering_geldig_tot">
                </div>
                <div class="form-group">
                    <label>Registratie autoriteit</label>
                    <input type="text" name="registratie_autoriteit" id="edit_registratie_autoriteit">
                </div>
                <div class="form-group">
                    <label>Certificaat</label>
                    <input type="text" name="certificaat" id="edit_certificaat">
                </div>
                <div class="form-group">
                    <label>Laatste onderhoud</label>
                    <input type="date" name="laatste_onderhoud" id="edit_laatste_onderhoud">
                </div>
                <div class="form-group">
                    <label>Volgende onderhoud</label>
                    <input type="date" name="volgende_onderhoud" id="edit_volgende_onderhoud">
                </div>
                <div class="form-group">
                    <label>Status <span class="required-star">*</span></label>
                    <select name="status" id="edit_status" required>
                        <option value="">Selecteer…</option>
                        <?php foreach ($uniqueStatuses as $s): ?>
                            <option value="<?= htmlspecialchars($s) ?>"><?= htmlspecialchars($s) ?></option>
                        <?php endforeach; ?>
                        <option value="Actief">Actief</option>
                        <option value="In onderhoud">In onderhoud</option>
                        <option value="Inactief">Inactief</option>
                        <option value="Verkocht">Verkocht</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Aankoopdatum</label>
                    <input type="date" name="aankoopdatum" id="edit_aankoopdatum">
                </div>
            </div>

            <div class="form-group">
                <label>Notities</label>
                <textarea name="notes" id="edit_notes" rows="3"></textarea>
            </div>

            <div class="pt-4 flex justify-end gap-3">
                <button type="button" class="btn-secondary" onclick="closeEditModal()">Annuleren</button>
                <button type="submit" class="btn-primary"><i class="fa-solid fa-save mr-2"></i> Wijzigingen Opslaan</button>
            </div>
        </form>
    </div>
</div>

<script>
    // ======= Helpers ==================================================
    function pick(d, keys) {
        for (var i = 0; i < keys.length; i++) {
            if (d[keys[i]] !== undefined && d[keys[i]] !== null && d[keys[i]] !== '') return d[keys[i]];
        }
        return '';
    }

    function toISODate(v) {
        try {
            if (!v) return '';
            var dt = new Date(v);
            return isNaN(dt) ? '' : dt.toISOString().split('T')[0];
        } catch (e) {
            return '';
        }
    }

    function toNLDate(v) {
        try {
            if (!v || v === '0000-00-00' || v === '0000-00-00 00:00:00') return 'N/B';
            var dt = new Date(v);
            return isNaN(dt) ? v : dt.toLocaleDateString('nl-NL');
        } catch (e) {
            return v;
        }
    }

    // ======= Modals ===================================================
    function openAddModal() {
        document.getElementById('addModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeAddModal() {
        document.getElementById('addModal').classList.remove('active');
        document.body.style.overflow = '';
        var f = document.querySelector('#addModal form');
        if (f) f.reset();
    }

    function closeDetailModal() {
        document.getElementById('detailModal').classList.remove('active');
        document.body.style.overflow = '';
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.remove('active');
        document.body.style.overflow = '';
    }

    // ======= Details ==================================================
    function showDetails(d) {
        var fields = {
            'Drone ID': pick(d, ['droneId', 'id']),
            'Model (droneNaam)': pick(d, ['droneNaam', 'model', 'naam_model']),
            'Naam/Omschrijving': pick(d, ['naam', 'omschrijving', 'description']),
            'Serienummer': pick(d, ['serienummer', 'serial', 'serial_number']),
            'Fabrikant': pick(d, ['fabrikant', 'manufacturer']),
            'Verzekering': pick(d, ['verzekering']),
            'Verzekering geldig tot': toNLDate(pick(d, ['verzekering_geldig_tot'])),
            'Registratie autoriteit': pick(d, ['registratie_autoriteit']),
            'Certificaat': pick(d, ['certificaat']),
            'Laatste onderhoud': toNLDate(pick(d, ['laatste_onderhoud'])),
            'Volgende onderhoud': toNLDate(pick(d, ['volgende_onderhoud'])),
            'Status': pick(d, ['status']),
            'Aankoopdatum': toNLDate(pick(d, ['aankoopdatum'])),
            'Categorie ID': pick(d, ['droneCategorieId']),
            'EASA Klasse ID': pick(d, ['easaKlasseId']),
            'Organisatie ID': pick(d, ['organisatieId']),
            'Notities': pick(d, ['notes', 'notities'])
        };
        var html = '';
        Object.keys(fields).forEach(function(label) {
            var val = fields[label] || 'N/B';
            if (label === 'Status') {
                var slug = String(val || 'onbekend').toLowerCase().replace(/\s+/g, '-');
                val = '<span class="status-badge status-' + slug + '">' + (val || 'Onbekend') + '</span>';
            }
            html += '<div class="detail-group"><div class="detail-label">' + label + '</div><div class="detail-value">' + (val || 'N/B') + '</div></div>';
        });
        document.getElementById('detailContent').innerHTML = html;
        document.getElementById('detailModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    // ======= Edit =====================================================
    function showEdit(d) {
        document.getElementById('edit_id').value = pick(d, ['droneId', 'id']);
        document.getElementById('edit_droneNaam').value = pick(d, ['droneNaam', 'model', 'naam_model']);
        document.getElementById('edit_naam').value = pick(d, ['naam', 'omschrijving', 'description']);
        document.getElementById('edit_serienummer').value = pick(d, ['serienummer', 'serial', 'serial_number']);
        document.getElementById('edit_fabrikant').value = pick(d, ['fabrikant', 'manufacturer']);
        document.getElementById('edit_verzekering').value = pick(d, ['verzekering']);
        document.getElementById('edit_verzekering_geldig_tot').value = toISODate(pick(d, ['verzekering_geldig_tot']));
        document.getElementById('edit_registratie_autoriteit').value = pick(d, ['registratie_autoriteit']);
        document.getElementById('edit_certificaat').value = pick(d, ['certificaat']);
        document.getElementById('edit_laatste_onderhoud').value = toISODate(pick(d, ['laatste_onderhoud']));
        document.getElementById('edit_volgende_onderhoud').value = toISODate(pick(d, ['volgende_onderhoud']));
        document.getElementById('edit_status').value = pick(d, ['status']) || '';
        document.getElementById('edit_aankoopdatum').value = toISODate(pick(d, ['aankoopdatum']));
        document.getElementById('edit_notes').value = pick(d, ['notes', 'notities']);

        document.getElementById('editModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    // ======= Filter ===================================================
    function filterTable() {
        var q = (document.getElementById('searchInput').value || '').toLowerCase();
        var st = (document.getElementById('statusFilter').value || '').toLowerCase();
        var fb = (document.getElementById('fabrikantFilter').value || '').toLowerCase();

        var rows = document.querySelectorAll('.drone-row');
        rows.forEach(function(r) {
            var text = r.textContent.toLowerCase();
            var rSt = r.dataset.status || '';
            var rFb = r.dataset.fabrikant || '';

            var okQ = text.indexOf(q) !== -1;
            var okSt = !st || rSt === st;
            var okFb = !fb || rFb === fb;
            r.style.display = (okQ && okSt && okFb) ? '' : 'none';
        });
    }

    // Overlay click => modal sluiten
    document.addEventListener('DOMContentLoaded', function() {
        ['addModal', 'detailModal', 'editModal'].forEach(function(id) {
            var m = document.getElementById(id);
            if (!m) return;
            m.addEventListener('click', function(e) {
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
