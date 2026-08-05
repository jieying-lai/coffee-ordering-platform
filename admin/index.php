<?php
$error = $_GET['error'] ?? '';
$errorMessage = '';
if ($error === 'invalid') {
    $errorMessage = 'Incorrect username or password.';
} elseif ($error === 'empty') {
    $errorMessage = 'Please fill in both fields.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="../style/mystyle.css">
	<link rel="stylesheet" href="../style/login.css">
	<title>Cozy Coffee Co. — Admin Login</title>
</head>

<body class="login-page">

	<a href="../index.php" class="back-link">← Back to Home</a>

	<h1>Admin Login</h1>

	<form class="login-card" action="admin_login_process.php" method="POST">

		<p class="required-message">
			<span class="required-star">*</span>
			indicates required field
		</p>

		<?php if ($errorMessage): ?>
			<p class="password-error"><?php echo htmlspecialchars($errorMessage); ?></p>
		<?php endif; ?>

		<div class="input-group">
			<input
				type="text"
				id="admin_username"
				name="admin_username"
				placeholder=" "
				required
			>
			<label for="admin_username">
				<span class="required-star">*</span>
				Admin username
			</label>
		</div>

		<div class="input-group password-group">
			<input
				type="password"
				id="admin_password"
				name="admin_password"
				placeholder=" "
				required
			>
			<label for="admin_password">
				<span class="required-star">*</span>
				Password
			</label>

			<button
				type="button"
				class="show-password"
				id="showPassword"
				aria-label="Show password"
			>
				<svg viewBox="0 0 24 24" aria-hidden="true">
					<path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"></path>
					<circle cx="12" cy="12" r="2.5"></circle>
					<line x1="4" y1="4" x2="20" y2="20"></line>
				</svg>
			</button>
		</div>

		<div class="button-section">
			<button type="submit" class="sign-in-button">
				Sign In
			</button>
		</div>

	</form>

	<script>
		const showPasswordButton = document.getElementById("showPassword");
		const passwordInput = document.getElementById("admin_password");

		showPasswordButton.addEventListener("click", function () {
			passwordInput.type = passwordInput.type === "password" ? "text" : "password";
		});
	</script>
</body>
</html>