<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="../style/mystyle.css">
	<link rel="stylesheet" href="../style/login.css">
	<title>Cozy Coffee Co. — Create Account</title>
</head>

<body class="login-page">

	<a href="../index.php" class="back-link">← Back to Home</a>

	<h1>Create your account</h1>

	<form class="login-card" action="register_process.php" method="POST">

		<p class="required-message">
			<span class="required-star">*</span>
			indicates required field
		</p>

		<div class="input-group">
			<input
				type="text"
				id="fullname"
				name="fullname"
				placeholder=" "
				required
			>
			<label for="fullname">
				<span class="required-star">*</span>
				Full name
			</label>
		</div>

		<div class="input-group">
			<input
				type="email"
				id="email"
				name="email"
				placeholder=" "
				required
			>
			<label for="email">
				<span class="required-star">*</span>
				Email address
			</label>
		</div>

		<div class="input-group">
			<input
				type="text"
				id="username"
				name="username"
				placeholder=" "
				required
			>
			<label for="username">
				<span class="required-star">*</span>
				Username
			</label>
		</div>

		<div class="input-group password-group">
			<input
				type="password"
				id="password"
				name="password"
				placeholder=" "
				minlength="8"
				required
			>
			<label for="password">
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

		<div class="input-group password-group">
			<input
				type="password"
				id="confirm_password"
				name="confirm_password"
				placeholder=" "
				minlength="8"
				required
			>
			<label for="confirm_password">
				<span class="required-star">*</span>
				Confirm password
			</label>
			<button
				type="button"
				class="show-password"
				id="showConfirmPassword"
				aria-label="Show confirm password"
			>
				<svg viewBox="0 0 24 24" aria-hidden="true">
					<path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"></path>
					<circle cx="12" cy="12" r="2.5"></circle>
					<line x1="4" y1="4" x2="20" y2="20"></line>
				</svg>
			</button>
		</div>

		<p class="password-error" id="passwordError" style="display:none; color:#B3261E; font-family:'Nunito',sans-serif; font-size:15px; margin-top:-20px; margin-bottom:20px;">
			Passwords do not match.
		</p>

		<div class="help-links">
			<a href="../login/index.php">Already have an account? Login</a>
		</div>

		<div class="button-section">
			<button type="submit" class="sign-in-button" id="registerBtn">
				Register
			</button>
		</div>

	</form>

	<script>
		function toggleVisibility(buttonId, inputId) {
			document.getElementById(buttonId).addEventListener("click", function () {
				const input = document.getElementById(inputId);
				input.type = input.type === "password" ? "text" : "password";
			});
		}
		toggleVisibility("showPassword", "password");
		toggleVisibility("showConfirmPassword", "confirm_password");

		// Basic client-side check — the real, secure check must still be
		// done in PHP (register_process.php) before inserting into the DB.
		const form = document.querySelector(".login-card");
		const password = document.getElementById("password");
		const confirmPassword = document.getElementById("confirm_password");
		const passwordError = document.getElementById("passwordError");

		form.addEventListener("submit", function (e) {
			if (password.value !== confirmPassword.value) {
				e.preventDefault();
				passwordError.style.display = "block";
			} else {
				passwordError.style.display = "none";
			}
		});
	</script>
</body>
</html>