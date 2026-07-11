<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="style/mystyle.css">
	<title>Cozy Coffee Co. — Login Account</title>
</head>
	

<body>
<main class="login-page">

		<h1>Sign in or create an account</h1>

		<form class="login-card">

			<p class="required-message">
				<span class="required-star">*</span>
				indicates required field
			</p>

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
					Username or email address
				</label>
			</div>

			<div class="input-group password-group">

				<input
					type="password"
					id="password"
					name="password"
					placeholder=" "
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

			<div class="remember-section">
				<input
					type="checkbox"
					id="remember"
					name="remember"
				>

				<label for="remember">
					Keep me signed in.
				</label>

				<a href="#">Details</a>
			</div>

			<div class="help-links">
				<a href="#">Forgot your username?</a>
				<a href="#">Forgot your password?</a>
			</div>

			<div class="button-section">
				<button type="submit" class="sign-in-button">
					Sign in
				</button>
			</div>

		</form>

	</main>

	<script>
		const showPasswordButton = document.getElementById("showPassword");
		const passwordInput = document.getElementById("password");

		showPasswordButton.addEventListener("click", function () {
			if (passwordInput.type === "password") {
				passwordInput.type = "text";
			} else {
				passwordInput.type = "password";
			}
		});
	</script>
</body>
</html>
