<?php
include "config.php";

if(!isset($_SESSION['user_id'])){ header("Location: login.php"); exit(); }

$package_id = (int)($_GET['package_id'] ?? 0);
$user_id    = $_SESSION['user_id'];

// Get package details
$pkg = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM travel_package WHERE package_id='$package_id'"));

if(!$pkg){ header("Location: packages.php"); exit(); }

// Dynamic Pricing calculation
$current_month = (int)date('n');
$season_row = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM seasonal_pricing
     WHERE package_id='$package_id'
       AND '$current_month' BETWEEN month_start AND month_end
     LIMIT 1"));
$price_multiplier = $season_row['price_multiplier'] ?? 1.0;
$unit_price = $pkg['total_cost'] * $price_multiplier * (1 - $pkg['discount']/100);

if(isset($_POST['confirm_booking'])){
    $passengers = (int)($_POST['passengers'] ?? 1);
    if($passengers < 1) $passengers = 1;

    $total_budget = $unit_price * $passengers;
    $slots = $pkg['available_slots'] ?? 0;

    if($slots < $passengers){
        $error = "Sorry, only $slots slots available for this package.";
    } else {
        $start_date = $pkg['start_date'];
        $end_date   = $pkg['end_date'];

        $res = mysqli_query($conn,
        "INSERT INTO trip(user_id, trip_name, start_date, end_date, total_budget, passengers, trip_status, package_id)
         VALUES('$user_id', '".mysqli_real_escape_string($conn, $pkg['package_name'])."', '$start_date', '$end_date',
         '$total_budget', '$passengers', 'Pending', '$package_id')");

        if($res){
            mysqli_query($conn,
            "UPDATE travel_package SET available_slots = available_slots - $passengers WHERE package_id='$package_id'");
            $booking_success = true;
        } else {
            $error = "Booking failed: " . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Confirm Booking — Voyager</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
  <style>
    .leaflet-container { background: #1a1a2e; border-radius: 8px; }
  </style>
</head>
<body>
<?php include "nav.php"; ?>

<div class="form-wrapper" style="max-width:850px; animation: fadeUp 0.5s ease both;">
  <?php if(isset($booking_success)): ?>
    <div style="text-align:center; padding:2rem;">
        <div style="font-size:4rem; margin-bottom:1.5rem;">🎉</div>
        <h1 style="font-family:'Playfair Display',serif; font-size:2.5rem; margin-bottom:1rem;">
          Booking <em style="color:var(--gold);">Submitted!</em>
        </h1>
        <p style="color:var(--muted); margin-bottom:1rem;">
          Your booking for <strong><?php echo htmlspecialchars($pkg['package_name']); ?></strong> has been sent for approval.
        </p>
        <a href="dashboard.php" class="btn btn-primary">View My Dashboard →</a>
    </div>
  <?php else: ?>
    <h2 style="font-family:'Playfair Display',serif; color:var(--gold);">Confirm Booking</h2>
    <p class="subtitle"><?php echo htmlspecialchars($pkg['package_name']); ?></p>

    <?php if(isset($error)): ?>
        <div class="alert alert-error">⚠ <?php echo $error; ?></div>
    <?php endif; ?>

    <div style="display:grid; grid-template-columns:1.2fr 1fr; gap:2rem; margin-bottom:2rem;">
        <div class="card" style="background:rgba(255,255,255,0.03);">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem;">
                <div>
                    <div style="color:var(--muted); font-size:0.75rem; text-transform:uppercase; letter-spacing:1px;">Departure</div>
                    <div style="font-size:1.1rem; color:var(--white); font-weight:700;"><?php echo date('M d, Y', strtotime($pkg['start_date'])); ?></div>
                </div>
                <div>
                    <div style="color:var(--muted); font-size:0.75rem; text-transform:uppercase; letter-spacing:1px;">Arrival</div>
                    <div style="font-size:1.1rem; color:var(--white); font-weight:700;"><?php echo date('M d, Y', strtotime($pkg['end_date'])); ?></div>
                </div>
                <div>
                    <div style="color:var(--muted); font-size:0.75rem; text-transform:uppercase; letter-spacing:1px;">Duration</div>
                    <div style="font-size:1.1rem; color:var(--white);"><?php echo htmlspecialchars($pkg['duration']); ?></div>
                </div>
                <div>
                    <div style="color:var(--muted); font-size:0.75rem; text-transform:uppercase; letter-spacing:1px;">Unit Price</div>
                    <div style="font-size:1.1rem; color:var(--gold); font-weight:700;">₹<?php echo number_format($unit_price); ?></div>
                </div>
            </div>
        </div>
        
        <div id="bookingMap" style="height:100%; min-height:220px; border-radius:8px; border:1px solid rgba(255,255,255,0.1);"></div>
    </div>

    <?php
    $package_coords = [1=>[32.2396,77.1887], 2=>[15.2993,74.1240], 3=>[9.4981,76.3388], 4=>[26.9124,75.7873], 5=>[11.6234,92.7265], 6=>[30.0869,78.2676], 7=>[34.1526,77.5771], 8=>[11.4102,76.6950]];
    $coords = $package_coords[$package_id] ?? [20.5937, 78.9629];
    ?>
    <script>
        var map = L.map('bookingMap', {zoomControl: false}).setView([<?php echo $coords[0]; ?>, <?php echo $coords[1]; ?>], 10);
        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png').addTo(map);
        L.marker([<?php echo $coords[0]; ?>, <?php echo $coords[1]; ?>]).addTo(map);
    </script>

    <form method="POST">
        <div class="form-group">
            <label>Number of Passengers</label>
            <input type="number" name="passengers" id="passengers" value="1" min="1" max="<?php echo $pkg['available_slots']; ?>" required oninput="updateTotal()">
            <p style="font-size:0.8rem; color:var(--muted); margin-top:0.4rem;">Available Slots: <?php echo $pkg['available_slots']; ?></p>
        </div>

        <div style="background:rgba(201,168,76,0.1); padding:1.5rem; border-radius:8px; border:1px solid rgba(201,168,76,0.2); margin-bottom:2rem; text-align:center;">
            <div style="color:var(--muted); font-size:0.85rem; margin-bottom:0.2rem;">Total Estimated Budget</div>
            <div id="total_budget_display" style="font-size:2rem; color:var(--gold); font-weight:700; font-family:'Playfair Display',serif;">
                ₹<?php echo number_format($unit_price); ?>
            </div>
        </div>

        <button type="submit" name="confirm_booking" class="btn btn-primary" style="width:100%;">Complete Booking</button>
        <a href="packages.php" class="btn btn-outline" style="width:100%; margin-top:0.75rem; text-align:center;">Cancel</a>
    </form>

    <script>
    function updateTotal() {
        const p = document.getElementById('passengers').value;
        const unit = <?php echo $unit_price; ?>;
        const total = p * unit;
        document.getElementById('total_budget_display').innerText = '₹' + new Intl.NumberFormat().format(total);
    }
    </script>
  <?php endif; ?>
</div>

</body>
</html>