<?php
include "config.php";

if(isset($_POST['login'])){
    $email    = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    // Check users table
    $user_result = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
    if(mysqli_num_rows($user_result) == 1){
        $row = mysqli_fetch_assoc($user_result);
        if(password_verify($password, $row['password'])){
            $_SESSION['user_id']   = $row['user_id'];
            $_SESSION['user_name'] = $row['full_name'];
            $_SESSION['role']      = 'user';
            header("Location: dashboard.php"); exit();
        }
    }

    // Admin check removed: Admins must use admin_login.php

    $error = "No account found with these credentials.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — Voyager</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include "nav.php"; ?>

<div class="form-wrapper">
  <h2>Welcome back</h2>
  <p class="subtitle">Sign in to manage your travels</p>

  <?php if(isset($error)): ?>
    <div class="alert alert-error">⚠ <?php echo $error; ?></div>
  <?php endif; ?>

  <form method="POST">
    <div class="form-group">
      <label>Email Address</label>
      <input type="email" name="email" placeholder="you@example.com" required>
    </div>
    <div class="form-group">
      <label>Password</label>
      <input type="password" name="password" placeholder="••••••••" required>
    </div>
    <button type="submit" name="login" class="btn btn-primary" style="width:100%;">Sign In</button>
  </form>

  <div class="divider">or</div>
  <p style="text-align:center; color:var(--muted); font-size:0.9rem;">
    Don't have an account? <a href="register.php">Register</a>
  </p>
</div>

</body>
</html>