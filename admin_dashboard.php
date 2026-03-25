<?php
include "config.php";

if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'){
    header("Location: login.php"); exit();
}

// Feature 9 - Approve / Reject booking
if(isset($_POST["approve"])){
    $tid = (int)$_POST['trip_id'];
    mysqli_query($conn, "UPDATE trip SET trip_status='Approved' WHERE trip_id='$tid'");
    // Award reward points = 10% of budget (Feature 5)
    $trip = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM trip WHERE trip_id='$tid'"));
    $points = (int)($trip['total_budget'] * 0.10);
    mysqli_query($conn, "UPDATE users SET reward_points = reward_points + $points WHERE user_id='{$trip['user_id']}'");
}
if(isset($_POST["complete"])){
    $tid = (int)$_POST["trip_id"];
    mysqli_query($conn, "UPDATE trip SET trip_status='Completed' WHERE trip_id='$tid'");
    $ctrip = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM trip WHERE trip_id='$tid'"));
    if($ctrip){
        $pts = (int)($ctrip["total_budget"] * 0.10);
        mysqli_query($conn, "UPDATE users SET reward_points = reward_points + $pts WHERE user_id='".$ctrip["user_id"]."'");
    }
}
if(isset($_POST["reject"])){
    $tid = (int)$_POST['trip_id'];
    mysqli_query($conn, "UPDATE trip SET trip_status='Cancelled' WHERE trip_id='$tid'");
}

// Approve New Admin Registration
if(isset($_POST['approve_admin'])){
    $aid = (int)$_POST['admin_id'];
    mysqli_query($conn, "UPDATE admin SET is_approved=1 WHERE admin_id='$aid'");
}

// Feature 4 - Analytics using COUNT, SUM, AVG
$stats = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT
        (SELECT COUNT(*) FROM users) AS total_users,
        (SELECT COUNT(*) FROM trip WHERE trip_status != 'Cancelled') AS total_bookings,
        (SELECT COALESCE(SUM(total_budget),0) FROM trip WHERE trip_status IN ('Approved', 'Completed')) AS total_revenue,
        (SELECT COALESCE(AVG(rating),0) FROM package_review) AS avg_rating"));

// Most popular package using GROUP BY + COUNT
$popular_pkg = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT tp.package_name, COUNT(t.trip_id) AS bookings
     FROM travel_package tp
     LEFT JOIN trip t ON tp.package_id = t.package_id
     GROUP BY tp.package_id
     ORDER BY bookings DESC LIMIT 1"));

