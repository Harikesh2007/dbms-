<?php
include "config.php";

if(isset($_POST['register'])){
    $name     = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email    = mysqli_real_escape_string($conn, $_POST['email']);
    $hashed   = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $check = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
    if(mysqli_num_rows($check) > 0){
        $error = "This email is already registered.";
    } else {
        $insert = "INSERT INTO users (full_name, email, password) VALUES ('$name', '$email', '$hashed')";
        if(mysqli_query($conn, $insert)){
            header("Location: login.php"); exit();
        } else {
            $error = "Registration failed. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register — Voyager</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include "nav.php"; ?>

<div class="form-wrapper">
  <h2>Create account</h2>
  <p class="subtitle">Start planning your next adventure</p>

  <?php if(isset($error)): ?>
    <div class="alert alert-error">⚠ <?php echo $error; ?></div>
  <?php endif; ?>

  <form method="POST">
    <div class="form-group">
      <label>Full Name</label>
      <input type="text" name="full_name" placeholder="John Doe" required>
    </div>
    <div class="form-group">
      <label>Email Address</label>
      <input type="email" name="email" placeholder="you@example.com" required>
    </div>
    <div class="form-group">
      <label>Password</label>
      <input type="password" name="password" placeholder="Create a strong password" required>
    </div>
    <button type="submit" name="register" class="btn btn-primary" style="width:100%;">Create Account</button>
  </form>

  <div class="divider">or</div>
  <p style="text-align:center; color:var(--muted); font-size:0.9rem;">
    Already have an account? <a href="login.php">Login</a>
  </p>
</div>

</body>
</html>
