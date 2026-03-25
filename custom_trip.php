<?php
include "config.php";

if(!isset($_SESSION['user_id'])){ header("Location: login.php"); exit(); }
$user_id = $_SESSION['user_id'];

// Fetch user reward points
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE user_id='$user_id'"));
$reward_points = $user['reward_points'] ?? 0;

// Feature 6: Fetch hotels, transport, activities for dynamic selection
$hotels     = mysqli_fetch_all(mysqli_query($conn, "SELECT * FROM hotel WHERE availability_status=1 ORDER BY price_per_day ASC"), MYSQLI_ASSOC);
$transports = mysqli_fetch_all(mysqli_query($conn, "SELECT * FROM transport_options WHERE availability=1 ORDER BY cost ASC"), MYSQLI_ASSOC);
$activities = mysqli_fetch_all(mysqli_query($conn, "SELECT a.*, d.destination_name FROM activities a LEFT JOIN destination d ON a.destination_id = d.destination_id ORDER BY a.cost ASC"), MYSQLI_ASSOC);
$destinations = mysqli_fetch_all(mysqli_query($conn, "SELECT * FROM destination ORDER BY destination_name ASC"), MYSQLI_ASSOC);

$success = false; $error = '';

if(isset($_POST['book_custom'])){
    $dest_id     = (int)$_POST['destination_id'];
    $hotel_id    = (int)$_POST['hotel_id'];
    $transport_id= (int)$_POST['transport_id'];
    $activity_id = (int)$_POST['activity_id'];
    $start_date  = $_POST['start_date'];
    $end_date    = $_POST['end_date'];
    $use_points  = isset($_POST['use_points']) ? 1 : 0;

    // Feature 7: Check availability - get package from selection
    // Calculate nights
    $nights = max(1, (strtotime($end_date) - strtotime($start_date)) / 86400);

    // Feature 6: JOIN query to compute total cost dynamically
    $hotel_row     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM hotel WHERE hotel_id='$hotel_id'"));
    $transport_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM transport_options WHERE transport_id='$transport_id'"));
    $activity_row  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM activities WHERE activity_id='$activity_id'"));

    $hotel_cost     = ($hotel_row['price_per_day'] ?? 0) * $nights;
    $transport_cost = $transport_row['cost'] ?? 0;
    $activity_cost  = $activity_row['cost'] ?? 0;
    $total_cost     = $hotel_cost + $transport_cost + $activity_cost;

    // Feature 5: Apply reward points (100 pts = ₹100 discount)
    $discount = 0;
    if($use_points && $reward_points >= 100){
        $discount = min(floor($reward_points / 100) * 100, $total_cost * 0.20); // max 20% off
        $pts_used = $discount;
        $total_cost -= $discount;
        mysqli_query($conn, "UPDATE users SET reward_points = reward_points - $pts_used WHERE user_id='$user_id'");
    }

    // Insert into trip
    $trip_name = "Custom Trip";
    mysqli_query($conn,
    "INSERT INTO trip(user_id, trip_name, start_date, end_date, total_budget, trip_status)
     VALUES('$user_id', '$trip_name', '$start_date', '$end_date', '$total_cost', 'Pending')");

    $new_trip_id = mysqli_insert_id($conn);

    // Insert into custom_booking
    mysqli_query($conn,
    "INSERT INTO custom_booking(user_id, destination_id, hotel_id, transport_id, activity_id, total_cost, booking_date, trip_status)
     VALUES('$user_id', '$dest_id', '$hotel_id', '$transport_id', '$activity_id', '$total_cost', CURDATE(), 'Pending')");

    $success = true;
    // Refresh points
    $user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE user_id='$user_id'"));
    $reward_points = $user['reward_points'] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Build Your Trip — Voyager</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include "nav.php"; ?>

<div class="section">
  <div class="section-title">Build Your Custom Trip</div>
  <p class="section-subtitle">Select your preferences and we'll calculate the total cost instantly</p>

  <?php if($success): ?>
  <div class="alert alert-success" style="margin-bottom:2rem;">✓ Custom trip booked! Status: <strong>Pending Admin Approval</strong></div>
  <?php endif; ?>

  <!-- Reward Points Banner -->
  <?php if($reward_points > 0): ?>
  <div class="card" style="margin-bottom:2rem; border-color:rgba(201,168,76,0.5); display:flex; align-items:center; gap:1.5rem; flex-wrap:wrap;">
    <div style="font-size:2rem;">🏆</div>
    <div>
      <div style="color:var(--gold); font-weight:700; font-size:1.1rem;"><?php echo $reward_points; ?> Reward Points Available</div>
      <div style="color:var(--muted); font-size:0.85rem;">Every 100 pts = ₹100 discount (up to 20% off)</div>
    </div>
  </div>
  <?php endif; ?>

  <div style="display:grid; grid-template-columns:1.5fr 1fr; gap:2rem; align-items:start;">

    <form method="POST" id="customForm">
      <!-- Destination -->
      <div class="form-group">
        <label>Destination</label>
        <select name="destination_id" required onchange="updateCost()" style="width:100%; padding:0.85rem 1rem; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:6px; color:var(--white); font-family:'Lato',sans-serif; font-size:0.95rem; outline:none;">
          <option value="" style="background:var(--deep);">-- Select Destination --</option>
          <?php foreach($destinations as $d): ?>
          <option value="<?php echo $d['destination_id']; ?>" style="background:var(--deep);">
            <?php echo htmlspecialchars($d['destination_name']); ?><?php if(!empty($d['city'])) echo ' — '.$d['city']; ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Dates -->
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
        <div class="form-group">
          <label>Start Date</label>
          <input type="date" name="start_date" required onchange="updateCost()" min="<?php echo date('Y-m-d'); ?>">
        </div>
        <div class="form-group">
          <label>End Date</label>
          <input type="date" name="end_date" required onchange="updateCost()" min="<?php echo date('Y-m-d'); ?>">
        </div>
      </div>

      <!-- Hotel -->
      <div class="form-group">
        <label>Hotel Type</label>
        <select name="hotel_id" required onchange="updateCost()" style="width:100%; padding:0.85rem 1rem; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:6px; color:var(--white); font-family:'Lato',sans-serif; font-size:0.95rem; outline:none;">
          <option value="" style="background:var(--deep);">-- Select Hotel --</option>
          <?php foreach($hotels as $h): ?>
          <option value="<?php echo $h['hotel_id']; ?>"
            data-cost="<?php echo $h['price_per_day']; ?>"
            style="background:var(--deep);">
            <?php echo htmlspecialchars($h['hotel_name']); ?> — ₹<?php echo number_format($h['price_per_day']); ?>/night (<?php echo $h['star_rating'] . ' star'; ?>)
          </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Transport -->
      <div class="form-group">
        <label>Transport Mode</label>
        <select name="transport_id" required onchange="updateCost()" style="width:100%; padding:0.85rem 1rem; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:6px; color:var(--white); font-family:'Lato',sans-serif; font-size:0.95rem; outline:none;">
          <option value="" style="background:var(--deep);">-- Select Transport --</option>
          <?php foreach($transports as $t): ?>
          <option value="<?php echo $t['transport_id']; ?>"
            data-cost="<?php echo $t['cost']; ?>"
            style="background:var(--deep);">
            <?php echo htmlspecialchars($t['transport_type']); ?> (<?php echo htmlspecialchars($t['provider_name'] ?? ''); ?>) — ₹<?php echo number_format($t['cost']); ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Activity -->
      <div class="form-group">
        <label>Activity</label>
        <select name="activity_id" required onchange="updateCost()" style="width:100%; padding:0.85rem 1rem; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:6px; color:var(--white); font-family:'Lato',sans-serif; font-size:0.95rem; outline:none;">
          <option value="" style="background:var(--deep);">-- Select Activity --</option>
          <?php foreach($activities as $a): ?>
          <option value="<?php echo $a['activity_id']; ?>"
            data-cost="<?php echo $a['cost']; ?>"
            style="background:var(--deep);">
            <?php echo htmlspecialchars($a['activity_name']); ?> (<?php echo $a['activity_type']; ?>) — ₹<?php echo number_format($a['cost']); ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>

      <?php if($reward_points >= 100): ?>
      <div class="form-group" style="display:flex; align-items:center; gap:0.75rem;">
        <input type="checkbox" name="use_points" id="usePoints" onchange="updateCost()" style="width:auto; cursor:pointer;">
        <label for="usePoints" style="text-transform:none; letter-spacing:0; font-size:0.95rem; cursor:pointer; color:var(--gold);">
          🏆 Use reward points for discount
        </label>
      </div>
      <?php endif; ?>

      <button type="submit" name="book_custom" class="btn btn-primary" style="width:100%; margin-top:1rem;">
        Book This Trip (Pending Approval)
      </button>
    </form>

    <!-- Live Cost Calculator -->
    <div class="card" style="position:sticky; top:100px;">
      <h3 style="font-size:1.1rem; margin-bottom:1.5rem;">💰 Cost Summary</h3>
      <div id="costBreakdown">
        <div style="color:var(--muted); font-size:0.9rem;">Select your preferences to see the cost breakdown</div>
      </div>
      <div style="border-top:1px solid var(--border); margin-top:1.5rem; padding-top:1.5rem;">
        <div style="display:flex; justify-content:space-between; align-items:center;">
          <span style="color:var(--muted); font-size:0.9rem;">Estimated Total</span>
          <span id="totalCost" style="font-family:'Playfair Display',serif; font-size:1.8rem; color:var(--gold);">₹0</span>
        </div>
        <div id="pointsDiscount" style="display:none; color:#64c878; font-size:0.85rem; margin-top:0.5rem; text-align:right;"></div>
      </div>
      <div style="margin-top:1.5rem; padding:1rem; background:rgba(201,168,76,0.08); border-radius:8px; font-size:0.82rem; color:var(--muted);">
        🏆 On approval, you'll earn <span style="color:var(--gold);" id="pointsEarn">0</span> reward points (10% of total)
      </div>
    </div>

  </div>
</div>

<script>
const rewardPoints = <?php echo $reward_points; ?>;

function updateCost(){
  const hotelSel = document.querySelector('[name="hotel_id"]');
  const transSel = document.querySelector('[name="transport_id"]');
  const actSel   = document.querySelector('[name="activity_id"]');
  const startEl  = document.querySelector('[name="start_date"]');
  const endEl    = document.querySelector('[name="end_date"]');
  const usePoints= document.getElementById('usePoints');

  const hotelCost = parseFloat(hotelSel.selectedOptions[0]?.dataset.cost || 0);
  const transCost = parseFloat(transSel.selectedOptions[0]?.dataset.cost || 0);
  const actCost   = parseFloat(actSel.selectedOptions[0]?.dataset.cost || 0);

  let nights = 1;
  if(startEl.value && endEl.value){
    const diff = (new Date(endEl.value) - new Date(startEl.value)) / 86400000;
    nights = Math.max(1, diff);
  }

  const hotelTotal = hotelCost * nights;
  let total = hotelTotal + transCost + actCost;

  // Points discount
  let discount = 0;
  if(usePoints && usePoints.checked && rewardPoints >= 100){
    discount = Math.min(Math.floor(rewardPoints / 100) * 100, total * 0.20);
    total -= discount;
  }

  // Update breakdown
  const bd = document.getElementById('costBreakdown');
  const rows = [
    ['🏨 Hotel', hotelTotal > 0 ? `₹${hotelCost.toLocaleString()} × ${nights} night${nights>1?'s':''} = ₹${hotelTotal.toLocaleString()}` : '—'],
    ['🚌 Transport', transCost > 0 ? `₹${transCost.toLocaleString()}` : '—'],
    ['🎯 Activity', actCost > 0 ? `₹${actCost.toLocaleString()}` : '—'],
  ];
  bd.innerHTML = rows.map(([label, val]) =>
    `<div style="display:flex;justify-content:space-between;padding:0.6rem 0;border-bottom:1px solid rgba(255,255,255,0.05);font-size:0.9rem;">
      <span style="color:var(--muted);">${label}</span><span>${val}</span>
    </div>`
  ).join('');

  document.getElementById('totalCost').textContent = `₹${Math.round(total).toLocaleString()}`;
  document.getElementById('pointsEarn').textContent = Math.floor(total * 0.10) + ' pts';

  const pdEl = document.getElementById('pointsDiscount');
  if(discount > 0){ pdEl.style.display='block'; pdEl.textContent = `🏆 Reward discount applied: −₹${discount}`; }
  else { pdEl.style.display='none'; }
}
</script>
</body>
</html>