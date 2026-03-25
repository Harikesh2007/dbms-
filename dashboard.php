<?php
include "config.php";

if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'user'){
    header("Location: login.php"); exit();
}
$user_id = $_SESSION['user_id'];
$user    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE user_id='$user_id'"));
$result  = mysqli_query($conn, "SELECT * FROM trip WHERE user_id='$user_id' ORDER BY trip_id DESC");
$trips   = mysqli_fetch_all($result, MYSQLI_ASSOC);
$total_trips   = count($trips);
$total_budget  = array_sum(array_column($trips, 'total_budget'));
$total_expense = array_sum(array_column($trips, 'total_expense'));
$reward_points = $user['reward_points'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard — Voyager</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include "nav.php"; ?>

<div class="section">
  <div class="section-title">My Dashboard</div>
  <p class="section-subtitle">Welcome back, <strong style="color:var(--gold);"><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong></p>

  <!-- Stats -->
  <div class="stat-grid">
    <div class="stat-card">
      <div class="stat-label">Total Trips</div>
      <div class="stat-value"><?php echo $total_trips; ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Total Budget</div>
      <div class="stat-value">₹<?php echo number_format($total_budget); ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Total Spent</div>
      <div class="stat-value">₹<?php echo number_format($total_expense); ?></div>
    </div>
    <div class="stat-card" style="border-color:rgba(201,168,76,0.5);">
      <div class="stat-label">🏆 Reward Points</div>
      <div class="stat-value" style="color:var(--gold-light);"><?php echo $reward_points; ?></div>
      <?php if($reward_points >= 100): ?>
      <div style="color:#64c878; font-size:0.75rem; margin-top:0.3rem;">Redeemable on Custom Trip!</div>
      <?php endif; ?>
    </div>
  </div>

  <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; margin-bottom:1rem;">
    <div>
      <div class="section-title" style="font-size:1.4rem; margin-bottom:0.2rem;">My Trips</div>
      <p style="color:var(--muted); font-size:0.9rem;">Bookings pending admin approval are marked in gold</p>
    </div>
    <a href="custom_trip.php" class="btn btn-outline" style="font-size:0.85rem;">🛠 Build Custom Trip</a>
  </div>

  <?php if(isset($_GET['cancelled'])): ?>
  <div class="alert alert-error" style="margin-bottom:1.5rem;">✓ Trip has been cancelled.</div>
  <?php endif; ?>

  <?php if($total_trips > 0): ?>
  <div class="grid">
    <?php foreach($trips as $row):
      $status = strtolower($row['trip_status']);
      $badge_class = match($status){
        'completed' => 'badge-completed',
        'cancelled' => 'badge-cancelled',
        'approved'  => 'badge-approved',
        default     => 'badge-planned'
      };
      $spent_pct   = $row['total_budget'] > 0 ? min(100, ($row['total_expense']/$row['total_budget'])*100) : 0;
      $is_cancelled = $status === 'cancelled';
    ?>
    <div class="card" style="<?php echo $is_cancelled ? 'opacity:0.55;' : ''; ?>">
      <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1rem;">
        <h3 style="font-size:1.1rem;"><?php echo htmlspecialchars($row['trip_name']); ?></h3>
        <span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($row['trip_status']); ?></span>
      </div>
      <div style="color:var(--muted); font-size:0.85rem; margin-bottom:1rem;">
        📅 <?php echo $row['start_date']; ?> → <?php echo $row['end_date']; ?>
      </div>
      <div style="margin-bottom:0.5rem; font-size:0.85rem; display:flex; justify-content:space-between;">
        <span style="color:var(--muted);">Budget</span>
        <span>₹<?php echo number_format($row['total_budget']); ?></span>
      </div>
      <div style="margin-bottom:1rem; font-size:0.85rem; display:flex; justify-content:space-between;">
        <span style="color:var(--muted);">Passengers</span>
        <span>👥 <?php echo (int)($row['passengers'] ?? 1); ?></span>
      </div>
      <div style="background:rgba(255,255,255,0.08); border-radius:50px; height:6px; margin-bottom:0.5rem; overflow:hidden;">
        <div style="height:100%; width:<?php echo $spent_pct; ?>%; background:linear-gradient(90deg,var(--gold),var(--gold-light)); border-radius:50px;"></div>
      </div>
      <div style="text-align:right; font-size:0.8rem; color:var(--muted); margin-bottom:1.5rem;">
        ₹<?php echo number_format($row['total_expense']); ?> spent (<?php echo round($spent_pct); ?>%)
      </div>
      <?php if(!$is_cancelled): ?>
      <div style="display:flex; gap:0.6rem; flex-wrap:wrap;">
        <?php if($status === 'approved'): ?>
        <a href="ticket.php?trip_id=<?php echo $row['trip_id']; ?>" class="btn btn-primary" style="font-size:0.73rem; padding:0.45rem 0.9rem; background:linear-gradient(90deg,var(--gold),var(--gold-light)); border:none; color:var(--deep);">🎫 E-Ticket</a>
        <?php endif; ?>
        <a href="add_expense.php?trip_id=<?php echo $row['trip_id']; ?>" class="btn btn-outline" style="font-size:0.73rem; padding:0.45rem 0.9rem;">+ Expense</a>
        <a href="review.php" class="btn btn-outline" style="font-size:0.73rem; padding:0.45rem 0.9rem;">⭐ Review</a>
        <a href="edit_trip.php?trip_id=<?php echo $row['trip_id']; ?>" class="btn btn-outline" style="font-size:0.73rem; padding:0.45rem 0.9rem;">✏ Edit</a>
        <a href="cancel_trip.php?trip_id=<?php echo $row['trip_id']; ?>" class="btn btn-danger" style="font-size:0.73rem; padding:0.45rem 0.9rem;">✕ Cancel</a>
      </div>
      <?php else: ?>
      <a href="edit_trip.php?trip_id=<?php echo $row['trip_id']; ?>" class="btn btn-outline" style="font-size:0.73rem; padding:0.45rem 0.9rem;">✏ Reopen Trip</a>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>

  <?php else: ?>
  <div class="card" style="text-align:center; padding:4rem 2rem;">
    <div style="font-size:3rem; margin-bottom:1rem;">🧳</div>
    <h3 style="margin-bottom:0.5rem;">No trips yet</h3>
    <p style="color:var(--muted); margin-bottom:2rem;">Start exploring and book your first adventure!</p>
    <div style="display:flex; gap:1rem; justify-content:center; flex-wrap:wrap;">
      <a href="packages.php" class="btn btn-primary">Browse Packages</a>
      <a href="custom_trip.php" class="btn btn-outline">Build Custom Trip</a>
    </div>
  </div>
  <?php endif; ?>
</div>
</body>
</html>