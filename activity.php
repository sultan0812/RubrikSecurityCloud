<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

$linkDokumen = "https://drive.google.com/drive/folders/1kI37WcZheAZuREnEd2ErUmX1tjWeZc82?usp=sharing";
$tanggal = date('l, d F Y');
$waktu = "06:00 WIB";

$defaultGtiObjects = "6.327";
$defaultTbnObjects = "1.313";

$actorsList = [];

if (isset($_POST['confirm_scanned_popup'])) {
    $defaultGtiObjects = trim($_POST['gti_objects']);
    $defaultTbnObjects = trim($_POST['tbn_objects']);
}

if (isset($_SESSION['accumulated_threat_hunt_data']) && !empty($_SESSION['accumulated_threat_hunt_data'])) {
    foreach ($_SESSION['accumulated_threat_hunt_data'] as $index => $sessionActor) {
        $actorsList[] = [
            'id' => $index + 1,
            'name' => $sessionActor['name'],
            'hash_count' => $sessionActor['totalHashes'],
            'gti_scanned' => $defaultGtiObjects,
            'tbn_scanned' => $defaultTbnObjects
        ];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['raw_actors'])) {
    $actorsList = [];
    $tanggal = isset($_POST['tanggal']) ? trim($_POST['tanggal']) : $tanggal;
    $waktu = isset($_POST['waktu']) ? trim($_POST['waktu']) : $waktu;
    $linkDokumen = isset($_POST['link_dokumen']) ? trim($_POST['link_dokumen']) : $linkDokumen;
    
    $defaultGtiObjects = isset($_POST['gti_objects']) ? trim($_POST['gti_objects']) : $defaultGtiObjects;
    $defaultTbnObjects = isset($_POST['tbn_objects']) ? trim($_POST['tbn_objects']) : $defaultTbnObjects;

    $rawActors = $_POST['raw_actors'];
    foreach ($rawActors as $actor) {
        $name = trim($actor['name']);
        $hashCount = trim($actor['hash_count']);

        if ($name !== '' || $hashCount !== '') {
            $actorsList[] = [
                'id' => count($actorsList) + 1,
                'name' => ($name !== '') ? $name : "Unnamed Threat Actor",
                'hash_count' => ($hashCount !== '') ? $hashCount : "0",
                'gti_scanned' => $defaultGtiObjects,
                'tbn_scanned' => $defaultTbnObjects
            ];
        }
    }
}

$totalTA = count($actorsList);
$triggerPopup = ($totalTA >= 35 && !isset($_POST['confirm_scanned_popup']));
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Threat Hunt Activity Report - RSC Enterprise</title>
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
    </style>
    <script>
        function copyActivityReport(btnElement) {
            const reportContent = document.getElementById("activityReportText").innerText;
            navigator.clipboard.writeText(reportContent).then(() => {
                const originalText = btnElement.innerText;
                btnElement.innerText = "Copied!";
                btnElement.classList.add("bg-emerald-500", "text-slate-950");
                setTimeout(() => {
                    btnElement.innerText = originalText;
                    btnElement.classList.remove("bg-emerald-500", "text-slate-950");
                }, 2000);
            });
        }

        function closeModal() {
            document.getElementById("scannedModal").classList.add("hidden");
        }
    </script>
