<?php
include "config.php";

if(!isset($_SESSION['user_id'])){ header("Location: login.php"); exit(); }

$trip_id = (int)($_GET['trip_id'] ?? 0);
$user_id = $_SESSION['user_id'];

$trip = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM trip WHERE trip_id='$trip_id' AND user_id='$user_id'"));

if(!$trip){ header("Location: dashboard.php"); exit(); }

$success       = false;
$error         = '';
$points_earned = 0;

if(isset($_POST['update'])){
    $trip_name  = mysqli_real_escape_string($conn, $_POST['trip_name']);
    $start_date = $_POST['start_date'];
    $end_date   = $_POST['end_date'];
    $budget     = $_POST['total_budget'];
    $status     = $_POST['trip_status'];
    $old_status = $trip['trip_status'];

    // Users can only set these statuses — block Approved and Completed
    $allowed = ['Pending', 'Ongoing', 'Cancelled'];
    if(!in_array($status, $allowed)){
        $error = "You are not allowed to set that status. Only admin can approve or complete trips.";
    } elseif($end_date < $start_date){
        $error = "End date cannot be before start date.";
    } else {
        mysqli_query($conn,
        "UPDATE trip SET
            trip_name    = '$trip_name',
            start_date   = '$start_date',
            end_date     = '$end_date',
            total_budget = '$budget',
            trip_status  = '$status'
         WHERE trip_id='$trip_id' AND user_id='$user_id'");

        $success = true;
        $trip = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT * FROM trip WHERE trip_id='$trip_id'"));
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Trip — Voyager</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include "nav.php"; ?>

<div class="form-wrapper" style="max-width:540px;">
  <h2>Edit Trip</h2>
  <p class="subtitle">Update your trip details below</p>

  <?php if($success): ?>
    <div class="alert alert-success">✓ Trip updated successfully!</div>
  <?php endif; ?>
  <?php if($error): ?>
    <div class="alert alert-error">⚠ <?php echo $error; ?></div>
  <?php endif; ?>

  <form method="POST">
    <div class="form-group">
      <label>Trip Name</label>
      <input type="text" name="trip_name" value="<?php echo htmlspecialchars($trip['trip_name']); ?>" required>
    </div>
    <div class="form-group">
      <label>Start Date</label>
      <input type="date" name="start_date" value="<?php echo $trip['start_date']; ?>" required>
    </div>
    <div class="form-group">
      <label>End Date</label>
      <input type="date" name="end_date" value="<?php echo $trip['end_date']; ?>" required>
    </div>
    <div class="form-group">
      <label>Total Budget (₹)</label>
      <input type="number" name="total_budget" value="<?php echo $trip['total_budget']; ?>" min="0" step="0.01" required>
    </div>

    <div class="form-group">
      <label>Trip Status</label>

      <?php 
      $locked = in_array($trip['trip_status'], ['Approved', 'Completed']);
      ?>

      <?php if($locked): ?>
        <!-- Show current status as read-only if Approved or Completed -->
        <div style="padding:0.85rem 1rem; background:rgba(255,255,255,0.03); border:1px solid var(--border);
          border-radius:6px; color:var(--muted); font-size:0.95rem;">
          <?php echo $trip['trip_status']; ?>
          <span style="font-size:0.78rem; margin-left:0.5rem;">
            (locked — only admin can change this)
          </span>
        </div>
        <!-- Hidden field to keep current status unchanged -->
        <input type="hidden" name="trip_status" value="<?php echo $trip['trip_status']; ?>">
        <?php if($trip['trip_status'] === 'Completed'): ?>
        <div style="color:#64c878; font-size:0.82rem; margin-top:0.5rem;">
          ✓ Trip completed! You can now <a href="review.php" style="color:var(--gold);">leave a review</a>.
        </div>
        <?php else: ?>
        <div style="color:var(--gold); font-size:0.82rem; margin-top:0.5rem;">
          ✓ Trip approved by admin. Admin will mark it Completed when done.
        </div>
        <?php endif; ?>

      <?php else: ?>
        <!-- User can only pick Pending, Ongoing, Cancelled -->
        <select name="trip_status" style="width:100%; padding:0.85rem 1rem; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:6px; color:var(--white); font-family:'Lato',sans-serif; font-size:0.95rem; outline:none;">
          <option value="Pending"  style="background:var(--deep);" <?php if($trip['trip_status']==='Pending')  echo 'selected'; ?>>Pending</option>
          <option value="Ongoing"  style="background:var(--deep);" <?php if($trip['trip_status']==='Ongoing')  echo 'selected'; ?>>Ongoing</option>
          <option value="Cancelled"style="background:var(--deep);" <?php if($trip['trip_status']==='Cancelled')echo 'selected'; ?>>Cancelled</option>
        </select>
        <div style="color:var(--muted); font-size:0.78rem; margin-top:0.4rem;">
          💡 <strong style="color:var(--gold);">Approved</strong> and <strong style="color:var(--gold);">Completed</strong> are set by admin only
        </div>
      <?php endif; ?>
    </div>

    <?php if(!$locked): ?>
    <button type="submit" name="update" class="btn btn-primary" style="width:100%;">Save Changes</button>
    <?php else: ?>
    <button type="submit" name="update" class="btn btn-primary" style="width:100%;">Save Other Details</button>
    <?php endif; ?>
  </form>

  <div style="margin-top:1.5rem; text-align:center;">
    <a href="dashboard.php" style="color:var(--muted); font-size:0.85rem;">← Back to Dashboard</a>
  </div>
</div>

</body>
</html>