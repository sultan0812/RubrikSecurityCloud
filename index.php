<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

require_once 'db.php';

$parsedResult = null;
$graphqlMutation = null;
$graphqlVariables = null;
$showSummary = false;

$mode = isset($_POST['report_mode']) ? $_POST['report_mode'] : 'NEW';

// Ambil daftar Threat Actor dari database untuk Dropdown
$dbActorsQuery = $conn->query("SELECT id, name FROM threat_actors WHERE category = 'EXISTING' ORDER BY name ASC");
$dbActors = [];
if ($dbActorsQuery) {
    while ($row = $dbActorsQuery->fetch_assoc()) {
        $dbActors[] = $row;
    }
}

function categorizeHashes($rawHashes) {
    if (is_array($rawHashes)) {
        $lines = $rawHashes;
    } else {
        $lines = explode("\n", str_replace("\r", "", $rawHashes));
    }

    $md5 = [];
    $sha1 = [];
    $sha256 = [];
    $allHashes = [];

    foreach ($lines as $line) {
        $clean = trim(preg_replace('/[\'",\s]+/', '', $line));
        if (empty($clean)) continue;

        $allHashes[] = $clean;

        if (preg_match('/^[a-fA-F0-9]{32}$/', $clean)) {
            $md5[] = $clean;
        } elseif (preg_match('/^[a-fA-F0-9]{40}$/', $clean)) {
            $sha1[] = $clean;
        } elseif (preg_match('/^[a-fA-F0-9]{64}$/', $clean)) {
            $sha256[] = $clean;
        }
    }

    return [
        'md5' => $md5,
        'sha1' => $sha1,
        'sha256' => $sha256,
        'all' => $allHashes,
        'total' => count($allHashes)
    ];
}

function formatHashBreakdown($iocs) {
    $parts = [];
    if (count($iocs['md5']) > 0) $parts[] = "MD5: " . count($iocs['md5']);
    if (count($iocs['sha1']) > 0) $parts[] = "SHA1: " . count($iocs['sha1']);
    if (count($iocs['sha256']) > 0) $parts[] = "SHA256: " . count($iocs['sha256']);

    return empty($parts) ? "Tanpa Hash Valid" : implode(", ", $parts);
}

function parseBulkTextRerun($bulkText) {
    $lines = explode("\n", str_replace("\r", "", $bulkText));
    $actors = [];
    $currentActorName = null;
    $currentHashes = [];

    foreach ($lines as $line) {
        $trimmed = trim($line);
        if (empty($trimmed)) continue;

        $cleanHash = trim(preg_replace('/[\'",\s]+/', '', $trimmed));
        if (preg_match('/^[a-fA-F0-9]{32}$/', $cleanHash) || preg_match('/^[a-fA-F0-9]{40}$/', $cleanHash) || preg_match('/^[a-fA-F0-9]{64}$/', $cleanHash)) {
            if ($currentActorName !== null) {
                $currentHashes[] = $cleanHash;
            }
        } else {
            if ($currentActorName !== null && !empty($currentHashes)) {
                $iocs = categorizeHashes($currentHashes);
                $actors[] = [
                    'name' => $currentActorName,
                    'category' => 'EXISTING',
                    'single' => $iocs,
                    'totalHashes' => $iocs['total']
                ];
            }
            $currentActorName = $trimmed;
            $currentHashes = [];
        }
    }

    if ($currentActorName !== null && !empty($currentHashes)) {
        $iocs = categorizeHashes($currentHashes);
        $actors[] = [
            'name' => $currentActorName,
            'category' => 'EXISTING',
            'single' => $iocs,
            'totalHashes' => $iocs['total']
        ];
    }

    return $actors;
}

