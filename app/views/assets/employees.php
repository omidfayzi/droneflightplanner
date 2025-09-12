<?php
// =================================================================
// PERSONEEL BEHEER PAGINA (robuuste fetch + dynamische kolommen)
// =================================================================

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../functions.php';

// ------------------------- Helpers -------------------------------
function is_assoc_array(array $arr): bool
{
    return array() !== $arr && array_keys($arr) !== range(0, count($arr) - 1);
}

/**
 * Haal lijst (array van employees) uit diverse API-responses.
 * Ondersteunt: data/employees/medewerkers/personeel/items/results/records/rows/list
 * en object-van-ID's → indexed array.
 */
function normalize_api_list($raw)
{
    if (!is_array($raw)) return [];

    // Bekende wrappers
    foreach (['data', 'employees', 'medewerkers', 'personeel', 'items', 'results', 'records', 'rows', 'list'] as $k) {
        if (isset($raw[$k]) && is_array($raw[$k])) {
            return $raw[$k];
        }
    }

    // Als het al een lijst is
    if (!is_assoc_array($raw)) return $raw;

    // Object-van-ID's: waarden zijn de records
    $vals = array_values($raw);
    if (!empty($vals) && is_array($vals[0])) return $vals;

    return [];
}

/** Veilig JSON decode + logging */
function safe_decode($json, $endpointLabel = '')
{
    if ($json === false || $json === null) return [];
    $arr = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON Decode Error ($endpointLabel): " . json_last_error_msg());
        return [];
    }
    return $arr;
}

/** Union van keys over alle records → stabiele kolommen */
function collect_all_keys(array $rows): array
{
    $keys = [];
    foreach ($rows as $r) {
        if (is_array($r)) {
            foreach ($r as $k => $_) $keys[$k] = true;
        }
    }
    return array_keys($keys);
}

// ------------------------- Fetch -------------------------------
$apiBaseUrl = defined('API_BASE_URL') ? API_BASE_URL : "http://devserv01.holdingthedrones.com:4539";
$base = rtrim($apiBaseUrl, '/');

$possibleEndpoints = [
    "$base/employees",
    "$base/personeel",
    "$base/medewerkers",
    "$base/staff",
    "$base/users",
];

$ctx = stream_context_create([
    'http' => [
        'timeout' => 6, // iets robuuster
        'ignore_errors' => true,
        'header' => "Accept: application/json\r\n",
    ]
]);

$employees = [];
$lastResponseRaw = null;

foreach ($possibleEndpoints as $ep) {
    $resp = @file_get_contents($ep, false, $ctx);
    $lastResponseRaw = $resp ?: $lastResponseRaw;
    $decoded = safe_decode($resp, $ep);
    $list = normalize_api_list($decoded);

    if (!empty($list) && is_array($list)) {
        $employees = $list;
        break; // we hebben data
    }
}

// Als nog steeds leeg: laatste poging — misschien kwam er een array zonder wrapper binnen
if (empty($employees)) {
    $decoded = safe_decode($lastResponseRaw, 'fallback');
    if (is_array($decoded)) {
        $maybeList = normalize_api_list($decoded);
        if (!empty($maybeList)) $employees = $maybeList;
    }
}

// ------------------------- Kolommen + filters -------------------
$kolommen = collect_all_keys($employees);

// Filters: status / afdeling / rol
$uniqueStatuses   = [];
$uniqueAfdelingen = [];
$uniqueRollen     = [];

foreach ($employees as $e) {
    if (!is_array($e)) continue;
    $status   = $e['status'] ?? '';
    $afdeling = $e['afdeling'] ?? ($e['department'] ?? '');
    $rol      = $e['rol'] ?? ($e['functie'] ?? ($e['role'] ?? ($e['position'] ?? '')));

    if ($status   !== '' && !in_array($status, $uniqueStatuses, true))     $uniqueStatuses[]   = $status;
    if ($afdeling !== '' && !in_array($afdeling, $uniqueAfdelingen, true)) $uniqueAfdelingen[] = $afdeling;
    if ($rol      !== '' && !in_array($rol, $uniqueRollen, true))          $uniqueRollen[]     = $rol;
}

sort($uniqueStatuses);
sort($uniqueAfdelingen);
sort($uniqueRollen);

// ------------------------- PHP helpers --------------------------
function nlDateEmp($val)
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
    return strtolower(preg_replace('/\s+/', '-', (string)$s));
}

