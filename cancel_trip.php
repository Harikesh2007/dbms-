<?php
include "config.php";

if(!isset($_SESSION['user_id'])){ header("Location: login.php"); exit(); }

$trip_id = (int)($_GET['trip_id'] ?? 0);
$user_id = $_SESSION['user_id'];

// Verify trip belongs to this user
$trip = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM trip WHERE trip_id='$trip_id' AND user_id='$user_id'"));

if(!$trip){ header("Location: dashboard.php"); exit(); }

// Already cancelled
if($trip['trip_status'] === 'Cancelled'){
    header("Location: dashboard.php"); exit();
}

if(isset($_POST['confirm_cancel'])){
    mysqli_query($conn,
    "UPDATE trip SET trip_status = 'Cancelled' WHERE trip_id='$trip_id' AND user_id='$user_id'");
    header("Location: dashboard.php?cancelled=1");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cancel Trip — Voyager</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<nav>
  <a href="index.php" class="nav-brand">✦ Voyager</a>
  <div class="nav-links">
    <a href="dashboard.php">← Back to Dashboard</a>
  </div>
</nav>

<div class="form-wrapper" style="text-align:center;">
  <div style="font-size:3rem; margin-bottom:1rem;">⚠️</div>
  <h2>Cancel Trip?</h2>
  <p class="subtitle">This will mark your trip as cancelled</p>

  <!-- Trip Summary -->
  <div class="card" style="text-align:left; margin:1.5rem 0;">
    <div style="font-size:0.8rem; color:var(--muted); text-transform:uppercase; letter-spacing:1px; margin-bottom:0.3rem;">Trip</div>
    <div style="font-size:1.1rem; font-weight:700; margin-bottom:0.75rem;"><?php echo htmlspecialchars($trip['trip_name']); ?></div>
    <div style="color:var(--muted); font-size:0.85rem; margin-bottom:0.4rem;">📅 <?php echo $trip['start_date']; ?> → <?php echo $trip['end_date']; ?></div>
    <div style="color:var(--muted); font-size:0.85rem;">💰 Budget: ₹<?php echo number_format($trip['total_budget']); ?></div>
  </div>

  <p style="color:var(--muted); font-size:0.9rem; margin-bottom:2rem;">
    Your expenses will still be saved. You can re-edit the trip status back to <strong>Planned</strong> anytime from the edit page.
  </p>

  <form method="POST" style="display:flex; gap:1rem; justify-content:center; flex-wrap:wrap;">
    <button type="submit" name="confirm_cancel" class="btn btn-danger" style="padding:0.85rem 2rem; font-size:0.9rem; border-radius:4px; cursor:pointer;">
      Yes, Cancel Trip
    </button>
    <a href="dashboard.php" class="btn btn-outline">No, Go Back</a>
  </form>
</div>

</body>
</html>