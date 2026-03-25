<?php
include "config.php";

if(!isset($_SESSION['user_id'])){ header("Location: login.php"); exit(); }
$user_id = $_SESSION['user_id'];
$trip_id = (int)($_GET['trip_id'] ?? 0);

// Get trip details
$trip = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT t.*, tp.package_name, u.full_name 
     FROM trip t 
     LEFT JOIN travel_package tp ON t.package_id = tp.package_id
     JOIN users u ON t.user_id = u.user_id
     WHERE t.trip_id='$trip_id' AND t.user_id='$user_id' AND t.trip_status='Approved'"));

if(!$trip){
    echo "<h2 style='color:#e07070; text-align:center; padding:5rem; font-family:sans-serif;'>⚠ Only Approved trips have E-Tickets.</h2>";
    exit();
}

$token = urlencode(base64_encode($trip_id . '_voyager'));
$verify_url = "http://localhost:8080/travel_project/verify_ticket.php?token=" . $token;
$qr_api = "https://api.qrserver.com/v1/create-qr-code/?size=180x180&format=svg&color=c9a84c&bgcolor=1a1a2e&data=" . urlencode($verify_url);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>E-Ticket — <?php echo htmlspecialchars($trip['trip_name']); ?></title>
  <link rel="stylesheet" href="style.css">
  <style>
    body { background: #0f0f16; display:flex; justify-content:center; align-items:center; min-height:100vh; margin:0; }
    .ticket { width:100%; max-width:700px; background:linear-gradient(145deg, #1e1e2f, #151522); border-radius:12px; overflow:hidden; position:relative; box-shadow:0 15px 35px rgba(0,0,0,0.5); display:flex; flex-direction:column; border:1px solid rgba(255,255,255,0.05); }
    .ticket-header { background:linear-gradient(90deg, var(--gold), var(--gold-light)); padding:1.5rem 2rem; color:var(--deep); display:flex; justify-content:space-between; align-items:center; }
    .ticket-body { padding:2.5rem; display:flex; gap:2rem; }
    .ticket-info { flex:1; }
    .info-label { font-size:0.7rem; color:var(--muted); text-transform:uppercase; letter-spacing:2px; margin-bottom:0.2rem; }
    .info-val { font-size:1.1rem; color:var(--white); font-weight:700; margin-bottom:1.5rem; font-family:'Playfair Display',serif; }
    .barcode-section { background:#1a1a2e; padding:1.5rem; border-radius:8px; text-align:center; display:flex; flex-direction:column; align-items:center; justify-content:center; border:1px solid rgba(201,168,76,0.3); }
    .cutout { position:absolute; right: -15px; top:120px; width:30px; height:30px; background:#0f0f16; border-radius:50%; }
    .cutout.left { left:-15px; }
  </style>
</head>
<body>

<div class="ticket" style="animation: fadeUp 0.6s ease both;">
  <div class="cutout left"></div>
  <div class="cutout"></div>
  
  <div class="ticket-header">
    <div style="font-family:'Playfair Display',serif; font-size:1.5rem; font-weight:700;">✦ VOYAGER</div>
    <div style="font-size:0.85rem; font-weight:700; letter-spacing:1px; background:var(--deep); color:var(--gold); padding:0.3rem 0.8rem; border-radius:50px;">
      BOARDING PASS
    </div>
  </div>

  <div class="ticket-body">
    <div class="ticket-info">
      <div style="display:flex; justify-content:space-between;">
        <div>
          <div class="info-label">Passenger</div>
          <div class="info-val" style="font-size:1.5rem; color:var(--gold);"><?php echo htmlspecialchars($trip['full_name']); ?></div>
        </div>
        <div style="text-align:right;">
          <div class="info-label">Booking ID</div>
          <div class="info-val">VYG-<?php echo str_pad($trip['trip_id'], 6, '0', STR_PAD_LEFT); ?></div>
        </div>
      </div>

      <div style="margin-top:1rem; display:flex; gap:2rem;">
        <div>
          <div class="info-label">Passengers</div>
          <div class="info-val" style="color:var(--gold);"><?php echo (int)$trip['passengers']; ?> Person(s)</div>
        </div>
      </div>

      <div style="margin-bottom:2rem; padding-bottom:1rem; border-bottom:1px dashed rgba(255,255,255,0.1);"></div>

      <div class="info-label">Destination Package</div>
      <div class="info-val" style="font-size:1.4rem;"><?php echo htmlspecialchars($trip['package_name'] ?? $trip['trip_name']); ?></div>

      <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-top:2rem;">
        <div>
          <div class="info-label">Departure</div>
          <div class="info-val" style="font-family:sans-serif;"><?php echo date('M d, Y', strtotime($trip['start_date'])); ?></div>
        </div>
        <div>
          <div class="info-label">Return</div>
          <div class="info-val" style="font-family:sans-serif;"><?php echo date('M d, Y', strtotime($trip['end_date'])); ?></div>
        </div>
      </div>
    </div>

    <!-- QR Code -->
    <div class="barcode-section">
      <img src="<?php echo $qr_api; ?>" alt="Verify QR Code" style="width:140px; height:140px; margin-bottom:1rem; padding:10px; background:#1a1a2e; border-radius:4px;">
      <div class="info-label">Scan to Verify</div>
      <div style="font-size:0.75rem; color:var(--muted); font-family:monospace;">VYG-<?php echo str_pad($trip['trip_id'],6,'0',STR_PAD_LEFT); ?></div>
    </div>
  </div>

  <div style="background:rgba(255,255,255,0.03); text-align:center; padding:1rem; border-top:1px solid rgba(255,255,255,0.05);">
    <button onclick="window.print()" class="btn btn-primary" style="font-size:0.8rem; padding:0.5rem 1rem;">🖨 Print Ticket</button>
    <a href="dashboard.php" class="btn btn-outline" style="font-size:0.8rem; padding:0.5rem 1rem;">← Back</a>
  </div>
</div>

</body>
</html>