</head>
<body class="bg-[#0b0f19] text-slate-100 min-h-screen p-4 lg:p-8 relative selection:bg-cyan-500 selection:text-slate-950">

    <!-- POPUP MODAL ELEGANT -->
    <?php if ($triggerPopup): ?>
        <div id="scannedModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-md z-50 flex items-center justify-center p-4">
            <div class="glass-card border border-cyan-500/40 p-6 rounded-2xl max-w-md w-full shadow-2xl space-y-5 relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-emerald-400 via-cyan-500 to-blue-500"></div>

                <div class="flex justify-between items-center border-b border-slate-800 pb-3">
                    <div class="flex items-center space-x-2">
                        <span class="text-xl">🎉</span>
                        <h3 class="text-sm font-bold text-white tracking-wide">Target 35 TA Reached!</h3>
                    </div>
                    <button type="button" onclick="closeModal()" class="text-slate-400 hover:text-white">✕</button>
                </div>

                <p class="text-xs text-slate-300 leading-relaxed">
                    Total akumulasi telah mencapai <b class="text-cyan-400"><?php echo $totalTA; ?> Threat Actor</b>. Silakan periksa & konfirmasi jumlah <b>Scanned Object</b> sebelum mencetak laporan:
                </p>

                <form action="activity.php" method="POST" class="space-y-4">
                    <input type="hidden" name="confirm_scanned_popup" value="1">
                    
                    <div>
                        <label class="block text-xs font-bold text-cyan-400 mb-1">Default Scanned GTI</label>
                        <input type="text" name="gti_objects" value="6.327" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-sm font-mono text-cyan-300">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-purple-400 mb-1">Default Scanned TBN</label>
                        <input type="text" name="tbn_objects" value="1.313" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-sm font-mono text-purple-300">
                    </div>

                    <div class="flex space-x-3 pt-2">
                        <button type="button" onclick="closeModal()" class="w-1/2 py-2.5 bg-slate-800 hover:bg-slate-700 text-xs font-semibold rounded-xl transition">Nanti Saja</button>
                        <button type="submit" class="w-1/2 py-2.5 bg-cyan-500 hover:bg-cyan-400 text-slate-950 text-xs font-bold rounded-xl transition shadow-lg shadow-cyan-500/20">Konfirmasi & Cetak</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- NAVBAR INTEGRASI ELEGANT -->
    <header class="max-w-7xl mx-auto mb-8 glass-card rounded-2xl p-4 flex flex-col md:flex-row justify-between items-center shadow-2xl relative z-10 border border-slate-800">
        <div class="flex items-center space-x-3 mb-4 md:mb-0">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-cyan-500 to-blue-600 flex items-center justify-center font-extrabold text-slate-950 shadow-lg shadow-cyan-500/20">
                R
            </div>
            <div>
                <h1 class="text-lg font-bold tracking-tight text-white flex items-center gap-2">
                    Rubrik Security Cloud <span class="text-xs px-2 py-0.5 rounded-full bg-cyan-500/10 text-cyan-400 border border-cyan-500/30 font-medium">Enterprise v2.4</span>
                </h1>
                <p class="text-xs text-slate-400">Threat Hunt Activity Report Console</p>
            </div>
        </div>
        
        <nav class="flex flex-wrap gap-1 bg-slate-900/80 p-1.5 rounded-xl border border-slate-800 text-xs font-semibold">
            <a href="index.php" class="px-4 py-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800/60 transition">Threat Hunt Form</a>
            <a href="analytics.php" class="px-4 py-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800/60 transition">Analytics</a>
            <a href="posture.php" class="px-4 py-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800/60 transition">Security Posture</a>
            <a href="activity.php" class="px-4 py-2 rounded-lg bg-gradient-to-r from-cyan-500 to-blue-600 text-slate-950 font-bold shadow-md shadow-cyan-500/20 transition-all">TH Activity Report</a>
        </nav>
    </header>

    <main class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-8 relative z-10">
        
        <!-- FORM INPUT (LEFT PANEL - 6 COLS) -->
        <form id="activityForm" action="activity.php" method="POST" class="lg:col-span-6 space-y-6">
            <div class="glass-card p-6 rounded-2xl shadow-2xl relative overflow-hidden space-y-5">
                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-cyan-500 to-blue-500"></div>
                
                <div class="border-b border-slate-800 pb-3 flex justify-between items-center">
                    <h2 class="text-sm font-bold text-cyan-400 uppercase tracking-wider">Configure Activity Report</h2>
                    <span class="text-xs bg-cyan-500/10 text-cyan-300 border border-cyan-500/20 px-2.5 py-1 rounded-full font-bold">Total: <?php echo $totalTA; ?> TA</span>
                </div>

                <!-- WAKTU & DOKUMEN -->
                <div class="space-y-3">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Tanggal Laporan</label>
                            <input type="text" name="tanggal" value="<?php echo htmlspecialchars($tanggal); ?>" class="w-full px-3 py-2 bg-slate-950/80 border border-slate-800 rounded-lg text-xs font-medium text-slate-200">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Jam / Pukul</label>
                            <input type="text" name="waktu" value="<?php echo htmlspecialchars($waktu); ?>" class="w-full px-3 py-2 bg-slate-950/80 border border-slate-800 rounded-lg text-xs font-medium text-slate-200">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Link Dokumen Evidence</label>
                        <input type="text" name="link_dokumen" value="<?php echo htmlspecialchars($linkDokumen); ?>" class="w-full px-3 py-2 bg-slate-950/80 border border-slate-800 rounded-lg text-xs font-mono text-cyan-300">
                    </div>
                </div>

                <!-- SCANNED OBJECT DEFAULTS -->
                <div class="p-4 bg-slate-900/60 rounded-xl border border-slate-800 grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold text-cyan-400 mb-1">Scanned Object GTI</label>
                        <input type="text" name="gti_objects" value="<?php echo htmlspecialchars($defaultGtiObjects); ?>" class="w-full px-3 py-1.5 bg-slate-950 border border-slate-800 rounded-lg text-xs font-mono text-cyan-200">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-purple-400 mb-1">Scanned Object TBN</label>
                        <input type="text" name="tbn_objects" value="<?php echo htmlspecialchars($defaultTbnObjects); ?>" class="w-full px-3 py-1.5 bg-slate-950 border border-slate-800 rounded-lg text-xs font-mono text-purple-200">
                    </div>
                </div>

                <!-- LIST INPUT ITEM -->
                <div class="space-y-3">
                    <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Item Batch Active List</span>
                    
                    <div class="space-y-2 max-h-[360px] overflow-y-auto pr-2 custom-scrollbar">
                        <?php for ($i = 0; $i < max(35, $totalTA); $i++): ?>
                            <?php 
                                $valName = isset($actorsList[$i]['name']) ? $actorsList[$i]['name'] : '';
                                $valHash = isset($actorsList[$i]['hash_count']) ? $actorsList[$i]['hash_count'] : '';
                            ?>
                            <div class="p-2.5 bg-slate-900/60 rounded-lg border border-slate-800 flex space-x-2 items-center">
                                <span class="text-xs font-bold text-slate-500 w-6"><?php echo $i + 1; ?>.</span>
                                <input 
                                    type="text" 
                                    name="raw_actors[<?php echo $i; ?>][name]" 
                                    value="<?php echo htmlspecialchars($valName); ?>"
                                    placeholder="Nama Threat Actor" 
                                    class="w-2/3 px-2.5 py-1.5 bg-slate-950/80 border border-slate-800 rounded text-xs font-medium"
                                >
                                <input 
                                    type="text" 
                                    name="raw_actors[<?php echo $i; ?>][hash_count]" 
                                    value="<?php echo htmlspecialchars($valHash); ?>"
                                    placeholder="Jml Hash" 
                                    class="w-1/3 px-2 py-1.5 bg-slate-950/80 border border-slate-800 rounded text-xs font-mono text-center text-cyan-400"
                                >
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <button type="submit" class="w-full py-3.5 bg-slate-800 hover:bg-slate-700 border border-slate-700 text-cyan-400 font-bold text-xs uppercase tracking-wider rounded-xl transition">
                    Update Preview Text
                </button>
            </div>
        </form>

        <!-- OUTPUT REPORT (RIGHT PANEL - 6 COLS) -->
        <div class="lg:col-span-6">
            <div class="glass-card p-6 rounded-2xl shadow-2xl relative overflow-hidden flex flex-col justify-between h-full">
                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-emerald-400 to-cyan-500"></div>

                <div>
                    <div class="flex justify-between items-center mb-4 border-b border-slate-800 pb-3">
                        <h3 class="text-xs font-bold text-emerald-400 uppercase tracking-wider flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                            Generated Activity Report
                        </h3>
                        <button onclick="copyActivityReport(this)" class="text-xs bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-300 px-3.5 py-1.5 rounded-lg font-semibold transition">
                            Copy Text
                        </button>
                    </div>

                    <pre id="activityReportText" class="p-4 bg-slate-950/90 rounded-xl text-slate-200 text-xs font-mono whitespace-pre-wrap leading-relaxed border border-slate-800 max-h-[580px] overflow-y-auto">Threat Hunt Activity Report
