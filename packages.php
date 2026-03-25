<?php
include "config.php";

$result = mysqli_query($conn,
    "SELECT tp.*,
            COALESCE(AVG(pr.rating),0) AS avg_rating,
            COUNT(pr.review_id) AS review_count
     FROM travel_package tp
     LEFT JOIN package_review pr ON tp.package_id = pr.package_id
     GROUP BY tp.package_id
     ORDER BY tp.package_id ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Travel Packages — Voyager</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include "nav.php"; ?>

<div class="section">
  <div style="display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:1rem; margin-bottom:2.5rem;">
    <div>
      <div class="section-title">Travel Packages</div>
      <p class="section-subtitle" style="margin-bottom:0;">Discover our handpicked destinations and experiences</p>
    </div>
    <a href="recommend.php" class="btn btn-outline" style="font-size:0.85rem;">🔍 Filter by Budget</a>
  </div>

  <div class="grid">
    <?php
    $icons = ['🏔️','🏝️','🏙️','🌿','🏛️','🌊','🌄','🗼','🏜️','🌋'];
    $i = 0;
    while($row = mysqli_fetch_assoc($result)):
      $icon = $icons[$i % count($icons)];
      $stars = round($row['avg_rating']);
      $slots = $row['available_slots'] ?? 999;

      // Feature 10: Dynamic Seasonal Pricing
      $current_month = (int)date('n');
      $season_row = mysqli_fetch_assoc(mysqli_query($conn,
          "SELECT * FROM seasonal_pricing
           WHERE package_id='{$row['package_id']}'
             AND '$current_month' BETWEEN month_start AND month_end
           LIMIT 1"));
      $price_multiplier = $season_row['price_multiplier'] ?? 1.0;
      $season_label     = $season_row['season_name'] ?? null;
      $season_base      = $row['total_cost'] * $price_multiplier;
      $discounted       = $season_base * (1 - $row['discount']/100);
      $is_peak          = $price_multiplier > 1.0;
      $is_off           = $price_multiplier < 1.0;

      $i++;
    ?>
    <div class="card pkg-card" style="animation: fadeUp 0.5s <?php echo $i*0.1; ?>s ease both;">
      <div style="font-size:2.5rem; margin-bottom:1rem;"><?php echo $icon; ?></div>
      <?php if($season_label): ?>
      <div style="position:absolute; top:1.5rem; right:1.5rem;
        background:<?php echo $is_peak ? 'rgba(224,112,112,0.2)' : 'rgba(100,200,120,0.2)'; ?>;
        color:<?php echo $is_peak ? '#e07070' : '#64c878'; ?>;
        font-size:0.65rem; font-weight:700; padding:0.2rem 0.6rem; border-radius:50px;
        letter-spacing:1px; text-transform:uppercase;
        border:1px solid <?php echo $is_peak ? 'rgba(224,112,112,0.4)' : 'rgba(100,200,120,0.4)'; ?>;">
        <?php echo $is_peak ? '🔥' : '❄️'; ?> <?php echo htmlspecialchars($season_label); ?>
      </div>
      <?php endif; ?>
      <h3 <?php echo $season_label ? 'style="padding-right:6rem;"' : ''; ?>><?php echo htmlspecialchars($row['package_name']); ?></h3>
      <div class="pkg-meta">
        <span>🕐 <?php echo htmlspecialchars($row['duration']); ?></span>
        <?php if($row['discount'] > 0): ?><span>🏷 <?php echo $row['discount']; ?>% off</span><?php endif; ?>
        <?php if($price_multiplier != 1.0): ?>
        <span style="color:<?php echo $is_peak ? '#e07070' : '#64c878'; ?>;">
          📊 <?php echo $is_peak ? '+' . round(($price_multiplier-1)*100) . '% peak' : round((1-$price_multiplier)*100) . '% off season'; ?>
        </span>
        <?php endif; ?>
        <span <?php echo $slots <= 3 ? 'style="color:#e07070;"' : ''; ?>>
          🪑 <?php echo $slots <= 0 ? 'Fully Booked' : "$slots slots"; ?>
        </span>
      </div>
      <?php if($row['avg_rating'] > 0): ?>
      <div style="color:var(--gold); font-size:0.85rem; margin-bottom:0.5rem;">
        <?php echo str_repeat('⭐',$stars); ?>
        <?php echo number_format($row['avg_rating'],1); ?>/5
        <span style="color:var(--muted);">(<?php echo $row['review_count']; ?>)</span>
      </div>
      <?php endif; ?>
      <div class="pkg-price">₹<?php echo number_format($discounted); ?></div>
      <?php if($row['discount'] > 0 || $price_multiplier != 1.0): ?>
      <div style="color:var(--muted); font-size:0.85rem; text-decoration:line-through; margin-bottom:1rem;">₹<?php echo number_format($row['total_cost']); ?></div>
      <?php else: ?><div style="margin-bottom:1rem;"></div><?php endif; ?>
      <div style="display:flex; gap:0.75rem; flex-wrap:wrap;">
        <?php if($slots > 0): ?>
        <a href="book.php?package_id=<?php echo $row['package_id']; ?>" class="btn btn-primary" style="font-size:0.85rem;">Book Now</a>
        <?php else: ?>
        <span class="btn" style="background:rgba(255,255,255,0.08); color:var(--muted); font-size:0.85rem; cursor:not-allowed;">Fully Booked</span>
        <?php endif; ?>
        <a href="package_review.php?package_id=<?php echo $row['package_id']; ?>" class="btn btn-outline" style="font-size:0.85rem;">Reviews</a>
      </div>
    </div>
    <?php endwhile; ?>
  </div>
</div>
</body>
</html>