<?php
/**
 * Voyager — Database & Session Diagnostic Page
 * Open this in browser: http://localhost/travel_project/db_test.php
 */
include "config.php";

$checks = [];

// 1. MySQL Connection
$checks[] = [
    'name' => 'MySQL Connection',
    'pass' => ($conn) ? true : false,
    'detail' => $conn ? 'Connected to localhost:3307' : mysqli_connect_error()
];

// 2. Database exists
$db_check = mysqli_query($conn, "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME='travel_db'");
$checks[] = [
    'name' => 'Database "travel_db"',
    'pass' => mysqli_num_rows($db_check) > 0,
    'detail' => mysqli_num_rows($db_check) > 0 ? 'Database exists' : 'Database NOT found — run setup.sql'
];

// 3. Check all required tables
$required_tables = [
    'users', 'admin', 'destination', 'travel_package', 'trip',
    'expense', 'review', 'package_review', 'hotel',
    'transport_options', 'activities', 'custom_booking'
];

$table_results = [];
foreach($required_tables as $table){
    $res = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM `$table`");
    if($res){
        $row = mysqli_fetch_assoc($res);
        $table_results[] = ['name' => $table, 'pass' => true, 'rows' => $row['cnt']];
    } else {
        $table_results[] = ['name' => $table, 'pass' => false, 'rows' => 0];
    }
}

$all_tables_ok = !in_array(false, array_column($table_results, 'pass'));
$checks[] = [
    'name' => 'All Required Tables (' . count($required_tables) . ')',
    'pass' => $all_tables_ok,
    'detail' => $all_tables_ok ? 'All tables found' : 'Some tables missing — run setup.sql'
];

// 4. Session test
$_SESSION['__diag_test'] = 'voyager_ok';
$session_ok = ($_SESSION['__diag_test'] === 'voyager_ok');
unset($_SESSION['__diag_test']);
$checks[] = [
    'name' => 'PHP Sessions (In-Memory)',
    'pass' => $session_ok,
    'detail' => $session_ok
        ? 'Session ID: ' . session_id() . ' | Save path: ' . (session_save_path() ?: 'default')
        : 'Session read/write FAILED'
];

// 5. Password hashing support
$hash_ok = function_exists('password_hash') && function_exists('password_verify');
$checks[] = [
    'name' => 'Password Hashing (bcrypt)',
    'pass' => $hash_ok,
    'detail' => $hash_ok ? 'password_hash() and password_verify() available' : 'PHP version too old — needs 5.5+'
];

// Currently logged in?
$logged_in = isset($_SESSION['user_id']) ? 'User #' . $_SESSION['user_id'] . ' (' . ($_SESSION['user_name'] ?? '') . ')'
           : (isset($_SESSION['admin_id']) ? 'Admin #' . $_SESSION['admin_id'] . ' (' . ($_SESSION['admin_name'] ?? '') . ')'
           : 'Not logged in');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Diagnostics — Voyager</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include "nav.php"; ?>

<div class="section" style="max-width:900px; margin:0 auto;">
  <div class="section-title">🔧 System Diagnostics</div>
  <p class="section-subtitle">Database connection, tables, and session verification</p>

  <!-- Overall Checks -->
  <div class="stat-grid" style="margin-bottom:2rem;">
    <?php foreach($checks as $c): ?>
    <div class="stat-card" style="border-color: <?php echo $c['pass'] ? 'rgba(100,200,120,0.4)' : 'rgba(224,112,112,0.4)'; ?>;">
      <div class="stat-label"><?php echo $c['pass'] ? '✅' : '❌'; ?> <?php echo $c['name']; ?></div>
      <div style="color:var(--muted); font-size:0.82rem; margin-top:0.5rem;"><?php echo $c['detail']; ?></div>
    </div>
    <?php endforeach; ?>
    <div class="stat-card">
      <div class="stat-label">👤 Current Session</div>
      <div style="color:var(--gold); font-size:0.9rem; margin-top:0.5rem;"><?php echo $logged_in; ?></div>
    </div>
  </div>

  <!-- Table Details -->
  <div class="card">
    <h3 style="font-size:1.2rem; margin-bottom:1.5rem;">📋 Table Status</h3>
    <div style="overflow-x:auto;">
      <table class="data-table">
        <thead>
          <tr><th>Table</th><th>Status</th><th>Row Count</th></tr>
        </thead>
        <tbody>
          <?php foreach($table_results as $t): ?>
          <tr>
            <td><code style="color:var(--gold);"><?php echo $t['name']; ?></code></td>
            <td><?php echo $t['pass'] ? '<span style="color:#64c878;">✅ OK</span>' : '<span style="color:#e07070;">❌ Missing</span>'; ?></td>
            <td><?php echo $t['pass'] ? $t['rows'] . ' rows' : '—'; ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- PHP Info Summary -->
  <div class="card" style="margin-top:1.5rem;">
    <h3 style="font-size:1.2rem; margin-bottom:1rem;">⚙️ Environment</h3>
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem; font-size:0.88rem;">
      <div style="color:var(--muted);">PHP Version</div><div><?php echo phpversion(); ?></div>
      <div style="color:var(--muted);">MySQL Server</div><div><?php echo mysqli_get_server_info($conn); ?></div>
      <div style="color:var(--muted);">Session Handler</div><div><?php echo ini_get('session.save_handler'); ?></div>
      <div style="color:var(--muted);">Max Execution Time</div><div><?php echo ini_get('max_execution_time'); ?>s</div>
    </div>
  </div>

  <div style="text-align:center; margin-top:2rem;">
    <a href="index.php" class="btn btn-primary">← Back to Site</a>
  </div>
</div>

</body>
</html>
