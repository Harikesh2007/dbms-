<?php
include "config.php";

$package_id = (int)($_GET['package_id'] ?? 0);
$package = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM travel_package WHERE package_id='$package_id'"));
if(!$package){ header("Location: packages.php"); exit(); }

$success = false; $error = '';

if(isset($_POST['submit']) && isset($_SESSION['user_id'])){
    $user_id = $_SESSION['user_id'];
    $rating  = (float)$_POST['rating'];
    $comment = mysqli_real_escape_string($conn, $_POST['comment']);

    // Check if already reviewed
    $existing = mysqli_query($conn,
        "SELECT * FROM package_review WHERE user_id='$user_id' AND package_id='$package_id'");
    if(mysqli_num_rows($existing) > 0){
        $error = "You have already reviewed this package.";
    } else {
        mysqli_query($conn,
        "INSERT INTO package_review(user_id, package_id, rating, comment, review_date)
         VALUES('$user_id', '$package_id', '$rating', '$comment', CURDATE())");
        $success = true;
    }
}

// Get all reviews with user names
$reviews = mysqli_fetch_all(mysqli_query($conn,
    "SELECT pr.*, u.full_name
     FROM package_review pr
     JOIN users u ON pr.user_id = u.user_id
     WHERE pr.package_id = '$package_id'
     ORDER BY pr.review_date DESC"), MYSQLI_ASSOC);

// Average rating
$avg = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT AVG(rating) AS avg_rating, COUNT(*) AS total
     FROM package_review WHERE package_id='$package_id'"));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reviews — <?php echo htmlspecialchars($package['package_name']); ?></title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
  <style>
    .leaflet-container { background: #1a1a2e; border-radius: 8px; font-family: 'Lato', sans-serif; }
    .leaflet-popup-content-wrapper, .leaflet-popup-tip { background: #1a1a2e; color: #fff; border: 1px solid var(--gold); }
    .leaflet-popup-content { margin: 10px 14px; line-height: 1.4; color:var(--muted); }
    .leaflet-popup-content strong { color: var(--gold); }
  </style>
</head>
<body>
<?php include "nav.php"; ?>

<div class="section">
  <!-- Package Header -->
  <div style="margin-bottom:2.5rem;">
    <a href="packages.php" style="color:var(--muted); font-size:0.85rem;">← Back to Packages</a>
    <h1 style="font-family:'Playfair Display',serif; font-size:2rem; margin-top:0.5rem; margin-bottom:1.5rem;">
      <?php echo htmlspecialchars($package['package_name']); ?>
    </h1>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:2rem; align-items:center;">
      <!-- Stats -->
      <div>
        <p style="color:rgba(255,255,255,0.85); font-size:1.05rem; line-height:1.7; margin-bottom:2rem;">
          <?php echo nl2br(htmlspecialchars($package['description'] ?? 'An unforgettable journey awaits you perfectly tailored for your adventure.')); ?>
        </p>
        <div style="display:flex; align-items:center; gap:2rem; flex-wrap:wrap;">
          <?php if($avg['total'] > 0): ?>
          <div style="display:flex; align-items:center; gap:0.75rem;">
            <span style="font-family:'Playfair Display',serif; font-size:3rem; color:var(--gold);">
              <?php echo number_format($avg['avg_rating'],1); ?>
            </span>
            <div>
              <div style="color:var(--gold); font-size:1.2rem;">
                <?php
                $stars = round($avg['avg_rating']);
                echo str_repeat('⭐', $stars) . str_repeat('☆', 5-$stars);
                ?>
              </div>
              <div style="color:var(--muted); font-size:0.85rem;"><?php echo $avg['total']; ?> reviews</div>
            </div>
          </div>
          <?php else: ?>
          <div style="color:var(--muted);">No reviews yet — be the first!</div>
          <?php endif; ?>
        </div>
      </div>
      
      <!-- Feature 11: Interactive Map -->
      <?php
      $package_coords = [
          1 => [32.2396, 77.1887], // Manali
          2 => [15.2993, 74.1240], // Goa
          3 => [9.4981, 76.3388],  // Kerala
          4 => [26.9124, 75.7873], // Rajasthan
          5 => [11.6234, 92.7265], // Andaman
          6 => [30.0869, 78.2676], // Rishikesh
          7 => [34.1526, 77.5771], // Leh Ladakh
          8 => [11.4102, 76.6950]  // Ooty
      ];
      $coords = $package_coords[$package_id] ?? [20.5937, 78.9629];
      ?>
      <div id="destMap" style="width:100%; height:250px; border-radius:8px; border:1px solid rgba(255,255,255,0.1); z-index:1;"></div>
      <script>
        var map = L.map('destMap').setView([<?php echo $coords[0]; ?>, <?php echo $coords[1]; ?>], <?php echo isset($package_coords[$package_id]) ? 10 : 4; ?>);
        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            subdomains: 'abcd',
            maxZoom: 20
        }).addTo(map);
        L.marker([<?php echo $coords[0]; ?>, <?php echo $coords[1]; ?>]).addTo(map)
            .bindPopup("<strong><?php echo addslashes($package['package_name']); ?></strong><br>Explore the beauty of this destination.")
            .openPopup();
      </script>
    </div>
  </div>

  <div style="display:grid; grid-template-columns:1fr 1.5fr; gap:2rem; flex-wrap:wrap;">

    <!-- Write Review -->
    <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'user'): ?>
    <div class="card" style="align-self:start;">
      <h3 style="font-size:1.1rem; margin-bottom:1.5rem;">Write a Review</h3>
      <?php if($success): ?>
        <div class="alert alert-success">✓ Review submitted!</div>
      <?php endif; ?>
      <?php if($error): ?>
        <div class="alert alert-error">⚠ <?php echo $error; ?></div>
      <?php endif; ?>
      <form method="POST">
        <div class="form-group">
          <label>Your Rating</label>
          <div style="display:flex; gap:0.5rem; margin-bottom:0.5rem;" id="starRating">
            <?php for($s=1; $s<=5; $s++): ?>
            <label style="cursor:pointer; font-size:1.8rem; color:var(--muted); transition:color 0.2s;"
              onmouseover="highlightStars(<?php echo $s; ?>)"
              onmouseout="resetStars()"
              onclick="selectStar(<?php echo $s; ?>)">★</label>
            <?php endfor; ?>
          </div>
          <input type="hidden" name="rating" id="ratingInput" value="5">
        </div>
        <div class="form-group">
          <label>Your Comment</label>
          <textarea name="comment" rows="4" placeholder="Share your experience..."></textarea>
        </div>
        <button name="submit" class="btn btn-primary" style="width:100%;">Submit Review</button>
      </form>
    </div>
    <?php else: ?>
    <div class="card" style="text-align:center; padding:2.5rem; align-self:start;">
      <div style="font-size:2rem; margin-bottom:1rem;">✍️</div>
      <h3 style="margin-bottom:0.5rem;">Want to review?</h3>
      <p style="color:var(--muted); margin-bottom:1.5rem; font-size:0.9rem;">Login to share your experience</p>
      <a href="login.php" class="btn btn-primary">Login</a>
    </div>
    <?php endif; ?>

    <!-- Reviews List -->
    <div>
      <h3 style="font-size:1.1rem; margin-bottom:1.5rem;">All Reviews</h3>
      <?php if(empty($reviews)): ?>
        <div class="card" style="text-align:center; padding:2rem; color:var(--muted);">No reviews yet.</div>
      <?php else: ?>
        <?php foreach($reviews as $r): ?>
        <div class="card" style="margin-bottom:1rem;">
          <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:0.5rem;">
            <div>
              <div style="font-weight:700; font-size:0.95rem;"><?php echo htmlspecialchars($r['full_name']); ?></div>
              <div style="color:var(--muted); font-size:0.8rem;"><?php echo $r['review_date']; ?></div>
            </div>
            <div style="color:var(--gold);">
              <?php echo str_repeat('⭐', (int)$r['rating']); ?>
              <span style="font-size:0.85rem; color:var(--muted);"><?php echo $r['rating']; ?>/5</span>
            </div>
          </div>
          <?php if($r['comment']): ?>
          <p style="color:rgba(255,255,255,0.75); font-size:0.9rem;"><?php echo htmlspecialchars($r['comment']); ?></p>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

  </div>
</div>

<script>
let selected = 5;
const labels = document.querySelectorAll('#starRating label');

function highlightStars(n){
  labels.forEach((l,i) => l.style.color = i < n ? 'var(--gold)' : 'var(--muted)');
}
function resetStars(){ highlightStars(selected); }
function selectStar(n){
  selected = n;
  document.getElementById('ratingInput').value = n;
  highlightStars(n);
}
resetStars();
</script>
</body>
</html>