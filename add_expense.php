<?php
include "config.php";

// Auth check
if(!isset($_SESSION['user_id'])){ header("Location: login.php"); exit(); }

$trip_id = (int)($_GET['trip_id'] ?? 0);
$user_id = $_SESSION['user_id'];
$success = false;

// Verify trip belongs to this user
$trip = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM trip WHERE trip_id='$trip_id' AND user_id='$user_id'"));
if(!$trip){ header("Location: dashboard.php"); exit(); }

if(isset($_POST['submit'])){
    $type   = mysqli_real_escape_string($conn, $_POST['type']);
    $amount = (float)$_POST['amount'];
    $date   = mysqli_real_escape_string($conn, $_POST['date']);

    mysqli_query($conn,
    "INSERT INTO expense(trip_id,expense_type,amount,expense_date)
     VALUES('$trip_id','$type','$amount','$date')");

    mysqli_query($conn,
    "UPDATE trip SET total_expense = total_expense + $amount WHERE trip_id='$trip_id' AND user_id='$user_id'");

    $success = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add Expense — Voyager</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include "nav.php"; ?>

<div class="form-wrapper">
  <h2>Add Expense</h2>
  <p class="subtitle">Trip: <?php echo htmlspecialchars($trip['trip_name']); ?> (#<?php echo $trip_id; ?>)</p>

  <?php if($success): ?>
    <div class="alert alert-success">✓ Expense added successfully!</div>
  <?php endif; ?>

  <form method="POST">
    <div class="form-group">
      <label>Expense Type</label>
      <input type="text" name="type" placeholder="e.g. Food, Hotel, Transport" required>
    </div>
    <div class="form-group">
      <label>Amount (₹)</label>
      <input type="number" name="amount" placeholder="0.00" min="0" step="0.01" required>
    </div>
    <div class="form-group">
      <label>Date</label>
      <input type="date" name="date" required>
    </div>
    <button name="submit" class="btn btn-primary" style="width:100%;">Add Expense</button>
  </form>
</div>

</body>
</html>