<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../style/mystyle.css">
  <link rel="stylesheet" href="../style/login.css">
  <title>Cozy Coffee Co. — Create Account</title>
  <style>
    /* Premium Light Theme Layout */
    body.cust-login-body {
      margin: 0;
      padding: 0;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, #FAF6F0 0%, #F5EDE2 50%, #FAF6F0 100%);
      font-family: var(--font-body, 'Plus Jakarta Sans', sans-serif);
      color: #3C2A21;
    }

    .cust-auth-card {
      width: 92%;
      max-width: 980px;
      margin: 40px auto;
      display: flex;
      border-radius: 24px;
      overflow: hidden;
      box-shadow: 0 20px 60px rgba(60, 42, 33, 0.12);
      border: 1px solid #EFE6DC;
      background: #FFFFFF;
      min-height: 600px;
    }

    /* Left Light Hero Panel */
    .cust-auth-hero {
      flex: 1.1;
      background: linear-gradient(145deg, #F7EFE5 0%, #EFE3D3 100%);
      padding: 44px 40px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      color: #3C2A21;
      position: relative;
    }

    .hero-badge-light {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 14px;
      background: rgba(200, 90, 62, 0.12);
      border: 1px solid rgba(200, 90, 62, 0.25);
      color: #C85A3E;
      font-size: 0.78rem;
      font-weight: 700;
      border-radius: 20px;
      letter-spacing: 0.5px;
      text-transform: uppercase;
      width: fit-content;
    }

    .hero-title-light {
      font-family: var(--font-heading, serif);
      font-size: 2.2rem;
      font-weight: 800;
      line-height: 1.25;
      color: #3C2A21;
      margin: 16px 0 12px;
    }

    .hero-desc-light {
      font-size: 0.92rem;
      color: #665447;
      line-height: 1.6;
      margin-bottom: 24px;
    }

    .hero-img-box {
      width: 100%;
      height: 170px;
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 8px 24px rgba(60, 42, 33, 0.12);
      margin-bottom: 20px;
    }

    .hero-img-box img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .hero-features-light {
      list-style: none;
      padding: 0;
      margin: 0;
      display: flex;
      flex-direction: column;
      gap: 10px;
    }

    .hero-features-light li {
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 0.88rem;
      color: #4A3B32;
      font-weight: 600;
    }

    /* Right Form Container */
    .cust-auth-form-wrap {
      flex: 1.1;
      background: #FFFFFF;
      padding: 44px 40px;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    .form-header-light h2 {
      font-size: 1.8rem;
      font-weight: 800;
      color: #3C2A21;
      margin: 0 0 6px;
    }

    .form-header-light p {
      font-size: 0.88rem;
      color: #7A695D;
      margin: 0 0 24px;
    }

    /* Chrome Autofill Repair */
    .input-group input:-webkit-autofill,
    .input-group input:-webkit-autofill:hover,
    .input-group input:-webkit-autofill:focus {
      -webkit-text-fill-color: #3C2A21 !important;
      -webkit-box-shadow: 0 0 0px 1000px #FFFFFF inset !important;
      transition: background-color 5000s ease-in-out 0s;
    }

    .input-group input {
      width: 100%;
      height: 48px;
      padding: 12px 16px;
      border: 1.5px solid #E5D9CC;
      border-radius: 12px;
      font-size: 0.95rem;
      color: #3C2A21;
      background: #FAF7F2;
      box-sizing: border-box;
      transition: all 0.2s ease;
    }

    .input-group input:focus {
      outline: none;
      border-color: #C85A3E;
      background: #FFFFFF;
      box-shadow: 0 0 0 4px rgba(200, 90, 62, 0.12);
    }

    .cust-btn-primary {
      width: 100%;
      height: 50px;
      border-radius: 12px;
      background: linear-gradient(135deg, #C85A3E 0%, #A8472F 100%);
      color: #FFFFFF;
      border: none;
      font-size: 1rem;
      font-weight: 700;
      cursor: pointer;
      box-shadow: 0 8px 20px rgba(200, 90, 62, 0.28);
      transition: all 0.2s ease;
      margin-top: 8px;
    }

    .cust-btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 12px 26px rgba(200, 90, 62, 0.38);
      background: linear-gradient(135deg, #B64C32 0%, #8C3722 100%);
    }

    .back-home-link {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      color: #7A695D;
      text-decoration: none;
      font-weight: 700;
      font-size: 0.88rem;
      transition: color 0.2s ease;
      margin-bottom: 12px;
    }
    .back-home-link:hover {
      color: #C85A3E;
    }

    @media (max-width: 840px) {
      .cust-auth-card {
        flex-direction: column;
        margin: 20px auto;
      }
      .cust-auth-hero {
        padding: 28px 24px;
      }
      .cust-auth-form-wrap {
        padding: 32px 24px;
      }
      .hero-img-box {
        height: 130px;
      }
    }
  </style>
</head>

<body class="cust-login-body">

  <div class="cust-auth-card">
    
    <!-- LEFT HERO PANEL (LIGHT LATTE THEME) -->
    <div class="cust-auth-hero">
      <div>
        <a href="../home/index.php" class="back-home-link">← Back to Store Home</a>
        <span class="hero-badge-light">🎁 Join Cozy Coffee Rewards</span>
        <h1 class="hero-title-light">Create Your Account</h1>
        <p class="hero-desc-light">Create a free account to collect points on every coffee order, redeem promotional coupons, and save your custom drink preferences!</p>

        <div class="hero-img-box">
          <img src="../images/coffee-hero.jpg" alt="Cozy Coffee Artisan Brews">
        </div>

        <ul class="hero-features-light">
          <li>✨ <span>Earn 1 point for every RM 1 spent</span></li>
          <li>🎁 <span>Redeem points for free drinks &amp; snacks</span></li>
          <li>🏷️ <span>Instant access to member promo deals</span></li>
        </ul>
      </div>

      <div style="margin-top: 24px; font-size: 0.82rem; color: #8A7769;">
        Already registered? <a href="../login/index.php" style="color: #C85A3E; font-weight: 700;">Sign in here →</a>
      </div>
    </div>

    <!-- RIGHT FORM CONTAINER -->
    <div class="cust-auth-form-wrap">
      <div class="form-header-light">
        <h2>Register Account</h2>
        <p>Fill in your details below to create your new account.</p>
      </div>

      <form action="register_process.php" method="POST">

        <div class="input-group" style="margin-bottom: 14px;">
          <input
            type="text"
            id="fullname"
            name="fullname"
            placeholder=" "
            required
            autocomplete="name"
          >
          <label for="fullname">
            <span class="required-star">*</span> Full name
          </label>
        </div>

        <div class="input-group" style="margin-bottom: 14px;">
          <input
            type="email"
            id="email"
            name="email"
            placeholder=" "
            required
            autocomplete="email"
          >
          <label for="email">
            <span class="required-star">*</span> Email address
          </label>
          <div id="regEmailHint" style="font-size: 0.78rem; margin-top: 4px; display: none;"></div>
        </div>

        <div class="input-group" style="margin-bottom: 14px;">
          <input
            type="text"
            id="username"
            name="username"
            placeholder=" "
            required
            autocomplete="username"
          >
          <label for="username">
            <span class="required-star">*</span> Username
          </label>
          <div id="regUserHint" style="font-size: 0.78rem; margin-top: 4px; display: none;"></div>
        </div>

        <div class="input-group password-group" style="margin-bottom: 14px;">
          <input
            type="password"
            id="password"
            name="password"
            placeholder=" "
            minlength="8"
            required
            autocomplete="new-password"
          >
          <label for="password">
            <span class="required-star">*</span> Password (min 8 chars)
          </label>
          <button
            type="button"
            class="show-password"
            id="showPassword"
            aria-label="Show password"
            title="Hover or click to view password"
          >🔒</button>
        </div>
        <div id="regPassHint" style="font-size: 0.78rem; margin-top: -10px; margin-bottom: 10px; display: none;"></div>

        <div class="input-group password-group" style="margin-bottom: 14px;">
          <input
            type="password"
            id="confirm_password"
            name="confirm_password"
            placeholder=" "
            minlength="8"
            required
            autocomplete="new-password"
          >
          <label for="confirm_password">
            <span class="required-star">*</span> Confirm password
          </label>
          <button
            type="button"
            class="show-password"
            id="showConfirmPassword"
            aria-label="Show confirm password"
            title="Hover or click to view password"
          >🔒</button>
        </div>
        <div id="passwordError" style="font-size: 0.78rem; margin-top: -10px; margin-bottom: 10px; display: none;"></div>

        <div style="margin: 14px 0; font-size: 0.85rem; color: #555;">
          <label style="display: flex; align-items: flex-start; gap: 8px; cursor: pointer;">
            <input type="checkbox" id="termsCheck" required style="margin-top: 3px; accent-color: #C85A3E;">
            <span>I agree to the <a href="#" style="color: #C85A3E; font-weight:700;">Terms of Service</a> and consent to personal data processing for Cozy Rewards.</span>
          </label>
        </div>

        <button type="submit" class="cust-btn-primary" id="registerBtn">
          Create Account
        </button>

        <div style="text-align: center; margin-top: 20px; font-size: 0.9rem; color: #666;">
          Already have an account? 
          <a href="../login/index.php" style="color: #C85A3E; font-weight: 800; text-decoration: none;">Sign In Here →</a>
        </div>

      </form>
    </div>

  </div>

  <script>
    function setupPasswordHoverView(buttonId, inputId) {
      const btn = document.getElementById(buttonId);
      const input = document.getElementById(inputId);
      if (!btn || !input) return;

      // Hover to view password
      btn.addEventListener("mouseenter", function () {
        input.type = "text";
        btn.textContent = "🔓";
      });

      btn.addEventListener("mouseleave", function () {
        input.type = "password";
        btn.textContent = "🔒";
      });

      // Click to toggle
      btn.addEventListener("click", function (e) {
        e.preventDefault();
        if (input.type === "password") {
          input.type = "text";
          btn.textContent = "🔓";
        } else {
          input.type = "password";
          btn.textContent = "🔒";
        }
      });
    }
    
    setupPasswordHoverView("showPassword", "password");
    setupPasswordHoverView("showConfirmPassword", "confirm_password");

    const emailInput = document.getElementById("email");
    const usernameInput = document.getElementById("username");
    const passwordInput = document.getElementById("password");
    const confirmInput = document.getElementById("confirm_password");
    const emailHint = document.getElementById("regEmailHint");
    const userHint = document.getElementById("regUserHint");
    const passHint = document.getElementById("regPassHint");
    const passError = document.getElementById("passwordError");

    emailInput?.addEventListener("input", function() {
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

    usernameInput?.addEventListener("input", function() {
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

    passwordInput?.addEventListener("input", function() {
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

    confirmInput?.addEventListener("input", function() {
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
  </script>
</body>
</html>