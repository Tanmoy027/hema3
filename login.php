<?php require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $passRaw  = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT id, password FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($passRaw, $user['password'])) {
        $_SESSION['uid'] = $user['id'];
        header('Location: index.php');
        exit;
    }
    $error = 'Incorrect username or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><title>Login</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
...
<body>
<main class="container px-3">
  <div class="col-lg-4 col-md-6 mx-auto card-glass p-4">
      <div class="logo-circle mb-3">W</div>
      <h3 class="text-center text-white mb-4 fw-semibold">Sign in</h3>

      <?php if(!empty($_GET['msg'])): ?>
         <div class="alert alert-success py-2">Registration successful—login below.</div>
      <?php endif;?>
      <?php if(!empty($error)): ?>
         <div class="alert alert-danger py-2"><?=htmlspecialchars($error)?></div>
      <?php endif;?>

      <form method="post" novalidate>
        <input class="form-control mb-2"  name="username" placeholder="Username" required>
        <input class="form-control mb-3" type="password" name="password" placeholder="Password" required>
        <button class="btn btn-primary w-100 mb-2 fw-semibold">Login</button>
      </form>
      <p class="text-center small mb-0 text-white-50">
         Need an account? <a href="register.php" class="fw-medium">Register</a>
      </p>
  </div>
</main>
<link href="assets/css/style.css" rel="stylesheet">
</body>
...

</html>