if (isset($_GET['action']) && $_GET['action'] === 'reset_session') {
    unset($_SESSION['accumulated_threat_hunt_data']);
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $processedActors = [];

    if ($mode === 'NEW') {
        $actorsData = isset($_POST['new_actors']) ? $_POST['new_actors'] : [];

        foreach ($actorsData as $index => $actor) {
            $name = isset($actor['name']) ? trim($actor['name']) : '';
            $hashesGti = isset($actor['hashes_gti']) ? trim($actor['hashes_gti']) : '';
            $hashesTbn = isset($actor['hashes_tbn']) ? trim($actor['hashes_tbn']) : '';

            $iocsGti = categorizeHashes($hashesGti);
            $iocsTbn = categorizeHashes($hashesTbn);

            $actualCount = max($iocsGti['total'], $iocsTbn['total']);

            if ($name !== '' || $iocsGti['total'] > 0 || $iocsTbn['total'] > 0) {
                $processedActors[] = [
                    'name' => ($name !== '') ? $name : "Threat Actor Baru #" . ($index + 1),
                    'category' => 'NEW',
                    'gti' => $iocsGti,
                    'tbn' => $iocsTbn,
                    'totalHashes' => $actualCount
                ];
            }
        }
    } elseif ($mode === 'RERUN_MANUAL') {
        $actorsData = isset($_POST['rerun_manual_actors']) ? $_POST['rerun_manual_actors'] : [];

        foreach ($actorsData as $index => $actor) {
            $name = isset($actor['name']) ? trim($actor['name']) : '';
            $hashes = isset($actor['hashes']) ? trim($actor['hashes']) : '';
            $iocs = categorizeHashes($hashes);

            if ($name !== '' || $iocs['total'] > 0) {
                $processedActors[] = [
                    'name' => ($name !== '') ? $name : "Threat Actor Existing #" . ($index + 1),
                    'category' => 'EXISTING',
                    'single' => $iocs,
                    'totalHashes' => $iocs['total']
                ];
            }
        }
    } elseif ($mode === 'RERUN_BULK') {
        $bulkRerunText = isset($_POST['bulk_rerun_text']) ? trim($_POST['bulk_rerun_text']) : '';
        if (!empty($bulkRerunText)) {
            $processedActors = parseBulkTextRerun($bulkRerunText);
        }
    } elseif ($mode === 'RERUN_DB') {
        $selectedActorIds = isset($_POST['db_actor_ids']) ? $_POST['db_actor_ids'] : [];

        foreach ($selectedActorIds as $selectedActorId) {
            $selectedActorId = intval($selectedActorId);
            if ($selectedActorId > 0) {
                // Ambil nama TA
                $stmtName = $conn->prepare("SELECT name FROM threat_actors WHERE id = ?");
                $stmtName->bind_param("i", $selectedActorId);
                $stmtName->execute();
                $resName = $stmtName->get_result()->fetch_assoc();
                $actorName = $resName ? $resName['name'] : "Unknown TA";
                $stmtName->close();

                // Ambil semua hash TA tersebut dari database
                $stmtHash = $conn->prepare("SELECT hash_value FROM threat_hashes WHERE actor_id = ?");
                $stmtHash->bind_param("i", $selectedActorId);
                $stmtHash->execute();
                $resHash = $stmtHash->get_result();
                
                $dbHashes = [];
                while ($rowHash = $resHash->fetch_assoc()) {
                    $dbHashes[] = $rowHash['hash_value'];
                }
                $stmtHash->close();

                $iocs = categorizeHashes($dbHashes);
                if ($iocs['total'] > 0) {
                    $processedActors[] = [
                        'name' => $actorName,
                        'category' => 'EXISTING',
                        'single' => $iocs,
                        'totalHashes' => $iocs['total']
                    ];
                }
            }
        }
    }

    $parsedResult = $processedActors;
    $showSummary = true;

    if (!isset($_SESSION['accumulated_threat_hunt_data'])) {
        $_SESSION['accumulated_threat_hunt_data'] = [];
    }

    foreach ($processedActors as $actor) {
        $_SESSION['accumulated_threat_hunt_data'][] = $actor;
    }

    $mutationArgs = [];
    $mutationBody = [];
    $variablesPayload = [];
    $datePrefix = date('y_m_d');
    $inputCounter = 0;

    foreach ($processedActors as $actor) {
        if ($actor['category'] === 'NEW') {
            $clusters = [
                'GTI' => $actor['gti'],
                'TBN' => $actor['tbn']
            ];

            foreach ($clusters as $clusterTag => $iocs) {
                if ($iocs['total'] > 0) {
                    $varKey = "input{$inputCounter}";

                    $mutationArgs[] = "\${$varKey}: StartTurboThreatHuntInput!";
                    $mutationBody[] = "  hunt{$inputCounter}: startTurboThreatHunt(input: \${$varKey}) {\n    huntId\n  }";

                    $indicators = [];
                    foreach ($iocs['all'] as $hashValue) {
                        $indicators[] = [
                            "iocKind" => "IOC_HASH",
                            "iocValue" => $hashValue
                        ];
                    }

                    $variablesPayload[$varKey] = [
                        "config" => [
                            "baseConfig" => [
                                "name" => "{$datePrefix} - Turbo {$actor['name']} ({$clusterTag})",
                                "threatHuntType" => "TURBO_THREAT_HUNT",
                                "ioc" => [
                                    "iocList" => [
                                        "indicatorsOfCompromise" => $indicators
                                    ]
                                ],
                                "snapshotScanLimit" => [
                                    "scanConfig" => new stdClass()
                                ]
                            ],
                            "objectsToScan" => [
                                ["objectType" => "CDM_CLUSTER", "objectIds" => []],
                                ["objectType" => "AWS_NATIVE_ACCOUNT", "objectIds" => []],
                                ["objectType" => "AZURE_NATIVE_SUBSCRIPTION", "objectIds" => []],
                                ["objectType" => "GCP_NATIVE_PROJECT", "objectIds" => []]
                            ]
                        ]
                    ];
                    $inputCounter++;
                }
            }
        } else {
            if ($actor['single']['total'] > 0) {
                $varKey = "input{$inputCounter}";

                $mutationArgs[] = "\${$varKey}: StartTurboThreatHuntInput!";
                $mutationBody[] = "  hunt{$inputCounter}: startTurboThreatHunt(input: \${$varKey}) {\n    huntId\n  }";

                $indicators = [];
                foreach ($actor['single']['all'] as $hashValue) {
                    $indicators[] = [
                        "iocKind" => "IOC_HASH",
                        "iocValue" => $hashValue
                    ];
                }

                $variablesPayload[$varKey] = [
                    "config" => [
                        "baseConfig" => [
                            "name" => "{$datePrefix} - Turbo {$actor['name']}",
                            "threatHuntType" => "TURBO_THREAT_HUNT",
                            "ioc" => [
                                "iocList" => [
                                    "indicatorsOfCompromise" => $indicators
                                ]
                            ],
                            "snapshotScanLimit" => [
                                "scanConfig" => new stdClass()
                            ]
                        ],
                        "objectsToScan" => [
                            ["objectType" => "CDM_CLUSTER", "objectIds" => []],
                            ["objectType" => "AWS_NATIVE_ACCOUNT", "objectIds" => []],
                            ["objectType" => "AZURE_NATIVE_SUBSCRIPTION", "objectIds" => []],
                            ["objectType" => "GCP_NATIVE_PROJECT", "objectIds" => []]
                        ]
                    ]
                ];
                $inputCounter++;
            }
        }
    }

    $graphqlMutation = "mutation StartBulkTurboHunt(" . implode(", ", $mutationArgs) . ") {\n" . implode("\n", $mutationBody) . "\n}";
    $graphqlVariables = json_encode($variablesPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}

$accumulatedCount = isset($_SESSION['accumulated_threat_hunt_data']) ? count($_SESSION['accumulated_threat_hunt_data']) : 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rubrik Security Cloud - Threat Hunt Engine</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        .glass-card {
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .custom-scrollbar::-webkit-scrollbar {
            width: 5px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: rgba(15, 23, 42, 0.6);
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(51, 65, 85, 0.8);
            border-radius: 4px;
        }
    </style>
    <script>
        function copyToClipboard(elementId, btnElement) {
            const textToCopy = document.getElementById(elementId).innerText;
            navigator.clipboard.writeText(textToCopy).then(() => {
                const originalText = btnElement.innerHTML;
                btnElement.innerHTML = "✨ Copied!";
                btnElement.classList.add("bg-emerald-500/20", "text-emerald-300", "border-emerald-500/40");
                setTimeout(() => {
                    btnElement.innerHTML = originalText;
                    btnElement.classList.remove("bg-emerald-500/20", "text-emerald-300", "border-emerald-500/40");
                }, 2000);
            });
        }

        function toggleModeDisplay() {
            const modeSelect = document.getElementById("report_mode");
            const newForm = document.getElementById("form_new_ta");
            const rerunManualForm = document.getElementById("form_rerun_manual");
            const rerunBulkForm = document.getElementById("form_rerun_bulk");
            const rerunDbForm = document.getElementById("form_rerun_db");

            newForm.classList.add("hidden");
            rerunManualForm.classList.add("hidden");
            rerunBulkForm.classList.add("hidden");
            rerunDbForm.classList.add("hidden");

            if (modeSelect.value === "NEW") {
                newForm.classList.remove("hidden");
            } else if (modeSelect.value === "RERUN_MANUAL") {
                rerunManualForm.classList.remove("hidden");
            } else if (modeSelect.value === "RERUN_BULK") {
                rerunBulkForm.classList.remove("hidden");
            } else if (modeSelect.value === "RERUN_DB") {
                rerunDbForm.classList.remove("hidden");
            }
        }

        function loadHashesFromDB(selectElement, previewId) {
            const actorId = selectElement.value;
            const previewBox = document.getElementById(previewId);

            if (!actorId) {
                previewBox.value = "";
                return;
            }

            fetch('get_hashes.php?actor_id=' + actorId)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        previewBox.value = "Jumlah Hash: " + data.hashes.length + "\n--------------------\n" + data.hash_text;
                    } else {
                        previewBox.value = "Gagal memuat hash.";
                    }
                })
                .catch(error => console.error('Error:', error));
        }
    </script>
