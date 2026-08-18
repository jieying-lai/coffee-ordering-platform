<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../style/mystyle.css">
  <link rel="stylesheet" href="../style/login.css">
  <title>Cozy Coffee Co. — Customer Login</title>
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
      min-height: 560px;
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
      height: 180px;
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
      flex: 1;
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
      height: 50px;
      padding: 14px 16px;
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
        height: 140px;
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
        <span class="hero-badge-light">☕ Cozy Coffee Rewards</span>
        <h1 class="hero-title-light">Welcome back to Cozy Coffee</h1>
        <p class="hero-desc-light">Sign in to your account to track your orders, earn Cozy Points, unlock member-only drink deals, and manage your saved profile.</p>

        <div class="hero-img-box">
          <img src="../images/coffee-hero.jpg" alt="Cozy Coffee Artisan Brews">
        </div>

        <ul class="hero-features-light">
          <li>✨ <span>Earn Cozy Points on every purchase</span></li>
          <li>🏷️ <span>Access member-only 15% OFF deals</span></li>
          <li>💬 <span>Live customer care barista support</span></li>
        </ul>
      </div>

      <div style="margin-top: 24px; font-size: 0.82rem; color: #8A7769;">
        Staff or Admin personnel? <a href="../admin/index.php" style="color: #C85A3E; font-weight: 700;">Login to Barista Admin →</a>
      </div>
    </div>

    <!-- RIGHT FORM CONTAINER -->
    <div class="cust-auth-form-wrap">
      <div class="form-header-light">
        <h2>Sign In</h2>
        <p>Enter your username or email address to access your account.</p>
      </div>

      <form action="login_process.php" method="POST">

        <div class="input-group" style="margin-bottom: 18px;">
          <input
            type="text"
            id="username"
            name="username"
            placeholder=" "
            required
            autocomplete="username"
          >
          <label for="username">
            <span class="required-star">*</span> Username or email address
          </label>
        </div>

        <div class="input-group password-group" style="margin-bottom: 14px;">
          <input
            type="password"
            id="password"
            name="password"
            placeholder=" "
            required
            autocomplete="current-password"
          >
          <label for="password">
            <span class="required-star">*</span> Password
          </label>

          <button
            type="button"
            class="show-password"
            id="showPassword"
            aria-label="Show password"
            title="Hover or click to view password"
          >🔒</button>
        </div>

        <div class="remember-section" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; font-size: 0.88rem;">
          <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; color: #555;">
            <input type="checkbox" id="remember" name="remember" style="accent-color: #C85A3E;">
            Keep me signed in
          </label>
          <a href="forgotPassword.php" style="color: #C85A3E; text-decoration: none; font-weight: 600;">Forgot password?</a>
        </div>

        <button type="submit" class="cust-btn-primary" id="signInBtn">
          Sign In to My Account
        </button>

        <div style="text-align: center; margin-top: 24px; font-size: 0.9rem; color: #666;">
          Don't have an account yet? 
          <a href="../register/index.php" style="color: #C85A3E; font-weight: 800; text-decoration: none;">Register Now →</a>
        </div>

      </form>
    </div>

  </div>

  <script>
    const showPasswordButton = document.getElementById("showPassword");
    const passwordInput = document.getElementById("password");

    if (showPasswordButton && passwordInput) {
      // Hover to view password
      showPasswordButton.addEventListener("mouseenter", function () {
        passwordInput.type = "text";
        showPasswordButton.textContent = "🔓";
      });

      showPasswordButton.addEventListener("mouseleave", function () {
        passwordInput.type = "password";
        showPasswordButton.textContent = "🔒";
      });

      // Click to toggle
      showPasswordButton.addEventListener("click", function (e) {
        e.preventDefault();
        if (passwordInput.type === "password") {
          passwordInput.type = "text";
          showPasswordButton.textContent = "🔓";
        } else {
          passwordInput.type = "password";
          showPasswordButton.textContent = "🔒";
        }
      });
    }
  </script>
</body>
</html>