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
	<title>Cozy Coffee Co. — Staff &amp; Admin Login</title>
</head>

<body class="login-page" style="background: linear-gradient(135deg, #F9F4EC 0%, #EFE5D6 50%, #F5ECDF 100%); min-height: 100vh;">

	<main class="login-page" style="max-width: 540px; margin: 40px auto; padding: 0 20px;">
		
		<div style="text-align: center; margin-bottom: 24px;">
			<a href="../index.php" class="back-link" style="font-weight: 600;">← Back to Customer Store</a>
			<h1 style="font-size: 2.2rem; color: var(--color-primary); margin-top: 10px;">Cozy Barista Admin</h1>
			<p style="color: #666; font-size: 0.95rem;">Staff &amp; Admin Management Portal</p>
		</div>

		<form class="login-card" action="admin_login_process.php" method="POST" style="background: #ffffff; padding: 32px; border-radius: 16px; border: 1px solid var(--color-border); box-shadow: 0 8px 24px rgba(0,0,0,0.06);">

			<p class="required-message">
				<span class="required-star">*</span>
				indicates required field
			</p>

			<?php if ($errorMessage): ?>
				<p class="password-error" style="color: #dc2626; font-weight: 700; background: #fee2e2; padding: 10px; border-radius: 8px; font-size: 0.88rem; text-align: center;"><?php echo htmlspecialchars($errorMessage); ?></p>
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
				<button type="submit" class="sign-in-button" style="background: var(--color-accent-dark); font-weight: 700;">
					Sign In to Barista Admin ☕
				</button>
			</div>

		</form>

	</main>

	<script>
		const showPasswordButton = document.getElementById("showPassword");
		const passwordInput = document.getElementById("admin_password");

		showPasswordButton.addEventListener("click", function () {
			passwordInput.type = passwordInput.type === "password" ? "text" : "password";
		});
	</script>
</body>
</html>