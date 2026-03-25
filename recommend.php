<?php
include "config.php";

$min = isset($_GET['min']) ? (int)$_GET['min'] : 0;
$max = isset($_GET['max']) ? (int)$_GET['max'] : 999999;
$searched = isset($_GET['search']);

// Budget filtered packages with average rating
$packages = [];
if($searched){
    $result = mysqli_query($conn,
    "SELECT tp.*,
            COALESCE(AVG(pr.rating), 0) AS avg_rating,
            COUNT(pr.review_id) AS review_count
     FROM travel_package tp
     LEFT JOIN package_review pr ON tp.package_id = pr.package_id
     WHERE (tp.total_cost * (1 - tp.discount/100)) BETWEEN $min AND $max
     GROUP BY tp.package_id
     ORDER BY avg_rating DESC");
    $packages = mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// Smart recommendations based on popularity (most booked)
$popular = mysqli_fetch_all(mysqli_query($conn,
    "SELECT tp.*, COUNT(t.trip_id) AS booking_count,
            COALESCE(AVG(pr.rating),0) AS avg_rating
     FROM travel_package tp
     LEFT JOIN trip t  ON tp.package_id = t.package_id
     LEFT JOIN package_review pr ON tp.package_id = pr.package_id
     GROUP BY tp.package_id
     ORDER BY booking_count DESC, avg_rating DESC
     LIMIT 3"), MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Find Packages — Voyager</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include "nav.php"; ?>

<div class="section">
  <div class="section-title">Find Your Perfect Package</div>
  <p class="section-subtitle">Enter your budget range to discover matching travel packages</p>

  <!-- Budget Filter Form -->
  <form method="GET" style="display:flex; gap:1rem; flex-wrap:wrap; align-items:flex-end; margin-bottom:3rem;">
    <div class="form-group" style="margin:0; flex:1; min-width:160px;">
      <label>Min Budget (₹)</label>
      <input type="number" name="min" value="<?php echo $min; ?>" placeholder="0" min="0">
    </div>
    <div class="form-group" style="margin:0; flex:1; min-width:160px;">
      <label>Max Budget (₹)</label>
      <input type="number" name="max" value="<?php echo $max == 999999 ? '' : $max; ?>" placeholder="Any">
    </div>
    <input type="hidden" name="search" value="1">
    <button type="submit" class="btn btn-primary" style="padding:0.85rem 2rem;">Search Packages</button>
    <?php if($searched): ?>
    <a href="recommend.php" class="btn btn-outline" style="padding:0.85rem 1.5rem;">Clear</a>
    <?php endif; ?>
  </form>

  <!-- Search Results -->
  <?php if($searched): ?>
  <div class="section-title" style="font-size:1.3rem; margin-bottom:0.5rem;">
    <?php echo count($packages); ?> Package<?php echo count($packages) != 1 ? 's' : ''; ?> found
    <span style="color:var(--muted); font-size:1rem; font-family:'Lato',sans-serif;"> between ₹<?php echo number_format($min); ?> – ₹<?php echo number_format($max); ?></span>
  </div>

  <?php if(empty($packages)): ?>
  <div class="card" style="text-align:center; padding:3rem; margin-bottom:3rem;">
    <div style="font-size:2.5rem; margin-bottom:1rem;">🔍</div>
    <h3>No packages found in this range</h3>
    <p style="color:var(--muted); margin-top:0.5rem;">Try a wider budget range</p>
  </div>
  <?php else: ?>
  <div class="grid" style="margin-bottom:3rem;">
    <?php foreach($packages as $i => $row):
      $final = $row['total_cost'] * (1 - $row['discount']/100);
      $stars = round($row['avg_rating']);
    ?>
    <div class="card pkg-card" style="animation: fadeUp 0.4s <?php echo $i*0.08; ?>s ease both;">
      <h3><?php echo htmlspecialchars($row['package_name']); ?></h3>
      <div class="pkg-meta">
        <span>🕐 <?php echo htmlspecialchars($row['duration']); ?></span>
        <?php if($row['discount'] > 0): ?><span>🏷 <?php echo $row['discount']; ?>% off</span><?php endif; ?>
        <?php if($row['available_slots'] ?? 0 > 0): ?><span>🪑 <?php echo $row['available_slots']; ?> slots left</span><?php endif; ?>
      </div>
      <?php if($row['avg_rating'] > 0): ?>
      <div style="color:var(--gold); font-size:0.9rem; margin-bottom:0.5rem;">
        <?php echo str_repeat('⭐', $stars); ?> <?php echo number_format($row['avg_rating'],1); ?>/5
        <span style="color:var(--muted); font-size:0.8rem;">(<?php echo $row['review_count']; ?> reviews)</span>
      </div>
      <?php endif; ?>
      <div class="pkg-price">₹<?php echo number_format($final); ?></div>
      <?php if($row['discount'] > 0): ?>
      <div style="color:var(--muted); font-size:0.85rem; text-decoration:line-through; margin-bottom:1rem;">₹<?php echo number_format($row['total_cost']); ?></div>
      <?php else: ?><div style="margin-bottom:1rem;"></div><?php endif; ?>
      <div style="display:flex; gap:0.75rem; flex-wrap:wrap;">
        <a href="book.php?package_id=<?php echo $row['package_id']; ?>" class="btn btn-primary" style="font-size:0.85rem;">Book Now</a>
        <a href="package_review.php?package_id=<?php echo $row['package_id']; ?>" class="btn btn-outline" style="font-size:0.85rem;">Reviews</a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
  <?php endif; ?>

  <!-- Smart Recommendations -->
  <div class="section-title" style="font-size:1.3rem; margin-bottom:0.3rem;">⭐ Most Popular Packages</div>
  <p class="section-subtitle">Based on bookings and ratings</p>
  <div class="grid">
    <?php foreach($popular as $i => $row):
      $final = $row['total_cost'] * (1 - $row['discount']/100);
    ?>
    <div class="card pkg-card" style="animation: fadeUp 0.4s <?php echo $i*0.1; ?>s ease both;">
      <div style="position:absolute; top:1.5rem; right:1.5rem; background:var(--gold); color:var(--deep);
        font-size:0.7rem; font-weight:700; padding:0.2rem 0.6rem; border-radius:50px; letter-spacing:1px;">
        #<?php echo $i+1; ?> POPULAR
      </div>
      <h3 style="padding-right:5rem;"><?php echo htmlspecialchars($row['package_name']); ?></h3>
      <div class="pkg-meta">
        <span>🕐 <?php echo htmlspecialchars($row['duration']); ?></span>
        <span>📦 <?php echo $row['booking_count']; ?> bookings</span>
      </div>
      <?php if($row['avg_rating'] > 0): ?>
      <div style="color:var(--gold); font-size:0.9rem; margin-bottom:0.5rem;">
        ⭐ <?php echo number_format($row['avg_rating'],1); ?>/5
      </div>
      <?php endif; ?>
      <div class="pkg-price">₹<?php echo number_format($final); ?></div>
      <div style="margin-bottom:1rem;"></div>
      <a href="book.php?package_id=<?php echo $row['package_id']; ?>" class="btn btn-primary" style="font-size:0.85rem;">Book Now</a>
    </div>
    <?php endforeach; ?>
  </div>
</div>
</body>
</html>