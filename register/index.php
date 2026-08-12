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

    <!-- MAIN 2-COLUMN GRID WRAPPER -->
    <main class="login-page">

        <!-- LEFT COLUMN: BRANDING & TITLE -->
        <div class="login-brand-section">
            <a href="../index.php" class="back-link">&larr; Back to Home</a>
            
            <h1>Create your account</h1>
            <p class="brand-subtitle">Join us to earn points, customize your brews, and save your favorite coffee picks!</p>

            <!-- Hero Image Container -->
            <div class="brand-hero-image">
                <img src="../images/coffee-hero.jpg" alt="Cozy Coffee Banner">
            </div>
        </div>

        <!-- RIGHT COLUMN: REGISTER FORM CARD -->
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
                <div id="regEmailHint" style="font-size: 0.78rem; margin-top: 4px; display: none;"></div>
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
                <div id="regUserHint" style="font-size: 0.78rem; margin-top: 4px; display: none;"></div>
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
                    Password (min 8 chars)
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
            <div id="regPassHint" style="font-size: 0.78rem; margin-top: -10px; margin-bottom: 12px; display: none;"></div>

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
            <div id="passwordError" style="font-size: 0.78rem; margin-top: -10px; margin-bottom: 12px; display: none;"></div>

            <!-- TERMS CONSENT CHECKBOX -->
            <div style="margin: 14px 0; font-size: 0.85rem; color: #555;">
              <label style="display: flex; align-items: flex-start; gap: 8px; cursor: pointer;">
                <input type="checkbox" id="termsCheck" required style="margin-top: 3px;">
                <span>I agree to the <a href="#" style="color: var(--color-accent-dark); font-weight:700;">Terms of Service</a> and consent to personal data processing for Cozy Rewards.</span>
              </label>
            </div>

            <div class="help-links">
                <a href="../login/index.php">Already have an account? Login</a>
            </div>

            <div class="button-section">
                <button type="submit" class="sign-in-button" id="registerBtn">
                    Create Account
                </button>
            </div>

        </form>

    </main>

    <script>
        function toggleVisibility(buttonId, inputId) {
            const btn = document.getElementById(buttonId);
            const input = document.getElementById(inputId);
            btn.addEventListener("click", function () {
                input.type = input.type === "password" ? "text" : "password";
            });
        }
        
        toggleVisibility("showPassword", "password");
        toggleVisibility("showConfirmPassword", "confirm_password");

        const form = document.querySelector(".login-card");
        const emailInput = document.getElementById("email");
        const usernameInput = document.getElementById("username");
        const passwordInput = document.getElementById("password");
        const confirmInput = document.getElementById("confirm_password");
        const emailHint = document.getElementById("regEmailHint");
        const userHint = document.getElementById("regUserHint");
        const passHint = document.getElementById("regPassHint");
        const passError = document.getElementById("passwordError");

        // Live typing validators
        emailInput.addEventListener("input", function() {
            const val = this.value.trim();
            emailHint.style.display = "block";
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (val.length === 0) {
                emailHint.style.display = "none";
            } else if (!emailRegex.test(val)) {
                emailHint.style.color = "#dc2626";
                emailHint.textContent = "✖ Please enter a valid email address (e.g. name@domain.com)";
            } else {
                emailHint.style.color = "#059669";
                emailHint.textContent = "✓ Valid email format";
            }
        });

        usernameInput.addEventListener("input", function() {
            const val = this.value.trim();
            userHint.style.display = "block";
            if (val.length === 0) {
                userHint.style.display = "none";
            } else if (val.length < 3) {
                userHint.style.color = "#dc2626";
                userHint.textContent = "✖ Username must be at least 3 characters";
            } else {
                userHint.style.color = "#059669";
                userHint.textContent = "✓ Username available";
            }
        });

        passwordInput.addEventListener("input", function() {
            const val = this.value;
            passHint.style.display = "block";
            if (val.length === 0) {
                passHint.style.display = "none";
            } else if (val.length < 8) {
                passHint.style.color = "#dc2626";
                passHint.textContent = "✖ Password must be at least 8 characters";
            } else {
                passHint.style.color = "#059669";
                passHint.textContent = "✓ Strong password length";
            }
        });

        confirmInput.addEventListener("input", function() {
            passError.style.display = "block";
            if (this.value === 0 || this.value === "") {
                passError.style.display = "none";
            } else if (this.value !== passwordInput.value) {
                passError.style.color = "#dc2626";
                passError.textContent = "✖ Passwords do not match";
            } else {
                passError.style.color = "#059669";
                passError.textContent = "✓ Passwords match!";
            }
        });

        form.addEventListener("submit", function (e) {
            if (passwordInput.value !== confirmInput.value) {
                e.preventDefault();
                passError.style.display = "block";
                passError.style.color = "#dc2626";
                passError.textContent = "✖ Passwords do not match";
                confirmInput.focus();
            }
        });
    </script>
</body>
</html>