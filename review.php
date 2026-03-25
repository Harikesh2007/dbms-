<?php
include "config.php";

if(!isset($_SESSION['user_id'])){ header("Location: login.php"); exit(); }

$user_id = $_SESSION['user_id'];
$success = false;
$error   = '';

// Simply check if user has ANY completed trip
$completed = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS cnt FROM trip
     WHERE user_id='$user_id' AND trip_status='Completed'"));
$has_completed = $completed['cnt'] > 0;

// Handle form submission
if(isset($_POST['submit']) && $has_completed){
    $destination = (int)$_POST['destination'];
    $rating      = (int)$_POST['rating'];
    $comment     = mysqli_real_escape_string($conn, $_POST['comment']);

    // Check duplicate
    $dup = mysqli_query($conn,
        "SELECT review_id FROM review
         WHERE user_id='$user_id' AND destination_id='$destination'");
    if(mysqli_num_rows($dup) > 0){
        $error = "You already reviewed this destination.";
    } else {
        $q = mysqli_query($conn,
            "INSERT INTO review(user_id, destination_id, rating, comment, review_date)
             VALUES('$user_id','$destination','$rating','$comment',CURDATE())");
        if($q){
            $success = true;
        } else {
            $error = "Database error: " . mysqli_error($conn);
        }
    }
}

// ALL destinations for dropdown
$destinations = mysqli_fetch_all(mysqli_query($conn,
    "SELECT destination_id, destination_name, city, country
     FROM destination ORDER BY destination_name ASC"), MYSQLI_ASSOC);

// All reviews for display
$reviews = mysqli_fetch_all(mysqli_query($conn,
    "SELECT r.*, u.full_name, d.destination_name, d.city
     FROM review r
     JOIN users u ON r.user_id = u.user_id
     JOIN destination d ON r.destination_id = d.destination_id
     ORDER BY r.review_date DESC LIMIT 20"), MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reviews — Voyager</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include "nav.php"; ?>

<div class="section">
  <div class="section-title">Destination Reviews</div>
  <p class="section-subtitle">Share your travel experience</p>

  <div style="display:grid; grid-template-columns:1fr 1.5fr; gap:2rem; align-items:start;">

    <!-- Form -->
    <div class="card" style="position:sticky; top:100px;">
      <h3 style="font-size:1.2rem; margin-bottom:1.5rem;">✍️ Write a Review</h3>

      <?php if($success): ?>
        <div class="alert alert-success">✓ Review submitted!</div>

      <?php elseif(!$has_completed): ?>
        <div style="text-align:center; padding:1.5rem 0;">
          <div style="font-size:3rem; margin-bottom:1rem;">🔒</div>
          <p style="color:var(--muted); font-size:0.9rem; line-height:1.7; margin-bottom:1.5rem;">
            You need at least one <strong style="color:var(--gold);">Completed</strong> trip to leave a review.<br><br>
            Go to your dashboard → click <strong>Edit</strong> on any trip → set status to <strong>Completed</strong> → Save.
          </p>
          <a href="dashboard.php" class="btn btn-primary" style="font-size:0.85rem;">Go to Dashboard</a>
        </div>

      <?php else: ?>
        <?php if($error): ?>
          <div class="alert alert-error">⚠ <?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
          <div class="form-group">
            <label>Destination</label>
            <select name="destination" required style="width:100%; padding:0.85rem 1rem; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:6px; color:var(--white); font-family:'Lato',sans-serif; font-size:0.95rem; outline:none;">
              <option value="" style="background:var(--deep);">-- Select destination --</option>
              <?php foreach($destinations as $d): ?>
              <option value="<?php echo $d['destination_id']; ?>" style="background:var(--deep);">
                <?php
                  echo htmlspecialchars($d['destination_name']);
                  if(!empty($d['city'])) echo ' — ' . htmlspecialchars($d['city']);
                  if(!empty($d['country'])) echo ', ' . htmlspecialchars($d['country']);
                ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label>Your Rating</label>
            <div style="display:flex; gap:0.5rem; margin-bottom:0.5rem;" id="starRating">
              <?php for($s=1; $s<=5; $s++): ?>
              <label style="cursor:pointer; font-size:2rem; color:var(--muted); transition:color 0.2s;"
                onmouseover="highlightStars(<?php echo $s; ?>)"
                onmouseout="resetStars()"
                onclick="selectStar(<?php echo $s; ?>)">★</label>
              <?php endfor; ?>
            </div>
            <input type="hidden" name="rating" id="ratingInput" value="5">
          </div>

          <div class="form-group">
            <label>Your Comment</label>
            <textarea name="comment" rows="4" placeholder="Tell us about your experience..."></textarea>
          </div>

          <button name="submit" class="btn btn-primary" style="width:100%;">Submit Review</button>
        </form>
      <?php endif; ?>
    </div>

    <!-- Reviews List -->
    <div>
      <h3 style="font-size:1.2rem; margin-bottom:1.5rem;">Recent Reviews</h3>
      <?php if(empty($reviews)): ?>
        <div class="card" style="text-align:center; padding:2.5rem; color:var(--muted);">
          No reviews yet — be the first!
        </div>
      <?php else: ?>
        <?php foreach($reviews as $r): ?>
        <div class="card" style="margin-bottom:1rem;">
          <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:0.75rem;">
            <div>
              <div style="font-weight:700;"><?php echo htmlspecialchars($r['full_name']); ?></div>
              <div style="color:var(--gold); font-size:0.88rem; margin-top:0.2rem;">
                📍 <?php echo htmlspecialchars($r['destination_name']); ?>
                <?php if(!empty($r['city'])): ?>, <?php echo htmlspecialchars($r['city']); ?><?php endif; ?>
              </div>
              <div style="color:var(--muted); font-size:0.78rem;"><?php echo $r['review_date']; ?></div>
            </div>
            <div style="text-align:right;">
              <div style="color:var(--gold);"><?php echo str_repeat('⭐',(int)$r['rating']); ?></div>
              <div style="color:var(--muted); font-size:0.8rem;"><?php echo $r['rating']; ?>/5</div>
            </div>
          </div>
          <?php if(!empty($r['comment'])): ?>
          <p style="color:rgba(255,255,255,0.7); font-size:0.9rem; line-height:1.6;">
            <?php echo htmlspecialchars($r['comment']); ?>
          </p>
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
function highlightStars(n){ labels.forEach((l,i) => l.style.color = i < n ? 'var(--gold)' : 'var(--muted)'); }
function resetStars(){ highlightStars(selected); }
function selectStar(n){ selected = n; document.getElementById('ratingInput').value = n; highlightStars(n); }
resetStars();
</script>
</body>
</html>