// ------------------------- Template vars ------------------------
$showHeader = 1;
$userName   = $_SESSION['user']['first_name'] ?? 'Onbekend';
$headTitle  = "Personeel";
$gobackUrl  = 0;
$rightAttributes = 0;

// ------------------------- HTML --------------------------------
ob_start();
?>
<link rel="stylesheet" href="/app/assets/styles/custom_styling.scss">

<div class="main bg-gray-100 shadow-md rounded-tl-xl w-full flex flex-col">
    <!-- Navigatie -->
    <div class="p-6 bg-white flex justify-between items-center border-b border-gray-200 flex-shrink-0">
        <div class="flex space-x-6 text-sm font-medium">
            <a href="drones.php" class="text-gray-600 hover:text-gray-900">Drones</a>
            <a href="employees.php" class="text-gray-900 border-b-2 border-black pb-2">Personeel</a>
            <a href="addons.php" class="text-gray-600 hover:text-gray-900">Add-ons</a>
            <a href="verzekeringen.php" class="text-gray-600 hover:text-gray-900">Verzekeringen</a>
        </div>
        <button onclick="openAddModal()" class="btn-primary text-sm flex items-center gap-2">
            <i class="fa-solid fa-plus-circle"></i> Nieuwe Medewerker
        </button>
    </div>

    <!-- Filterbalk -->
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
                <span class="filter-label">Afdeling:</span>
                <select id="afdelingFilter" class="filter-select" onchange="filterTable()">
                    <option value="">Alle afdelingen</option>
                    <?php foreach ($uniqueAfdelingen as $a): ?>
                        <option value="<?= htmlspecialchars(strtolower($a)) ?>"><?= htmlspecialchars($a) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <span class="filter-label">Rol/Functie:</span>
                <select id="rolFilter" class="filter-select" onchange="filterTable()">
                    <option value="">Alle rollen</option>
                    <?php foreach ($uniqueRollen as $r): ?>
                        <option value="<?= htmlspecialchars(strtolower($r)) ?>"><?= htmlspecialchars($r) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group flex-grow">
                <input id="searchInput" type="text" class="filter-search" placeholder="Zoek medewerker..." oninput="filterTable()">
            </div>
        </div>
    </div>

    <!-- Tabel -->
    <div class="p-6 overflow-y-auto flex-grow">
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table id="employeesTable" class="w-full">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                        <tr>
                            <?php foreach ($kolommen as $kolom): ?>
                                <th class="px-4 py-3 text-left"><?= htmlspecialchars($kolom) ?></th>
                            <?php endforeach; ?>
                            <th class="px-4 py-3 text-left">Acties</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 text-sm">
                        <?php if (!empty($employees)): ?>
                            <?php foreach ($employees as $emp): if (!is_array($emp)) continue; ?>
                                <?php
                                $empJson = json_encode($emp, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
                                $statusLower   = strtolower($emp['status'] ?? '');
                                $afdelingLower = strtolower($emp['afdeling'] ?? ($emp['department'] ?? ''));
                                $rolLower      = strtolower($emp['rol'] ?? ($emp['functie'] ?? ($emp['role'] ?? ($emp['position'] ?? ''))));
                                ?>
                                <tr class="hover:bg-gray-50 transition employee-row"
                                    data-status="<?= htmlspecialchars($statusLower) ?>"
                                    data-afdeling="<?= htmlspecialchars($afdelingLower) ?>"
                                    data-rol="<?= htmlspecialchars($rolLower) ?>">

                                    <?php foreach ($kolommen as $kolom): ?>
                                        <?php
                                        $waarde = $emp[$kolom] ?? '';
                                        $isDate = in_array(strtolower($kolom), [
                                            'geboortedatum',
                                            'indienst_datum',
                                            'startdatum',
                                            'einddatum',
                                            'contract_einddatum',
                                            'laatste_werkdag',
                                            'hire_date',
                                            'birthdate',
                                            'dob'
                                        ], true);

                                        if ($kolom === 'status') {
                                            $waarde = '<span class="status-badge status-' . htmlspecialchars(slug($emp['status'] ?? 'onbekend')) . '">' .
                                                htmlspecialchars($emp['status'] ?? 'Onbekend') . '</span>';
                                        } elseif ($isDate) {
                                            $waarde = nlDateEmp($waarde);
                                        } else {
                                            $waarde = ($waarde !== '' ? htmlspecialchars((string)$waarde) : 'N/B');
                                        }
                                        ?>
                                        <td class="px-4 py-3 whitespace-nowrap"><?= $waarde ?></td>
                                    <?php endforeach; ?>

                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <button class="text-blue-600 hover:text-blue-800 mr-2" title="Details" onclick='showDetails(<?= $empJson ?>)'>
                                            <i class="fa-regular fa-file-lines"></i>
                                        </button>
                                        <button class="text-green-600 hover:text-green-800" title="Bewerken" onclick='showEdit(<?= $empJson ?>)'>
                                            <i class="fa-solid fa-edit"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?= count($kolommen) + 1 ?>" class="text-center text-gray-500 py-10">
                                    Geen medewerkers gevonden of data kon niet worden geladen.
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
        <h3 class="modal-title"><i class="fa-solid fa-user-plus"></i> Nieuwe Medewerker</h3>

        <form action="save_employee.php" method="POST" class="space-y-4">
            <div class="form-grid">
                <div class="form-group">
                    <label>Voornaam <span class="required-star">*</span></label>
                    <input type="text" name="voornaam" required>
                </div>
                <div class="form-group">
                    <label>Achternaam <span class="required-star">*</span></label>
                    <input type="text" name="achternaam" required>
                </div>
                <div class="form-group">
                    <label>E-mail</label>
                    <input type="email" name="email">
                </div>
                <div class="form-group">
                    <label>Telefoon</label>
                    <input type="text" name="telefoon">
                </div>
                <div class="form-group">
                    <label>Afdeling</label>
                    <input type="text" name="afdeling" list="afdelingOptions">
                    <datalist id="afdelingOptions">
                        <?php foreach ($uniqueAfdelingen as $a): ?>
                            <option value="<?= htmlspecialchars($a) ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div class="form-group">
                    <label>Rol/Functie</label>
                    <input type="text" name="rol" list="rolOptions">
                    <datalist id="rolOptions">
                        <?php foreach ($uniqueRollen as $r): ?>
                            <option value="<?= htmlspecialchars($r) ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div class="form-group">
                    <label>Status <span class="required-star">*</span></label>
                    <select name="status" required>
                        <option value="">Selecteer…</option>
                        <?php foreach ($uniqueStatuses as $s): ?>
                            <option value="<?= htmlspecialchars($s) ?>"><?= htmlspecialchars($s) ?></option>
                        <?php endforeach; ?>
                        <option value="Actief">Actief</option>
                        <option value="Inactief">Inactief</option>
                        <option value="Ziek">Ziek</option>
                        <option value="Verlof">Verlof</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Geboortedatum</label>
                    <input type="date" name="geboortedatum">
                </div>
                <div class="form-group">
                    <label>In dienst sinds</label>
                    <input type="date" name="indienst_datum">
                </div>
            </div>

            <div class="pt-4 flex justify-end gap-3">
                <button type="button" class="btn-secondary" onclick="closeAddModal()">Annuleren</button>
                <button type="submit" class="btn-primary"><i class="fa-solid fa-save mr-2"></i> Opslaan</button>
            </div>
        </form>
    </div>
</div>

<!-- DETAILS -->
<div id="detailModal" class="modal-overlay">
    <div class="modal-content">
        <button class="modal-close-btn" onclick="closeDetailModal()">×</button>
        <h3 class="modal-title"><i class="fa-solid fa-id-badge"></i> Medewerker Details</h3>
        <div id="detailContent" class="detail-grid"></div>
    </div>
</div>

<!-- EDIT -->
<div id="editModal" class="modal-overlay">
    <div class="modal-content">
        <button class="modal-close-btn" onclick="closeEditModal()">×</button>
        <h3 class="modal-title"><i class="fa-solid fa-pen"></i> Medewerker Bewerken</h3>

        <form action="update_employee.php" method="POST" class="space-y-4">
            <input type="hidden" name="employeeId" id="edit_id">
            <div class="form-grid">
                <div class="form-group">
                    <label>Voornaam <span class="required-star">*</span></label>
                    <input type="text" name="voornaam" id="edit_voornaam" required>
                </div>
                <div class="form-group">
                    <label>Achternaam <span class="required-star">*</span></label>
                    <input type="text" name="achternaam" id="edit_achternaam" required>
                </div>
                <div class="form-group">
                    <label>E-mail</label>
                    <input type="email" name="email" id="edit_email">
                </div>
                <div class="form-group">
                    <label>Telefoon</label>
                    <input type="text" name="telefoon" id="edit_telefoon">
                </div>
                <div class="form-group">
                    <label>Afdeling</label>
                    <input type="text" name="afdeling" id="edit_afdeling" list="afdelingOptions">
                </div>
                <div class="form-group">
                    <label>Rol/Functie</label>
                    <input type="text" name="rol" id="edit_rol" list="rolOptions">
                </div>
                <div class="form-group">
                    <label>Status <span class="required-star">*</span></label>
                    <select name="status" id="edit_status" required>
                        <option value="">Selecteer…</option>
                        <?php foreach ($uniqueStatuses as $s): ?>
                            <option value="<?= htmlspecialchars($s) ?>"><?= htmlspecialchars($s) ?></option>
                        <?php endforeach; ?>
                        <option value="Actief">Actief</option>
                        <option value="Inactief">Inactief</option>
                        <option value="Ziek">Ziek</option>
                        <option value="Verlof">Verlof</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Geboortedatum</label>
                    <input type="date" name="geboortedatum" id="edit_geboortedatum">
                </div>
                <div class="form-group">
                    <label>In dienst sinds</label>
                    <input type="date" name="indienst_datum" id="edit_indienst">
                </div>
            </div>

            <div class="pt-4 flex justify-end gap-3">
                <button type="button" class="btn-secondary" onclick="closeEditModal()">Annuleren</button>
                <button type="submit" class="btn-primary"><i class="fa-solid fa-save mr-2"></i> Wijzigingen Opslaan</button>
            </div>
        </form>
    </div>
</div>

<script>
    // ======= Helpers =======
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

    // ======= Modals =======
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

    // ======= Details =======
    function showDetails(d) {
        var fields = {
            'Medewerker ID': pick(d, ['employeeId', 'id']),
            'Voornaam': pick(d, ['voornaam', 'first_name', 'firstName', 'name_first']),
            'Achternaam': pick(d, ['achternaam', 'last_name', 'lastName', 'surname']),
            'E-mail': pick(d, ['email']),
            'Telefoon': pick(d, ['telefoon', 'phone', 'phone_number', 'mobile']),
            'Afdeling': pick(d, ['afdeling', 'department']),
            'Rol/Functie': pick(d, ['rol', 'functie', 'role', 'position', 'job_title']),
            'Status': pick(d, ['status']),
            'Geboortedatum': toNLDate(pick(d, ['geboortedatum', 'birthdate', 'dob'])),
            'In dienst sinds': toNLDate(pick(d, ['indienst_datum', 'startdatum', 'hire_date', 'start_date'])),
            'Contract einddatum': toNLDate(pick(d, ['einddatum', 'contract_einddatum', 'end_date']))
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

    // ======= Edit =======
    function showEdit(d) {
        document.getElementById('edit_id').value = pick(d, ['employeeId', 'id']);
        document.getElementById('edit_voornaam').value = pick(d, ['voornaam', 'first_name', 'firstName', 'name_first']);
        document.getElementById('edit_achternaam').value = pick(d, ['achternaam', 'last_name', 'lastName', 'surname']);
        document.getElementById('edit_email').value = pick(d, ['email']);
        document.getElementById('edit_telefoon').value = pick(d, ['telefoon', 'phone', 'phone_number', 'mobile']);
        document.getElementById('edit_afdeling').value = pick(d, ['afdeling', 'department']);
        document.getElementById('edit_rol').value = pick(d, ['rol', 'functie', 'role', 'position', 'job_title']);
        document.getElementById('edit_status').value = pick(d, ['status']) || '';
        document.getElementById('edit_geboortedatum').value = toISODate(pick(d, ['geboortedatum', 'birthdate', 'dob']));
        document.getElementById('edit_indienst').value = toISODate(pick(d, ['indienst_datum', 'startdatum', 'hire_date', 'start_date']));

        document.getElementById('editModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    // ======= Filter =======
    function filterTable() {
        var q = (document.getElementById('searchInput').value || '').toLowerCase();
        var st = (document.getElementById('statusFilter').value || '').toLowerCase();
        var af = (document.getElementById('afdelingFilter').value || '').toLowerCase();
        var rl = (document.getElementById('rolFilter').value || '').toLowerCase();

        document.querySelectorAll('.employee-row').forEach(function(r) {
            var text = r.textContent.toLowerCase();
            var rSt = r.dataset.status || '';
            var rAf = r.dataset.afdeling || '';
            var rRl = r.dataset.rol || '';

            var okQ = text.indexOf(q) !== -1;
            var okSt = !st || rSt === st;
            var okAf = !af || rAf === af;
            var okRl = !rl || rRl === rl;

            r.style.display = (okQ && okSt && okAf && okRl) ? '' : 'none';
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
