<?php
/**
 * Debug View - View registration debug logs
 * Access this file in browser: http://localhost/Hotel/debug_view.php
 * DELETE THIS FILE IN PRODUCTION!
 */

$log_file = __DIR__ . '/debug_registration.log';

if (file_exists($log_file)) {
    $logs = file_get_contents($log_file);
    $logs = htmlspecialchars($logs);
    $lines = explode("\n", $logs);
    $recent_logs = array_slice($lines, -100); // Show last 100 lines
    $recent_logs = implode("\n", $recent_logs);
} else {
    $recent_logs = "No log file found. Registration hasn't been attempted yet.";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Registration Debug Logs</title>
    <style>
        body {
            font-family: monospace;
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
        }
        pre {
            background: #252526;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .header {
            color: #4ec9b0;
            margin-bottom: 20px;
        }
        .refresh {
            background: #007acc;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-bottom: 20px;
        }
        .clear {
            background: #d32f2f;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <h1 class="header">Registration Debug Logs</h1>
    <button class="refresh" onclick="location.reload()">Refresh</button>
    <button class="clear" onclick="if(confirm('Clear logs?')) { window.location.href='?clear=1'; }">Clear Logs</button>
    
    <?php
    if (isset($_GET['clear']) && $_GET['clear'] == '1') {
        if (file_exists($log_file)) {
            file_put_contents($log_file, '');
            echo "<p style='color: #4ec9b0;'>Logs cleared!</p>";
            echo "<script>setTimeout(function(){ window.location.href='debug_view.php'; }, 1000);</script>";
        }
    }
    ?>
    
    <pre><?php echo $recent_logs; ?></pre>
    
    <p style="color: #858585; font-size: 12px; margin-top: 20px;">
        Last updated: <?php echo date('Y-m-d H:i:s'); ?><br>
        Log file: <?php echo $log_file; ?><br>
        File size: <?php echo file_exists($log_file) ? filesize($log_file) : 0; ?> bytes
    </p>
</body>
</html>

