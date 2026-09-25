<?php
// Set zona waktu Indonesia
date_default_timezone_set('Asia/Jakarta');

$showResult = false;
$data = [
    'tanggal' => date('l, d F Y'),
    'waktu' => date('H:i') . ' WIB',
    'obj_anomaly_enabled' => '',
    'obj_threat_enabled' => '',
    'anomalies' => '0',
    'threats' => '0',
    'matched_hunts' => '0',
    'ioc_days' => '2',
    'ioc_hours' => '10',
    'file_hashes' => '',
    'yara_rules' => '',
    'scanned_backup' => '',
    'anomalies_backup' => '0',
    'scanned_hashes' => '',
    'scanned_yara' => '',
    'backup_with_threat' => '0',
    'hunt_in_progress' => '0',
    'hunt_failed' => '0',
    'hunt_completed' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($data as $key => $val) {
        if (isset($_POST[$key])) {
            $data[$key] = trim($_POST[$key]);
        }
    }
    $showResult = true;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Threat Analytics Report - RSC Enterprise</title>
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
        function copyAnalyticsReport(btnElement) {
            const reportContent = document.getElementById("analyticsReportText").innerText;
            navigator.clipboard.writeText(reportContent).then(() => {
                const originalText = btnElement.innerHTML;
                btnElement.innerHTML = "✨ Copied!";
                btnElement.classList.add("bg-emerald-500", "text-slate-950");
                setTimeout(() => {
                    btnElement.innerHTML = originalText;
                    btnElement.classList.remove("bg-emerald-500", "text-slate-950");
                }, 2000);
            });
        }
    </script>
</head>
<body class="bg-[#0b0f19] text-slate-100 min-h-screen p-4 lg:p-8 relative selection:bg-cyan-500 selection:text-slate-950">

    <!-- BACKGROUND GLOW EFFECTS -->
    <div class="fixed -top-40 -left-40 w-96 h-96 bg-cyan-500/10 rounded-full blur-3xl pointer-events-none"></div>
    <div class="fixed top-1/2 -right-40 w-96 h-96 bg-indigo-600/10 rounded-full blur-3xl pointer-events-none"></div>

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
                <p class="text-xs text-slate-400">Threat Intelligence & Analytics Console</p>
            </div>
        </div>
        
        <nav class="flex flex-wrap gap-1 bg-slate-900/80 p-1.5 rounded-xl border border-slate-800 text-xs font-semibold">
            <a href="index.php" class="px-4 py-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800/60 transition">Threat Hunt Form</a>
            <a href="analytics.php" class="px-4 py-2 rounded-lg bg-gradient-to-r from-cyan-500 to-blue-600 text-slate-950 font-bold shadow-md shadow-cyan-500/20 transition-all">Threat Analytics</a>
            <a href="posture.php" class="px-4 py-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800/60 transition">Security Posture</a>
            <a href="activity.php" class="px-4 py-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800/60 transition">TH Activity Report</a>
        </nav>
    </header>

    <main class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-8 relative z-10">
        
        <!-- FORM INPUT ANALYTICS HARIAN (LEFT PANEL - 6 COLS) -->
        <form action="analytics.php" method="POST" class="lg:col-span-6 space-y-6">
            <div class="glass-card p-6 rounded-2xl shadow-2xl relative overflow-hidden space-y-5">
                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-cyan-500 via-blue-500 to-indigo-500"></div>
                
                <div class="border-b border-slate-800 pb-3 flex justify-between items-center">
                    <h2 class="text-sm font-bold text-cyan-400 uppercase tracking-wider flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-cyan-400"></span>
                        Input Data Analytics Harian
                    </h2>
                    <span class="text-[10px] bg-cyan-500/10 text-cyan-300 border border-cyan-500/20 px-2.5 py-1 rounded-full font-bold">Laporan Analytics</span>
                </div>

                <!-- WAKTU REPORT -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Hari & Tanggal Laporan</label>
                        <input type="text" name="tanggal" value="<?php echo htmlspecialchars($data['tanggal']); ?>" class="w-full px-3.5 py-2 bg-slate-950/80 border border-slate-800 rounded-lg text-xs font-medium text-slate-200 focus:outline-none focus:border-cyan-500 transition">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Jam / Pukul Laporan</label>
                        <input type="text" name="waktu" value="<?php echo htmlspecialchars($data['waktu']); ?>" class="w-full px-3.5 py-2 bg-slate-950/80 border border-slate-800 rounded-lg text-xs font-medium text-slate-200 focus:outline-none focus:border-cyan-500 transition">
                    </div>
                </div>

                <div class="space-y-4 max-h-[500px] overflow-y-auto pr-2 custom-scrollbar">
                    
                    <!-- SEKSI 1: THREAT SUMMARY -->
                    <div class="space-y-3 p-4 rounded-xl bg-slate-900/60 border border-slate-800">
                        <span class="block text-xs font-bold text-cyan-400 uppercase tracking-wider">1. Threat Summary</span>
                        
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-medium text-slate-400 mb-1">Obj Anomaly Enabled</label>
                                <input type="text" name="obj_anomaly_enabled" placeholder="Contoh: 20.4k" value="<?php echo htmlspecialchars($data['obj_anomaly_enabled']); ?>" class="w-full px-3 py-1.5 bg-slate-950/80 border border-slate-800 rounded-lg text-xs font-mono text-cyan-300 focus:outline-none focus:border-cyan-500">
                            </div>
                            <div>
                                <label class="block text-[11px] font-medium text-slate-400 mb-1">Obj Threat Enabled</label>
                                <input type="text" name="obj_threat_enabled" placeholder="Contoh: 21.1k" value="<?php echo htmlspecialchars($data['obj_threat_enabled']); ?>" class="w-full px-3 py-1.5 bg-slate-950/80 border border-slate-800 rounded-lg text-xs font-mono text-cyan-300 focus:outline-none focus:border-cyan-500">
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-3 pt-1">
                            <div>
                                <label class="block text-[11px] text-amber-400 font-bold mb-1">Anomalies</label>
                                <input type="text" name="anomalies" value="<?php echo htmlspecialchars($data['anomalies']); ?>" class="w-full px-2.5 py-1.5 bg-slate-950/80 border border-slate-800 rounded-lg text-xs font-mono text-center font-bold text-amber-300 focus:outline-none focus:border-amber-400">
                            </div>
                            <div>
                                <label class="block text-[11px] text-rose-400 font-bold mb-1">Threats</label>
                                <input type="text" name="threats" value="<?php echo htmlspecialchars($data['threats']); ?>" class="w-full px-2.5 py-1.5 bg-slate-950/80 border border-slate-800 rounded-lg text-xs font-mono text-center font-bold text-rose-300 focus:outline-none focus:border-rose-400">
                            </div>
                            <div>
                                <label class="block text-[11px] text-purple-400 font-bold mb-1">Matched Hunts</label>
                                <input type="text" name="matched_hunts" value="<?php echo htmlspecialchars($data['matched_hunts']); ?>" class="w-full px-2.5 py-1.5 bg-slate-950/80 border border-slate-800 rounded-lg text-xs font-mono text-center font-bold text-purple-300 focus:outline-none focus:border-purple-400">
                            </div>
                        </div>
                    </div>

                    <!-- SEKSI 2: KNOWN IOC -->
                    <div class="space-y-3 p-4 rounded-xl bg-slate-900/60 border border-slate-800">
                        <span class="block text-xs font-bold text-cyan-400 uppercase tracking-wider">2. Known IOC (Last Updated)</span>
                        
                        <div class="bg-slate-950/80 p-3 rounded-lg border border-slate-800">
                            <label class="block text-[11px] text-slate-300 mb-1 font-semibold">Update Terakhir IOC:</label>
                            <div class="flex items-center space-x-2 text-xs">
                                <span class="text-slate-400">Last updated</span>
                                <input type="text" name="ioc_days" placeholder="2" value="<?php echo htmlspecialchars($data['ioc_days']); ?>" class="w-12 text-center px-2 py-1 bg-slate-900 border border-slate-700 rounded font-bold font-mono text-cyan-400 focus:outline-none focus:border-cyan-500">
                                <span class="text-slate-400">days,</span>
                                <input type="text" name="ioc_hours" placeholder="10" value="<?php echo htmlspecialchars($data['ioc_hours']); ?>" class="w-12 text-center px-2 py-1 bg-slate-900 border border-slate-700 rounded font-bold font-mono text-cyan-400 focus:outline-none focus:border-cyan-500">
                                <span class="text-slate-400">hours ago</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-medium text-slate-400 mb-1">File Hashes</label>
                                <input type="text" name="file_hashes" placeholder="Contoh: 8.4m" value="<?php echo htmlspecialchars($data['file_hashes']); ?>" class="w-full px-3 py-1.5 bg-slate-950/80 border border-slate-800 rounded-lg text-xs font-mono text-slate-200 focus:outline-none focus:border-cyan-500">
                            </div>
                            <div>
                                <label class="block text-[11px] font-medium text-slate-400 mb-1">YARA Rules</label>
                                <input type="text" name="yara_rules" placeholder="Contoh: 3.5k" value="<?php echo htmlspecialchars($data['yara_rules']); ?>" class="w-full px-3 py-1.5 bg-slate-950/80 border border-slate-800 rounded-lg text-xs font-mono text-slate-200 focus:outline-none focus:border-cyan-500">
                            </div>
                        </div>
                    </div>

                    <!-- SEKSI 3: ANOMALY DETECTION & MONITORING -->
                    <div class="space-y-3 p-4 rounded-xl bg-slate-900/60 border border-slate-800">
                        <span class="block text-xs font-bold text-cyan-400 uppercase tracking-wider">3. Anomaly & Threat Monitoring Scan</span>
                        
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-medium text-slate-400 mb-1">Scanned Backup Objects</label>
                                <input type="text" name="scanned_backup" placeholder="Contoh: 1.7k" value="<?php echo htmlspecialchars($data['scanned_backup']); ?>" class="w-full px-3 py-1.5 bg-slate-950/80 border border-slate-800 rounded-lg text-xs font-mono text-slate-200 focus:outline-none focus:border-cyan-500">
                            </div>
                            <div>
                                <label class="block text-[11px] font-medium text-slate-400 mb-1">Total Anomalies Backup</label>
                                <input type="text" name="anomalies_backup" value="<?php echo htmlspecialchars($data['anomalies_backup']); ?>" class="w-full px-3 py-1.5 bg-slate-950/80 border border-slate-800 rounded-lg text-xs font-mono text-slate-200 focus:outline-none focus:border-cyan-500">
                            </div>
                            <div>
                                <label class="block text-[11px] font-medium text-slate-400 mb-1">Scanned Obj (File Hashes)</label>
                                <input type="text" name="scanned_hashes" placeholder="Contoh: 1.7k" value="<?php echo htmlspecialchars($data['scanned_hashes']); ?>" class="w-full px-3 py-1.5 bg-slate-950/80 border border-slate-800 rounded-lg text-xs font-mono text-slate-200 focus:outline-none focus:border-cyan-500">
                            </div>
                            <div>
                                <label class="block text-[11px] font-medium text-slate-400 mb-1">Scanned Obj (YARA Rules)</label>
                                <input type="text" name="scanned_yara" placeholder="Contoh: 1.7k" value="<?php echo htmlspecialchars($data['scanned_yara']); ?>" class="w-full px-3 py-1.5 bg-slate-950/80 border border-slate-800 rounded-lg text-xs font-mono text-slate-200 focus:outline-none focus:border-cyan-500">
                            </div>
                        </div>
                    </div>

                    <!-- SEKSI 4: THREAT HUNTING (TH) -->
                    <div class="space-y-3 p-4 rounded-xl bg-slate-900/60 border border-slate-800">
                        <span class="block text-xs font-bold text-cyan-400 uppercase tracking-wider">4. Threat Hunting Status (TH)</span>
                        
                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <label class="block text-[11px] font-medium text-slate-400 mb-1">TH In Progress</label>
                                <input type="text" name="hunt_in_progress" value="<?php echo htmlspecialchars($data['hunt_in_progress']); ?>" class="w-full px-2.5 py-1.5 bg-slate-950/80 border border-slate-800 rounded-lg text-xs font-mono text-center text-slate-300 focus:outline-none focus:border-cyan-500">
                            </div>
                            <div>
                                <label class="block text-[11px] font-medium text-slate-400 mb-1">TH Failed</label>
                                <input type="text" name="hunt_failed" value="<?php echo htmlspecialchars($data['hunt_failed']); ?>" class="w-full px-2.5 py-1.5 bg-slate-950/80 border border-slate-800 rounded-lg text-xs font-mono text-center text-slate-300 focus:outline-none focus:border-cyan-500">
                            </div>
                            <div>
                                <label class="block text-[11px] text-emerald-400 font-bold mb-1">TH Completed</label>
                                <input type="text" name="hunt_completed" placeholder="Contoh: 70" value="<?php echo htmlspecialchars($data['hunt_completed']); ?>" class="w-full px-2.5 py-1.5 bg-slate-950/80 border border-slate-800 rounded-lg text-xs font-mono text-center font-bold text-emerald-300 focus:outline-none focus:border-emerald-400">
                            </div>
                        </div>
                    </div>

                </div>

                <button type="submit" class="w-full py-3.5 bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 text-slate-950 font-extrabold text-xs uppercase tracking-wider rounded-xl transition-all shadow-lg shadow-cyan-500/20 active:scale-[0.99]">
                    Generate Analytics Report Text
                </button>
            </div>
        </form>

        <!-- OUTPUT TEXT REPORT FOR COPYING (RIGHT PANEL - 6 COLS) -->
        <div class="lg:col-span-6">
            <div class="glass-card p-6 rounded-2xl shadow-2xl relative overflow-hidden flex flex-col justify-between h-full">
                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-emerald-400 to-cyan-500"></div>

                <div>
                    <div class="flex justify-between items-center mb-4 border-b border-slate-800 pb-3">
                        <h3 class="text-xs font-bold text-emerald-400 uppercase tracking-wider flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                            Format Ringkasan Text Analytics
                        </h3>
                        <button onclick="copyAnalyticsReport(this)" class="text-xs bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-300 px-3.5 py-1.5 rounded-lg font-semibold transition shadow">
                            Copy Text
                        </button>
                    </div>

                    <pre id="analyticsReportText" class="p-4 bg-slate-950/90 rounded-xl text-slate-200 text-xs font-mono whitespace-pre-wrap leading-relaxed border border-slate-800 max-h-[560px] overflow-y-auto custom-scrollbar"><?php echo htmlspecialchars($data['tanggal']); ?>

Pukul <?php echo htmlspecialchars($data['waktu']); ?>


Data Threat Analytics
Threat Summary: 
* <?php echo htmlspecialchars($data['obj_anomaly_enabled']); ?> Object have anomaly detection enabled
* <?php echo htmlspecialchars($data['obj_threat_enabled']); ?> Object have threat monitoring enabled
* Anomalies : <?php echo htmlspecialchars($data['anomalies']); ?>

* Threats : <?php echo htmlspecialchars($data['threats']); ?>

* Matched Hunts : <?php echo htmlspecialchars($data['matched_hunts']); ?>


Known IOC (Last updated <?php echo htmlspecialchars($data['ioc_days']); ?> days, <?php echo htmlspecialchars($data['ioc_hours']); ?> hours ago) :
* <?php echo htmlspecialchars($data['file_hashes']); ?> File Hashes
* <?php echo htmlspecialchars($data['yara_rules']); ?> YARA Rules

Anomaly Detection : 
* Total Scanned Backup : <?php echo htmlspecialchars($data['scanned_backup']); ?> objects scanned
* Total Anomalies Backup : <?php echo htmlspecialchars($data['anomalies_backup']); ?>


Threat Monitoring : 
* Total Scanned object for file hashes : <?php echo htmlspecialchars($data['scanned_hashes']); ?>

* Total Scanned object for YARA rules : <?php echo htmlspecialchars($data['scanned_yara']); ?>

* Total Backup with Threat : <?php echo htmlspecialchars($data['backup_with_threat']); ?>


Threat Hunting :
* Total In progress Threat Hunting : <?php echo htmlspecialchars($data['hunt_in_progress']); ?>

* Total Failed Threat Hunting : <?php echo htmlspecialchars($data['hunt_failed']); ?>

* Total Completed Threat Hunting : <?php echo htmlspecialchars($data['hunt_completed']); ?></pre>
                </div>
                
                <p class="text-[11px] text-slate-500 mt-4 text-center">Setiap perubahan angka pada form akan langsung diperbarui saat di-submit.</p>
            </div>
        </div>

    </main>

</body>
</html>