</head>
<body class="bg-[#0b0f19] text-slate-100 min-h-screen p-4 lg:p-8 relative overflow-x-hidden selection:bg-cyan-500 selection:text-slate-950">

    <div class="fixed -top-40 -left-40 w-96 h-96 bg-cyan-500/10 rounded-full blur-3xl pointer-events-none"></div>
    <div class="fixed top-1/2 -right-40 w-96 h-96 bg-blue-600/10 rounded-full blur-3xl pointer-events-none"></div>

    <header class="max-w-7xl mx-auto mb-8 glass-card rounded-2xl p-4 flex flex-col md:flex-row justify-between items-center shadow-2xl relative z-10 border border-slate-800">
        <div class="flex items-center space-x-3 mb-4 md:mb-0">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-cyan-500 to-blue-600 flex items-center justify-center font-extrabold text-slate-950 shadow-lg shadow-cyan-500/20">
                R
            </div>
            <div>
                <h1 class="text-lg font-bold tracking-tight text-white flex items-center gap-2">
                    Rubrik Security Cloud <span class="text-xs px-2 py-0.5 rounded-full bg-cyan-500/10 text-cyan-400 border border-cyan-500/30 font-medium">Enterprise v2.4</span>
                </h1>
                <p class="text-xs text-slate-400">Threat Hunt Automation & Intelligence Engine</p>
            </div>
        </div>
        
        <nav class="flex flex-wrap gap-1 bg-slate-900/80 p-1.5 rounded-xl border border-slate-800 text-xs font-semibold">
            <a href="index.php" class="px-4 py-2 rounded-lg bg-gradient-to-r from-cyan-500 to-blue-600 text-slate-950 font-bold shadow-md shadow-cyan-500/20 transition-all">Threat Hunt Form</a>
            <a href="analytics.php" class="px-4 py-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800/60 transition">Analytics</a>
            <a href="posture.php" class="px-4 py-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800/60 transition">Security Posture</a>
            <a href="activity.php" class="px-4 py-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800/60 transition">TH Activity Report</a>
        </nav>
    </header>

    <main class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-8 relative z-10">
        
        <form action="index.php" method="POST" class="lg:col-span-6 space-y-6">
            <div class="glass-card p-6 rounded-2xl shadow-2xl relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-cyan-500 via-blue-500 to-indigo-500"></div>

                <div class="mb-6 p-4 rounded-xl bg-slate-900/90 border border-slate-800 flex justify-between items-center shadow-inner">
                    <div class="flex items-center space-x-3">
                        <div class="relative flex h-3 w-3">
                          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-cyan-400 opacity-75"></span>
                          <span class="relative inline-flex rounded-full h-3 w-3 bg-cyan-500"></span>
                        </div>
                        <div>
                            <span class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold block">Batch Akumulasi Engine</span>
                            <span class="text-sm font-bold text-white font-mono"><?php echo $accumulatedCount; ?> <span class="text-slate-500 font-normal">/ 35 Threat Actors</span></span>
                        </div>
                    </div>
                    <?php if ($accumulatedCount > 0): ?>
                        <a href="index.php?action=reset_session" onclick="return confirm('Yakin ingin mereset akumulasi data batch?')" class="text-xs text-rose-400 hover:text-rose-300 font-semibold px-3 py-1.5 rounded-lg bg-rose-500/10 border border-rose-500/20 hover:bg-rose-500/20 transition">Reset Batch</a>
                    <?php endif; ?>
                </div>

                <div class="mb-6">
                    <label class="block text-xs font-bold uppercase tracking-wider text-cyan-400 mb-2">Pilih Mode Input Report</label>
                    <div class="relative">
                        <select 
                            name="report_mode" 
                            id="report_mode" 
                            onchange="toggleModeDisplay()"
                            class="w-full px-4 py-3 bg-slate-900/90 border border-slate-700/80 rounded-xl text-sm font-semibold text-slate-100 focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-all appearance-none cursor-pointer"
                        >
                            <option value="NEW" <?php echo $mode === 'NEW' ? 'selected' : ''; ?>>✨ Mode 1: Input 2 Threat Actor Baru (GTI & TBN)</option>
                            <option value="RERUN_MANUAL" <?php echo $mode === 'RERUN_MANUAL' ? 'selected' : ''; ?>>⚡ Mode 2: Rerun Threat Actor (Form Manual)</option>
                            <option value="RERUN_BULK" <?php echo $mode === 'RERUN_BULK' ? 'selected' : ''; ?>>🚀 Mode 3: Rerun Threat Actor (Bulk Paste Teks)</option>
                            <option value="RERUN_DB" <?php echo $mode === 'RERUN_DB' ? 'selected' : ''; ?>>🗄️ Mode 4: 33 Auto Dropdown dari Database MySQL</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-400">
                          <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l0.707 0.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                        </div>
                    </div>
                </div>

                <!-- OPSI 1: NEW -->
                <div id="form_new_ta" class="<?php echo $mode === 'NEW' ? '' : 'hidden'; ?> space-y-5">
                    <div class="flex justify-between items-center border-b border-slate-800 pb-3">
                        <h3 class="text-sm font-bold text-amber-400 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-amber-400"></span> Input 2 Threat Actor Baru
                        </h3>
                    </div>
                    <div class="space-y-5 max-h-[480px] overflow-y-auto pr-2 custom-scrollbar">
                        <?php for ($i = 0; $i < 2; $i++): ?>
                            <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800 space-y-3">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Nama Threat Actor #<?php echo $i + 1; ?></label>
                                    <input type="text" name="new_actors[<?php echo $i; ?>][name]" placeholder="Contoh: trojan Joke" class="w-full px-3.5 py-2 bg-slate-950/80 border border-slate-800 rounded-lg text-xs text-slate-100">
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div class="bg-slate-950/50 p-3 rounded-lg border border-cyan-500/20">
                                        <label class="block text-[11px] font-bold text-cyan-400 mb-1">Hashes GTI</label>
                                        <textarea name="new_actors[<?php echo $i; ?>][hashes_gti]" rows="3" class="w-full p-2 bg-slate-900 border border-slate-800 rounded text-xs font-mono text-slate-300"></textarea>
                                    </div>
                                    <div class="bg-slate-950/50 p-3 rounded-lg border border-purple-500/20">
                                        <label class="block text-[11px] font-bold text-purple-400 mb-1">Hashes TBN</label>
                                        <textarea name="new_actors[<?php echo $i; ?>][hashes_tbn]" rows="3" class="w-full p-2 bg-slate-900 border border-slate-800 rounded text-xs font-mono text-slate-300"></textarea>
                                    </div>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <!-- OPSI 2: MANUAL -->
                <div id="form_rerun_manual" class="<?php echo $mode === 'RERUN_MANUAL' ? '' : 'hidden'; ?> space-y-4">
                    <div class="flex justify-between items-center border-b border-slate-800 pb-3">
                        <h3 class="text-sm font-bold text-blue-400 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-blue-400"></span> Rerun Threat Actor (Manual)
                        </h3>
                    </div>
                    <div class="space-y-3 max-h-[420px] overflow-y-auto pr-2 custom-scrollbar">
                        <?php for ($i = 0; $i < 33; $i++): ?>
                            <div class="p-3 bg-slate-900/60 rounded-xl border border-slate-800 space-y-2">
                                <input type="text" name="rerun_manual_actors[<?php echo $i; ?>][name]" placeholder="Nama TA" class="w-full px-3 py-1.5 bg-slate-950/80 border border-slate-800 rounded-lg text-xs">
                                <textarea name="rerun_manual_actors[<?php echo $i; ?>][hashes]" rows="2" placeholder="List hash..." class="w-full p-2 bg-slate-950/80 border border-slate-800 rounded-lg text-xs font-mono text-slate-300"></textarea>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <!-- OPSI 3: BULK -->
                <div id="form_rerun_bulk" class="<?php echo $mode === 'RERUN_BULK' ? '' : 'hidden'; ?> space-y-3">
                    <div class="flex justify-between items-center border-b border-slate-800 pb-3">
                        <h3 class="text-sm font-bold text-indigo-400 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-indigo-400"></span> Bulk Paste Teks
                        </h3>
                    </div>
                    <textarea name="bulk_rerun_text" rows="12" placeholder="Nama TA&#10;Hash..." class="w-full p-3.5 bg-slate-950/80 border border-slate-800 rounded-xl text-xs font-mono text-cyan-300"></textarea>
                </div>

                <!-- OPSI 4: 33 AUTO DROPDOWN DARI DATABASE MYSQL -->
                <div id="form_rerun_db" class="<?php echo $mode === 'RERUN_DB' ? '' : 'hidden'; ?> space-y-4">
                    <div class="flex justify-between items-center border-b border-slate-800 pb-3">
                        <h3 class="text-sm font-bold text-cyan-400 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-cyan-400 animate-pulse"></span> 33 Auto Dropdown Database
                        </h3>
                        <span class="text-[10px] bg-cyan-500/10 text-cyan-300 border border-cyan-500/20 px-2.5 py-1 rounded-full font-semibold">Multi-Select Slots</span>
                    </div>

                    <p class="text-[11px] text-slate-400">Pilih Threat Actor pada slot di bawah ini. Daftar hash akan otomatis termuat dari database.</p>

                    <div class="space-y-4 max-h-[480px] overflow-y-auto pr-2 custom-scrollbar">
                        <?php for ($i = 0; $i < 33; $i++): ?>
                            <div class="p-3 bg-slate-900/60 rounded-xl border border-slate-800 space-y-2">
                                <label class="block text-[11px] font-bold text-slate-300">Slot Threat Actor #<?php echo $i + 1; ?></label>
                                <select name="db_actor_ids[]" onchange="loadHashesFromDB(this, 'preview_hash_<?php echo $i; ?>')" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-xs font-medium text-cyan-300 focus:outline-none focus:border-cyan-500">
                                    <option value="">-- Pilih Threat Actor --</option>
                                    <?php foreach ($dbActors as $actor): ?>
                                        <option value="<?php echo $actor['id']; ?>"><?php echo htmlspecialchars($actor['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <textarea id="preview_hash_<?php echo $i; ?>" rows="2" readonly placeholder="Hash preview..." class="w-full p-2 bg-slate-950/80 border border-slate-800 rounded text-[11px] font-mono text-emerald-400"></textarea>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <button 
                    type="submit" 
                    class="mt-6 w-full py-3.5 bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 text-slate-950 font-extrabold text-xs uppercase tracking-wider rounded-xl transition-all shadow-lg shadow-cyan-500/20 active:scale-[0.99]"
                >
                    Submit & Generasi Engine
                </button>
            </div>
        </form>

        <div class="lg:col-span-6 space-y-6">
            <?php if ($showSummary && $parsedResult): ?>
                
                <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 flex justify-between items-center glass-card shadow-lg">
                    <div class="flex items-center space-x-2 text-xs text-emerald-400 font-semibold">
                        <span class="text-base">✓</span>
                        <span>Berhasil memproses <?php echo count($parsedResult); ?> Threat Actor (Akumulasi: <?php echo $accumulatedCount; ?> TA).</span>
                    </div>
                    <a href="activity.php" class="text-xs bg-emerald-500 text-slate-950 px-3 py-1.5 rounded-lg font-bold hover:bg-emerald-400 transition shadow-md shadow-emerald-500/20">Buka Activity Report →</a>
                </div>

                <div class="glass-card p-6 rounded-2xl shadow-2xl relative overflow-hidden">
                    <div class="flex justify-between items-center mb-3">
                        <h2 class="text-sm font-bold text-emerald-400 tracking-wide uppercase flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-emerald-400"></span> Ringkasan Result Report
                        </h2>
                        <button onclick="copyToClipboard('summaryText', this)" class="text-xs bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-300 px-3 py-1.5 rounded-lg font-medium transition shadow">Copy Text</button>
                    </div>
                    
                    <pre id="summaryText" class="p-4 bg-slate-950/90 rounded-xl text-slate-300 text-xs overflow-x-auto font-mono max-h-[220px] whitespace-pre-wrap leading-relaxed border border-slate-800">List threat actor:
<?php 
foreach ($parsedResult as $actor) {
    echo htmlspecialchars($actor['name']) . "\n";
}
?>

List jumlah hash
<?php 
foreach ($parsedResult as $actor) {
    echo $actor['totalHashes'] . "\n";
}
?>

List hash SHA256 / MD5 / SHA1 Detail
<?php 
foreach ($parsedResult as $actor) {
    if ($actor['category'] === 'NEW') {
        $gtiBreakdown = formatHashBreakdown($actor['gti']);
        $tbnBreakdown = formatHashBreakdown($actor['tbn']);
        
        echo htmlspecialchars($actor['name']) . "\n";
        if ($actor['gti']['total'] > 0) echo "  └─ GTI: " . $gtiBreakdown . "\n";
        if ($actor['gti']['total'] > 0) echo "  └─ TBN: " . $tbnBreakdown . "\n";
    } else {
        $singleBreakdown = formatHashBreakdown($actor['single']);
        echo htmlspecialchars($actor['name']) . " | " . $singleBreakdown . "\n";
    }
}
?>
</pre>
                </div>

                <div class="glass-card p-6 rounded-2xl shadow-2xl relative overflow-hidden">
                    <div class="flex justify-between items-center mb-2">
                        <h2 class="text-sm font-bold text-indigo-400 tracking-wide uppercase flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-indigo-400"></span> GraphQL Bulk Mutation
                        </h2>
                        <button onclick="copyToClipboard('mutationText', this)" class="text-xs bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-300 px-3 py-1.5 rounded-lg font-medium transition shadow">Copy Mutation</button>
                    </div>
                    <pre id="mutationText" class="p-4 bg-slate-950/90 rounded-xl text-cyan-400 text-xs overflow-x-auto font-mono max-h-[180px] border border-slate-800"><?php echo htmlspecialchars($graphqlMutation); ?></pre>
                </div>

                <div class="glass-card p-6 rounded-2xl shadow-2xl relative overflow-hidden">
                    <div class="flex justify-between items-center mb-2">
                        <h2 class="text-sm font-bold text-purple-400 tracking-wide uppercase flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-purple-400"></span> GraphQL Variables JSON
                        </h2>
                        <button onclick="copyToClipboard('variablesText', this)" class="text-xs bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-300 px-3 py-1.5 rounded-lg font-medium transition shadow">Copy Variables</button>
                    </div>
                    <pre id="variablesText" class="p-4 bg-slate-950/90 rounded-xl text-emerald-400 text-xs overflow-x-auto font-mono max-h-[220px] border border-slate-800"><?php echo htmlspecialchars($graphqlVariables); ?></pre>
                </div>

            <?php else: ?>
                <div class="glass-card p-12 rounded-2xl border border-dashed border-slate-800 text-center space-y-3">
                    <div class="w-12 h-12 rounded-2xl bg-slate-900 border border-slate-800 flex items-center justify-center mx-auto text-slate-500 text-xl">
                        ⚡
                    </div>
                    <h3 class="text-sm font-bold text-slate-300">Siap Generasi Intelijen</h3>
                    <p class="text-xs text-slate-500 max-w-sm mx-auto">Silakan pilih mode input di sebelah kiri (termasuk 33 Slot Dropdown Database) untuk memuat data Threat Actor.</p>
                </div>
            <?php endif; ?>
        </div>

    </main>

</body>
</html>