<?php
include "config.php";

if(isset($_POST['register'])){
    $name     = mysqli_real_escape_string($conn, $_POST['name']);
    $email    = mysqli_real_escape_string($conn, $_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $check = mysqli_query($conn, "SELECT * FROM admin WHERE email='$email'");
    if(mysqli_num_rows($check) > 0){
        $error = "Email already registered in the admin directory.";
    } else {
        mysqli_query($conn, "INSERT INTO admin (name, email, password, is_approved) VALUES ('$name', '$email', '$password', 0)");
        $success = "Application submitted! Please wait for a super admin to approve your account before you can log in.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Regstration — Voyager</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include "nav.php"; ?>

<div class="form-wrapper" style="animation: fadeUp 0.5s ease both;">
  <h2 style="color:var(--gold);">Staff Registration</h2>
  <p class="subtitle">Apply for administrative privileges</p>

  <?php if(isset($success)): ?>
    <div class="alert alert-success">✓ <?php echo $success; ?></div>
  <?php endif; ?>
  <?php if(isset($error)): ?>
    <div class="alert alert-error">⚠ <?php echo $error; ?></div>
  <?php endif; ?>

  <form method="POST">
    <div class="form-group">
      <label>Full Name</label>
      <input type="text" name="name" placeholder="John Doe" required>
    </div>
    <div class="form-group">
      <label>Staff Email Address</label>
      <input type="email" name="email" placeholder="john@voyager.com" required>
    </div>
    <div class="form-group">
      <label>Security Access Code</label>
      <input type="password" name="password" placeholder="••••••••" required>
    </div>
    <button type="submit" name="register" class="btn btn-primary" style="width:100%; border:1px solid var(--gold);">Submit Application</button>
  </form>

  <p style="text-align:center; color:var(--muted); font-size:0.9rem; margin-top:1.5rem;">
    Already have access? <a href="admin_login.php" style="color:var(--gold);">Log In here</a>
  </p>
</div>

</body>
</html>