<?php echo htmlspecialchars($tanggal); ?>

Pukul <?php echo htmlspecialchars($waktu); ?>

Link Dokumen : <?php echo htmlspecialchars($linkDokumen); ?>


<?php 
if (!empty($actorsList)) {
    foreach ($actorsList as $index => $actor) {
        echo ($index + 1) . ". " . htmlspecialchars($actor['name']) . " - OPS RSC\n\n";
        echo "Cluster GTI\n";
        echo "- Total IOC : " . htmlspecialchars($actor['hash_count']) . " Hash\n";
        echo "- Hunt Type : Turbo Threat Hunt\n";
        echo "- Scanned Object : " . htmlspecialchars($actor['gti_scanned']) . "\n";
        echo "- Total Hunt : 1 Hunt\n";
        echo "- Matches Hunt : 0\n\n";
        echo "Cluster TBN\n";
        echo "- Total IOC : " . htmlspecialchars($actor['hash_count']) . " Hash\n";
        echo "- Hunt Type : Turbo Threat Hunt\n";
        echo "- Scanned Object : " . htmlspecialchars($actor['tbn_scanned']) . "\n";
        echo "- Total Hunt : 1 Hunt\n";
        echo "- Matches Hunt : 0\n\n";
        echo "-----\n\n";
    }
} else {
    echo "Belum ada data Threat Hunt tersimpan.";
}
?></pre>
                </div>
                
                <p class="text-[11px] text-slate-500 mt-4 text-center">Form terintegrasi secara dinamis dengan Session Engine Dashboard.</p>
            </div>
        </div>

    </main>

</body>
</html>