<?php
include "config.php";

if(isset($_POST['login'])){
    $email    = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    $admin_result = mysqli_query($conn, "SELECT * FROM admin WHERE email='$email'");
    if(mysqli_num_rows($admin_result) == 1){
        $row = mysqli_fetch_assoc($admin_result);
        if(password_verify($password, $row['password'])){
            if($row['is_approved'] == 1) {
                $_SESSION['admin_id']   = $row['admin_id'];
                $_SESSION['admin_name'] = $row['name'];
                $_SESSION['role']       = 'admin';
                header("Location: admin_dashboard.php"); exit();
            } else {
                $error = "Your admin account is pending approval by a super admin.";
            }
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "No admin account found with these credentials.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login — Voyager</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include "nav.php"; ?>

<div class="form-wrapper" style="animation: fadeUp 0.5s ease both;">
  <h2 style="color:var(--gold);">Admin Portal</h2>
  <p class="subtitle">Secure access for Voyager staff</p>

  <?php if(isset($error)): ?>
    <div class="alert alert-error">⚠ <?php echo $error; ?></div>
  <?php endif; ?>

  <form method="POST">
    <div class="form-group">
      <label>Staff Email</label>
      <input type="email" name="email" placeholder="admin@voyager.com" required>
    </div>
    <div class="form-group">
      <label>Access Code</label>
      <input type="password" name="password" placeholder="••••••••" required>
    </div>
    <button type="submit" name="login" class="btn btn-primary" style="width:100%; background:linear-gradient(90deg,var(--gold),var(--gold-light)); color:var(--deep); border:none;">Secure Login</button>
  </form>

  <div class="divider">or</div>
  <p style="text-align:center; color:var(--muted); font-size:0.9rem;">
    New staff member? <a href="admin_register.php" style="color:var(--gold);">Apply for access</a>
  </p>
</div>

</body>
</html>
