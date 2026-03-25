<?php
include "config.php";

$raw_token = $_GET['token'] ?? '';
$decoded = base64_decode($raw_token);

$is_valid = false;
$trip = null;

if(strpos($decoded, '_voyager') !== false) {
    $trip_id = (int)str_replace('_voyager', '', $decoded);
    
    // Check if trip actually exists and is approved
    $res = mysqli_query($conn, 
        "SELECT t.*, u.full_name, u.email, tp.package_name 
         FROM trip t 
         JOIN users u ON t.user_id = u.user_id 
         LEFT JOIN travel_package tp ON t.package_id = tp.package_id
         WHERE t.trip_id='$trip_id' AND t.trip_status='Approved'");
         
    if(mysqli_num_rows($res) > 0){
        $trip = mysqli_fetch_assoc($res);
        $is_valid = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ticket Verification</title>
  <link rel="stylesheet" href="style.css">
  <style>
    body { background: #0f0f16; display:flex; justify-content:center; align-items:center; min-height:100vh; margin:0; text-align:center; }
    .card { background:linear-gradient(145deg, #1e1e2f, #151522); padding:3rem; border-radius:12px; border:1px solid rgba(255,255,255,0.05); max-width:500px; width:100%; box-shadow:0 15px 35px rgba(0,0,0,0.5); }
    .icon { font-size:4rem; margin-bottom:1rem; }
  </style>
</head>
<body>

<div class="card" style="animation: fadeUp 0.5s ease both;">
  <?php if($is_valid): ?>
    <div class="icon">✅</div>
    <h1 style="font-family:'Playfair Display',serif; font-size:2rem; color:var(--gold); margin-bottom:0.5rem;">Valid Ticket</h1>
    <p style="color:var(--white); font-size:1.1rem; margin-bottom:0.5rem;"><strong>Passenger:</strong> <?php echo htmlspecialchars($trip['full_name']); ?></p>
    <p style="color:rgba(255,255,255,0.7); margin-bottom:0.5rem;"><strong>Group Size:</strong> <?php echo (int)$trip['passengers']; ?> Person(s)</p>
    <p style="color:rgba(255,255,255,0.7); margin-bottom:0.5rem;"><strong>Destination:</strong> <?php echo htmlspecialchars($trip['package_name'] ?? $trip['trip_name']); ?></p>
    <p style="color:rgba(255,255,255,0.7); margin-bottom:1.5rem;"><strong>Dates:</strong> <?php echo date('M d', strtotime($trip['start_date'])) . ' — ' . date('M d, Y', strtotime($trip['end_date'])); ?></p>
    
    <div style="background:rgba(100,200,120,0.1); color:#64c878; padding:1rem; border-radius:8px; border:1px solid rgba(100,200,120,0.2);">
      Boarding Pass is active and approved by Admin.
    </div>
  <?php else: ?>
    <div class="icon">❌</div>
    <h1 style="font-family:'Playfair Display',serif; font-size:2rem; color:#e07070; margin-bottom:0.5rem;">Invalid Ticket</h1>
    <p style="color:var(--muted); margin-bottom:1.5rem;">This boarding pass token is either invalid, tampered with, or the trip is no longer approved.</p>
    
    <a href="index.php" class="btn btn-outline">Return Home</a>
  <?php endif; ?>
</div>

</body>
</html>
