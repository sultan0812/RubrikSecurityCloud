<?php
// Set zona waktu Indonesia
date_default_timezone_set('Asia/Jakarta');

$showResult = false;
$data = [
    'tanggal' => date('l, d F Y'),
    'waktu' => date('H:i') . ' WIB',
    'high_sens_objects' => '33',
    'high_sens_past7' => '0',
    'total_files_hits' => '320.4m',
    'total_files_past7' => '-19.8k',
    'total_hits' => '21.1b',
    'total_hits_past7' => '-9.8m',
    'object_coverage' => '3.41%',
    
    // Cluster GTI
    'gti_hits' => '20.9b',
    'gti_high_risk' => '251m',
    'gti_med_risk' => '21b',
    'gti_analyzer' => '12',
    'gti_objects' => '5.9k',
    
    // Cluster TBN
    'tbn_hits' => '1.8b',
    'tbn_high_risk' => '31k',
    'tbn_med_risk' => '2b',
    'tbn_analyzer' => '12',
    'tbn_objects' => '1.9k'
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
    <title>Security Posture Report - RSC Enterprise</title>
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
        function copyPostureReport(btnElement) {
            const reportContent = document.getElementById("postureReportText").innerText;
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
    <div class="fixed top-1/2 -right-40 w-96 h-96 bg-blue-600/10 rounded-full blur-3xl pointer-events-none"></div>

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
                <p class="text-xs text-slate-400">Security Posture Console</p>
            </div>
        </div>
        
        <nav class="flex flex-wrap gap-1 bg-slate-900/80 p-1.5 rounded-xl border border-slate-800 text-xs font-semibold">
            <a href="index.php" class="px-4 py-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800/60 transition">Threat Hunt Form</a>
            <a href="analytics.php" class="px-4 py-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800/60 transition">Threat Analytics</a>
            <a href="posture.php" class="px-4 py-2 rounded-lg bg-gradient-to-r from-cyan-500 to-blue-600 text-slate-950 font-bold shadow-md shadow-cyan-500/20 transition-all">Security Posture</a>
            <a href="activity.php" class="px-4 py-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800/60 transition">TH Activity Report</a>
        </nav>
    </header>

    <main class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-8 relative z-10">
        
        <!-- FORM INPUT SECURITY POSTURE (LEFT PANEL - 6 COLS) -->
        <form action="posture.php" method="POST" class="lg:col-span-6 space-y-6">
            <div class="glass-card p-6 rounded-2xl shadow-2xl relative overflow-hidden space-y-5">
                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-cyan-500 via-blue-500 to-indigo-500"></div>
                
                <div class="border-b border-slate-800 pb-3 flex justify-between items-center">
                    <h2 class="text-sm font-bold text-cyan-400 uppercase tracking-wider flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-cyan-400"></span>
                        Input Security Posture Data
                    </h2>
                    <span class="text-[10px] bg-cyan-500/10 text-cyan-300 border border-cyan-500/20 px-2.5 py-1 rounded-full font-bold">Posture Report</span>
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
                    
                    <!-- SEKSI 1: DATA SECURITY POSTURE DASHBOARD -->
                    <div class="space-y-3 p-4 rounded-xl bg-slate-900/60 border border-slate-800">
                        <span class="block text-xs font-bold text-cyan-400 uppercase tracking-wider">1. Data Security Posture Dashboard</span>
                        
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-medium text-slate-400 mb-1">High-sensitivity objects</label>
                                <input type="text" name="high_sens_objects" value="<?php echo htmlspecialchars($data['high_sens_objects']); ?>" class="w-full px-3 py-1.5 bg-slate-950/80 border border-slate-800 rounded-lg text-xs font-mono text-cyan-300 focus:outline-none focus:border-cyan-500">
                            </div>
                            <div>
                                <label class="block text-[11px] font-medium text-slate-400 mb-1">High Sens (Past 7 days)</label>
                                <input type="text" name="high_sens_past7" value="<?php echo htmlspecialchars($data['high_sens_past7']); ?>" class="w-full px-3 py-1.5 bg-slate-950/80 border border-slate-800 rounded-lg text-xs font-mono text-cyan-300 focus:outline-none focus:border-cyan-500">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-medium text-slate-400 mb-1">Total files with hits</label>
                                <input type="text" name="total_files_hits" value="<?php echo htmlspecialchars($data['total_files_hits']); ?>" class="w-full px-3 py-1.5 bg-slate-950/80 border border-slate-800 rounded-lg text-xs font-mono text-slate-200 focus:outline-none focus:border-cyan-500">
                            </div>
                            <div>
                                <label class="block text-[11px] font-medium text-slate-400 mb-1">Files Hits (Past 7 days)</label>
                                <input type="text" name="total_files_past7" value="<?php echo htmlspecialchars($data['total_files_past7']); ?>" class="w-full px-3 py-1.5 bg-slate-950/80 border border-slate-800 rounded-lg text-xs font-mono text-slate-200 focus:outline-none focus:border-cyan-500">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-medium text-slate-400 mb-1">Total hits</label>
                                <input type="text" name="total_hits" value="<?php echo htmlspecialchars($data['total_hits']); ?>" class="w-full px-3 py-1.5 bg-slate-950/80 border border-slate-800 rounded-lg text-xs font-mono text-slate-200 focus:outline-none focus:border-cyan-500">
                            </div>
                            <div>
                                <label class="block text-[11px] font-medium text-slate-400 mb-1">Total Hits (Past 7 days)</label>
                                <input type="text" name="total_hits_past7" value="<?php echo htmlspecialchars($data['total_hits_past7']); ?>" class="w-full px-3 py-1.5 bg-slate-950/80 border border-slate-800 rounded-lg text-xs font-mono text-slate-200 focus:outline-none focus:border-cyan-500">
                            </div>
                        </div>

                        <div>
                            <label class="block text-[11px] font-medium text-slate-400 mb-1">Object coverage</label>
                            <input type="text" name="object_coverage" value="<?php echo htmlspecialchars($data['object_coverage']); ?>" class="w-full px-3 py-1.5 bg-slate-950/80 border border-slate-800 rounded-lg text-xs font-mono text-cyan-300 focus:outline-none focus:border-cyan-500">
                        </div>
                    </div>

                    <!-- SEKSI 2: SENSITIVE DATA DISTRIBUTION -->
                    <div class="space-y-3 p-4 rounded-xl bg-slate-900/60 border border-slate-800">
                        <span class="block text-xs font-bold text-cyan-400 uppercase tracking-wider">2. Sensitive Data Distribution</span>
                        
                        <!-- Policies Cluster GTI -->
                        <div class="p-3 bg-slate-950/80 rounded-lg border border-cyan-500/20 space-y-2">
                            <span class="block text-[11px] font-bold text-cyan-400">Policies Cluster GTI</span>
                            <div class="grid grid-cols-3 gap-2">
                                <div>
                                    <label class="block text-[10px] text-slate-400">Total Hits</label>
                                    <input type="text" name="gti_hits" value="<?php echo htmlspecialchars($data['gti_hits']); ?>" class="w-full px-2.5 py-1 bg-slate-900 border border-slate-800 rounded text-xs font-mono text-slate-200 focus:outline-none focus:border-cyan-500">
                                </div>
                                <div>
                                    <label class="block text-[10px] text-rose-400">High Risk Hits</label>
                                    <input type="text" name="gti_high_risk" value="<?php echo htmlspecialchars($data['gti_high_risk']); ?>" class="w-full px-2.5 py-1 bg-slate-900 border border-slate-800 rounded text-xs font-mono text-rose-300 focus:outline-none focus:border-rose-400">
                                </div>
                                <div>
                                    <label class="block text-[10px] text-amber-400">Med Risk Hits</label>
                                    <input type="text" name="gti_med_risk" value="<?php echo htmlspecialchars($data['gti_med_risk']); ?>" class="w-full px-2.5 py-1 bg-slate-900 border border-slate-800 rounded text-xs font-mono text-amber-300 focus:outline-none focus:border-amber-400">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-[10px] text-slate-400">Analyzer</label>
                                    <input type="text" name="gti_analyzer" value="<?php echo htmlspecialchars($data['gti_analyzer']); ?>" class="w-full px-2.5 py-1 bg-slate-900 border border-slate-800 rounded text-xs font-mono text-slate-200 focus:outline-none focus:border-cyan-500">
                                </div>
                                <div>
                                    <label class="block text-[10px] text-slate-400">Total Objects</label>
                                    <input type="text" name="gti_objects" value="<?php echo htmlspecialchars($data['gti_objects']); ?>" class="w-full px-2.5 py-1 bg-slate-900 border border-slate-800 rounded text-xs font-mono text-slate-200 focus:outline-none focus:border-cyan-500">
                                </div>
                            </div>
                        </div>

                        <!-- Policies Cluster TBN -->
                        <div class="p-3 bg-slate-950/80 rounded-lg border border-purple-500/20 space-y-2">
                            <span class="block text-[11px] font-bold text-purple-400">Policies Cluster TBN</span>
                            <div class="grid grid-cols-3 gap-2">
                                <div>
                                    <label class="block text-[10px] text-slate-400">Total Hits</label>
                                    <input type="text" name="tbn_hits" value="<?php echo htmlspecialchars($data['tbn_hits']); ?>" class="w-full px-2.5 py-1 bg-slate-900 border border-slate-800 rounded text-xs font-mono text-slate-200 focus:outline-none focus:border-purple-500">
                                </div>
                                <div>
                                    <label class="block text-[10px] text-rose-400">High Risk Hits</label>
                                    <input type="text" name="tbn_high_risk" value="<?php echo htmlspecialchars($data['tbn_high_risk']); ?>" class="w-full px-2.5 py-1 bg-slate-900 border border-slate-800 rounded text-xs font-mono text-rose-300 focus:outline-none focus:border-rose-400">
                                </div>
                                <div>
                                    <label class="block text-[10px] text-amber-400">Med Risk Hits</label>
                                    <input type="text" name="tbn_med_risk" value="<?php echo htmlspecialchars($data['tbn_med_risk']); ?>" class="w-full px-2.5 py-1 bg-slate-900 border border-slate-800 rounded text-xs font-mono text-amber-300 focus:outline-none focus:border-amber-400">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-[10px] text-slate-400">Analyzer</label>
                                    <input type="text" name="tbn_analyzer" value="<?php echo htmlspecialchars($data['tbn_analyzer']); ?>" class="w-full px-2.5 py-1 bg-slate-900 border border-slate-800 rounded text-xs font-mono text-slate-200 focus:outline-none focus:border-purple-500">
                                </div>
                                <div>
                                    <label class="block text-[10px] text-slate-400">Total Objects</label>
                                    <input type="text" name="tbn_objects" value="<?php echo htmlspecialchars($data['tbn_objects']); ?>" class="w-full px-2.5 py-1 bg-slate-900 border border-slate-800 rounded text-xs font-mono text-slate-200 focus:outline-none focus:border-purple-500">
                                </div>
                            </div>
                        </div>

                    </div>

                </div>

                <button type="submit" class="w-full py-3.5 bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 text-slate-950 font-extrabold text-xs uppercase tracking-wider rounded-xl transition-all shadow-lg shadow-cyan-500/20 active:scale-[0.99]">
                    Generate Posture Report Text
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
                            Format Ringkasan Text Posture
                        </h3>
                        <button onclick="copyPostureReport(this)" class="text-xs bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-300 px-3.5 py-1.5 rounded-lg font-semibold transition shadow">
                            Copy Text
                        </button>
                    </div>

                    <pre id="postureReportText" class="p-4 bg-slate-950/90 rounded-xl text-slate-200 text-xs font-mono whitespace-pre-wrap leading-relaxed border border-slate-800 max-h-[560px] overflow-y-auto custom-scrollbar"><?php echo htmlspecialchars($data['tanggal']); ?>

Pukul <?php echo htmlspecialchars($data['waktu']); ?>


Data Security Posture Dashboard :
* High-sensitivity objects : <?php echo htmlspecialchars($data['high_sens_objects']); ?> (Past 7 days: <?php echo htmlspecialchars($data['high_sens_past7']); ?>)
* Total files with hits : <?php echo htmlspecialchars($data['total_files_hits']); ?> (Past 7 days: <?php echo htmlspecialchars($data['total_files_past7']); ?>)
* Total hits : <?php echo htmlspecialchars($data['total_hits']); ?> (Past 7 days: <?php echo htmlspecialchars($data['total_hits_past7']); ?>)
* Object coverage : <?php echo htmlspecialchars($data['object_coverage']); ?>


Sensitive Data Distribution :
* Policies Cluster GTI : <?php echo htmlspecialchars($data['gti_hits']); ?> hits (<?php echo htmlspecialchars($data['gti_high_risk']); ?> high risk and <?php echo htmlspecialchars($data['gti_med_risk']); ?> medium risk) with <?php echo htmlspecialchars($data['gti_analyzer']); ?> Analyzer on <?php echo htmlspecialchars($data['gti_objects']); ?> Objects
* Policies Cluster TBN : <?php echo htmlspecialchars($data['tbn_hits']); ?> hits (<?php echo htmlspecialchars($data['tbn_high_risk']); ?> high risk and <?php echo htmlspecialchars($data['tbn_med_risk']); ?> medium risk) with <?php echo htmlspecialchars($data['tbn_analyzer']); ?> Analyzer on <?php echo htmlspecialchars($data['tbn_objects']); ?> Objects</pre>
                </div>
                
                <p class="text-[11px] text-slate-500 mt-4 text-center">Ubah data pada form sebelah kiri untuk memperbarui tampilan text laporan ini.</p>
            </div>
        </div>

    </main>

</body>
</html>