// Revenue by package
$rev_by_pkg = mysqli_fetch_all(mysqli_query($conn,
    "SELECT tp.package_name,
            COUNT(t.trip_id) AS bookings,
            COALESCE(SUM(t.total_budget),0) AS revenue,
            COALESCE(AVG(pr.rating),0) AS avg_rating
     FROM travel_package tp
     LEFT JOIN trip t ON tp.package_id = t.package_id AND t.trip_status IN ('Approved', 'Completed')
     LEFT JOIN package_review pr ON tp.package_id = pr.package_id
     GROUP BY tp.package_id
     ORDER BY revenue DESC"), MYSQLI_ASSOC);

// Pending bookings
$pending = mysqli_fetch_all(mysqli_query($conn,
    "SELECT t.*, u.full_name, u.email, tp.package_name
     FROM trip t
     JOIN users u ON t.user_id = u.user_id
     LEFT JOIN travel_package tp ON t.package_id = tp.package_id
     WHERE t.trip_status IN ('Pending','Approved')
     ORDER BY t.trip_id DESC"), MYSQLI_ASSOC);

// Pending admins
$pending_admins = mysqli_fetch_all(mysqli_query($conn,
    "SELECT * FROM admin WHERE is_approved = 0 ORDER BY created_at DESC"), MYSQLI_ASSOC);

// All users with reward points
$users = mysqli_fetch_all(mysqli_query($conn,
    "SELECT u.*, COUNT(t.trip_id) AS trip_count
     FROM users u
     LEFT JOIN trip t ON u.user_id = t.user_id
     GROUP BY u.user_id
     ORDER BY u.user_id DESC"), MYSQLI_ASSOC);

// Recent package reviews
$pkg_reviews = mysqli_fetch_all(mysqli_query($conn,
    "SELECT pr.*, u.full_name, tp.package_name
     FROM package_review pr
     JOIN users u ON pr.user_id = u.user_id
     JOIN travel_package tp ON pr.package_id = tp.package_id
     ORDER BY pr.review_date DESC LIMIT 10"), MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Panel — Voyager</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<nav>
  <a href="index.php" class="nav-brand">✦ Voyager</a>
  <div class="nav-links">
    <span style="color:var(--gold); font-size:0.8rem; letter-spacing:2px; text-transform:uppercase; font-weight:700;">Admin</span>
    <a href="logout.php" class="btn btn-danger" style="padding:0.4rem 1rem; font-size:0.8rem; border-radius:4px; display:inline-block;">Logout</a>
  </div>
</nav>

<div class="section">
  <div class="section-title">Admin Dashboard</div>
  <p class="section-subtitle">Welcome, <strong style="color:var(--gold);"><?php echo htmlspecialchars($_SESSION['admin_name']); ?></strong></p>

  <!-- Feature 4: Analytics Stats -->
  <div class="stat-grid" style="margin-bottom:3rem;">
    <div class="stat-card">
      <div class="stat-label">Total Users</div>
      <div class="stat-value"><?php echo $stats['total_users']; ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Total Bookings</div>
      <div class="stat-value"><?php echo $stats['total_bookings']; ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Total Revenue</div>
      <div class="stat-value">₹<?php echo number_format($stats['total_revenue']); ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Avg Package Rating</div>
      <div class="stat-value">⭐ <?php echo number_format($stats['avg_rating'],1); ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Most Popular Package</div>
      <div style="font-family:'Playfair Display',serif; font-size:1.1rem; color:var(--gold); margin-top:0.5rem;">
        <?php echo $popular_pkg ? htmlspecialchars($popular_pkg['package_name']) : 'N/A'; ?>
      </div>
      <div style="color:var(--muted); font-size:0.8rem;"><?php echo $popular_pkg['bookings'] ?? 0; ?> bookings</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Pending Approvals</div>
      <div class="stat-value" style="color:<?php echo count($pending) > 0 ? '#e07070' : 'var(--gold)'; ?>">
        <?php echo count($pending); ?>
      </div>
    </div>
  </div>

  <!-- Feature 9: Pending Booking Approvals -->
  <?php if(!empty($pending)): ?>
  <div class="card" style="margin-bottom:2.5rem;">
    <h3 style="font-size:1.2rem; margin-bottom:1.5rem; color:var(--gold);">
      ⏳ Pending Bookings (<?php echo count($pending); ?>)
    </h3>
    <div style="overflow-x:auto;">
      <table class="data-table">
        <thead>
          <tr><th>Trip ID</th><th>User</th><th>Email</th><th>Package</th><th>Budget</th><th>Date</th><th>Actions</th></tr>
        </thead>
        <tbody>
          <?php foreach($pending as $p): ?>
          <tr>
            <td style="color:var(--muted);">#<?php echo $p['trip_id']; ?></td>
            <td><?php echo htmlspecialchars($p['full_name']); ?></td>
            <td style="color:var(--muted);"><?php echo htmlspecialchars($p['email']); ?></td>
            <td><?php echo htmlspecialchars($p['package_name'] ?? $p['trip_name']); ?></td>
            <td style="color:var(--gold);">₹<?php echo number_format($p['total_budget']); ?></td>
            <td style="color:var(--muted);"><?php echo $p['start_date']; ?></td>
            <td>
              <form method="POST" style="display:inline;">
                <input type="hidden" name="trip_id" value="<?php echo $p['trip_id']; ?>">
                <button name="approve" class="btn" style="background:#64c878; color:#1a1a2e; font-size:0.75rem; padding:0.35rem 0.9rem; border-radius:4px; cursor:pointer; font-weight:700; border:none;">✓ Approve</button>
              </form>
              <form method="POST" style="display:inline; margin-left:0.5rem;">
                <input type="hidden" name="trip_id" value="<?php echo $p['trip_id']; ?>">
                <button name="reject" class="btn btn-danger" style="font-size:0.75rem; padding:0.35rem 0.9rem; cursor:pointer; border:none;">✕ Reject</button>
              </form>
              <form method="POST" style="display:inline; margin-left:0.5rem;">
                <input type="hidden" name="trip_id" value="<?php echo $p['trip_id']; ?>">
                <button name="complete" class="btn" style="background:#c9a84c; color:#1a1a2e; font-size:0.75rem; padding:0.35rem 0.9rem; border-radius:4px; cursor:pointer; font-weight:700; border:none;">✓ Complete</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

  <!-- Pending Staff Approvals -->
  <?php if(!empty($pending_admins)): ?>
  <div class="card" style="margin-bottom:2.5rem; border-color:var(--gold);">
    <h3 style="font-size:1.2rem; margin-bottom:1.5rem; color:var(--gold);">
      🛡️ Pending Staff Access Requests (<?php echo count($pending_admins); ?>)
    </h3>
    <div style="overflow-x:auto;">
      <table class="data-table">
        <thead>
          <tr><th>ID</th><th>Name</th><th>Email Address</th><th>Applied On</th><th>Action</th></tr>
        </thead>
        <tbody>
          <?php foreach($pending_admins as $pa): ?>
          <tr>
            <td style="color:var(--muted);">#<?php echo $pa['admin_id']; ?></td>
            <td style="font-weight:700;"><?php echo htmlspecialchars($pa['name']); ?></td>
            <td style="color:var(--gold);"><?php echo htmlspecialchars($pa['email']); ?></td>
            <td style="color:var(--muted);"><?php echo $pa['created_at']; ?></td>
            <td>
              <form method="POST" style="display:inline;">
                <input type="hidden" name="admin_id" value="<?php echo $pa['admin_id']; ?>">
                <button name="approve_admin" class="btn" style="background:var(--gold); color:var(--deep); font-size:0.75rem; padding:0.35rem 0.9rem; border-radius:4px; font-weight:700; border:none; cursor:pointer;">✓ Approve Access</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

  <!-- Feature 4: Package Performance with SQL aggregates -->
  <div class="card" style="margin-bottom:2.5rem;">
    <h3 style="font-size:1.2rem; margin-bottom:1.5rem;">📦 Package Performance</h3>
    <div style="overflow-x:auto;">
      <table class="data-table">
        <thead>
          <tr><th>Package</th><th>Bookings (COUNT)</th><th>Revenue (SUM)</th><th>Avg Rating (AVG)</th></tr>
        </thead>
        <tbody>
          <?php foreach($rev_by_pkg as $r): ?>
          <tr>
            <td><?php echo htmlspecialchars($r['package_name']); ?></td>
            <td><?php echo $r['bookings']; ?></td>
            <td style="color:var(--gold);">₹<?php echo number_format($r['revenue']); ?></td>
            <td><?php echo $r['avg_rating'] > 0 ? '⭐ '.number_format($r['avg_rating'],1) : '<span style="color:var(--muted)">No ratings</span>'; ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Feature 2: Package Reviews -->
  <div class="card" style="margin-bottom:2.5rem;">
    <h3 style="font-size:1.2rem; margin-bottom:1.5rem;">⭐ Recent Package Reviews</h3>
    <?php if(empty($pkg_reviews)): ?>
      <p style="color:var(--muted);">No reviews yet.</p>
    <?php else: ?>
    <div style="overflow-x:auto;">
      <table class="data-table">
        <thead>
          <tr><th>User</th><th>Package</th><th>Rating</th><th>Comment</th><th>Date</th></tr>
        </thead>
        <tbody>
          <?php foreach($pkg_reviews as $r): ?>
          <tr>
            <td><?php echo htmlspecialchars($r['full_name']); ?></td>
            <td><?php echo htmlspecialchars($r['package_name']); ?></td>
            <td style="color:var(--gold);"><?php echo str_repeat('⭐',(int)$r['rating']); ?></td>
            <td style="color:var(--muted);"><?php echo htmlspecialchars(substr($r['comment'],0,80)).(strlen($r['comment'])>80?'...':''); ?></td>
            <td style="color:var(--muted);"><?php echo $r['review_date']; ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <!-- Feature 5: Users + Reward Points -->
  <div class="card">
    <h3 style="font-size:1.2rem; margin-bottom:1.5rem;">👥 All Users & Reward Points</h3>
    <div style="overflow-x:auto;">
      <table class="data-table">
        <thead>
          <tr><th>ID</th><th>Name</th><th>Email</th><th>Trips</th><th>Reward Points</th></tr>
        </thead>
        <tbody>
          <?php foreach($users as $u): ?>
          <tr>
            <td style="color:var(--muted);">#<?php echo $u['user_id']; ?></td>
            <td><?php echo htmlspecialchars($u['full_name']); ?></td>
            <td style="color:var(--muted);"><?php echo htmlspecialchars($u['email']); ?></td>
            <td><?php echo $u['trip_count']; ?></td>
            <td style="color:var(--gold); font-weight:700;">🏆 <?php echo $u['reward_points'] ?? 0; ?> pts</td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</body>
</html>