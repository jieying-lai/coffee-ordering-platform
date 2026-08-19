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
  <title>Cozy Coffee Co. — Staff &amp; Admin Portal</title>
  <style>
    /* Premium Dark Ambient Page Layout */
    .input-group, .password-group {
      position: relative !important;
    }
    .password-group input, input[type="password"] {
      padding-right: 48px !important;
    }
    .show-password {
      position: absolute !important;
      top: 50% !important;
      right: 12px !important;
      transform: translateY(-50%) !important;
      background: transparent !important;
      border: none !important;
      cursor: pointer !important;
      font-size: 1.2rem !important;
      padding: 4px !important;
      color: #7A695D !important;
      z-index: 10 !important;
    }

    body.admin-login-body {
      margin: 0;
      padding: 0;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: radial-gradient(circle at 20% 20%, #3C2A21 0%, #1C120C 60%, #120A06 100%);
      font-family: var(--font-body, 'Plus Jakarta Sans', sans-serif);
      color: #FAF7F2;
      box-sizing: border-box;
    }

    /* Outer Wrapper Card */
    .admin-login-card {
      width: 92%;
      max-width: 960px;
      margin: 40px auto;
      display: flex;
      border-radius: 24px;
      overflow: hidden;
      box-shadow: 0 30px 80px rgba(0, 0, 0, 0.55);
      border: 1px solid rgba(255, 255, 255, 0.1);
      background: #ffffff;
      min-height: 540px;
    }

    /* Left Hero Panel (Espresso Ambient Theme) */
    .admin-login-hero {
      flex: 1.1;
      background: linear-gradient(145deg, #2D1E17 0%, #1F130E 60%, #160D09 100%);
      padding: 48px 40px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      color: #F7EFE5;
      position: relative;
      overflow: hidden;
    }

    .admin-login-hero::before {
      content: "";
      position: absolute;
      top: -40px;
      left: -40px;
      width: 220px;
      height: 220px;
      background: radial-gradient(circle, rgba(200, 90, 62, 0.25) 0%, rgba(0,0,0,0) 70%);
      border-radius: 50%;
      pointer-events: none;
    }

    .hero-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 14px;
      background: rgba(200, 90, 62, 0.2);
      border: 1px solid rgba(200, 90, 62, 0.4);
      color: #F2A97B;
      font-size: 0.78rem;
      font-weight: 700;
      border-radius: 20px;
      letter-spacing: 0.5px;
      text-transform: uppercase;
      width: fit-content;
    }

    .hero-title {
      font-family: var(--font-heading, serif);
      font-size: 2.2rem;
      font-weight: 800;
      line-height: 1.2;
      color: #FFFFFF;
      margin: 16px 0 12px;
    }

    .hero-desc {
      font-size: 0.92rem;
      color: #D1C2B4;
      line-height: 1.6;
      margin-bottom: 28px;
    }

    .hero-features {
      list-style: none;
      padding: 0;
      margin: 0;
      display: flex;
      flex-direction: column;
      gap: 14px;
    }

    .hero-features li {
      display: flex;
      align-items: center;
      gap: 12px;
      font-size: 0.88rem;
      color: #E6D9CD;
      font-weight: 600;
    }

    .hero-features .feat-icon {
      width: 32px;
      height: 32px;
      border-radius: 8px;
      background: rgba(255, 255, 255, 0.08);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.1rem;
    }

    .hero-footer {
      font-size: 0.8rem;
      color: #8C786A;
      border-top: 1px solid rgba(255, 255, 255, 0.08);
      padding-top: 20px;
      margin-top: 30px;
    }

    /* Right Form Container */
    .admin-login-form-wrap {
      flex: 1;
      background: #FFFFFF;
      padding: 48px 44px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      color: #3C2A21;
    }

    .form-header h2 {
      font-size: 1.8rem;
      font-weight: 800;
      color: var(--color-primary, #3C2A21);
      margin: 0 0 6px;
    }

    .form-header p {
      font-size: 0.88rem;
      color: #7A695D;
      margin: 0 0 28px;
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
      height: 52px;
      padding: 14px 16px;
      border: 1px solid #E2D7CB;
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

    .sign-in-btn {
      width: 100%;
      height: 52px;
      border-radius: 12px;
      background: linear-gradient(135deg, #8C6D58 0%, #C85A3E 100%);
      color: #FFFFFF;
      border: none;
      font-size: 1rem;
      font-weight: 700;
      cursor: pointer;
      box-shadow: 0 8px 20px rgba(200, 90, 62, 0.28);
      transition: all 0.2s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      margin-top: 10px;
    }

    .sign-in-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 12px 26px rgba(200, 90, 62, 0.38);
      background: linear-gradient(135deg, #7A5C47 0%, #B64C32 100%);
    }

    .back-store-link {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      color: #8C6D58;
      text-decoration: none;
      font-weight: 700;
      font-size: 0.88rem;
      margin-top: 24px;
      transition: color 0.2s ease;
    }
    .back-store-link:hover {
      color: #C85A3E;
    }

    @media (max-width: 840px) {
      .admin-login-card {
        flex-direction: column;
        margin: 20px auto;
      }
      .admin-login-hero {
        padding: 32px 24px;
      }
      .admin-login-form-wrap {
        padding: 32px 24px;
      }
    }
  </style>
</head>

<body class="admin-login-body">

  <div class="admin-login-card">
    
    <!-- LEFT HERO PANEL -->
    <div class="admin-login-hero">
      <div>
        <span class="hero-badge">🔒 Staff Management Portal</span>
        <h1 class="hero-title">Cozy Barista Admin</h1>
        <p class="hero-desc">Access the central operations hub for Cozy Coffee Co. Manage live orders, customize coffee menus, respond to customer care chats, and track store activity.</p>
        
        <ul class="hero-features">
          <li>
            <div class="feat-icon">⚡</div>
            <span>Real-Time Customer Order Monitoring</span>
          </li>
          <li>
            <div class="feat-icon">💬</div>
            <span>Barista Live Customer Care Chat</span>
          </li>
          <li>
            <div class="feat-icon">☕</div>
            <span>Instant Stock &amp; Price Adjustments</span>
          </li>
        </ul>
      </div>

      <div class="hero-footer">
        © <?php echo date('Y'); ?> Cozy Coffee Co. — Authorised Staff Personnel Only.
      </div>
    </div>

    <!-- RIGHT LOGIN FORM -->
    <div class="admin-login-form-wrap">
      <div class="form-header">
        <h2>Sign In to Admin</h2>
        <p>Please enter your staff credentials to access the portal.</p>
      </div>

      <?php if ($errorMessage): ?>
        <div style="color: #dc2626; font-weight: 700; background: #fee2e2; border-left: 4px solid #dc2626; padding: 12px 14px; border-radius: 8px; font-size: 0.88rem; margin-bottom: 20px;">
          ⚠️ <?php echo htmlspecialchars($errorMessage); ?>
        </div>
      <?php endif; ?>

      <form action="admin_login_process.php" method="POST">

        <div class="input-group" style="margin-bottom: 18px;">
          <input
            type="text"
            id="admin_username"
            name="admin_username"
            placeholder=" "
            required
            autocomplete="username"
          >
          <label for="admin_username">
            <span class="required-star">*</span> Admin Username
          </label>
        </div>

        <div class="input-group password-group" style="margin-bottom: 22px;">
          <input
            type="password"
            id="admin_password"
            name="admin_password"
            placeholder=" "
            required
            autocomplete="current-password"
          >
          <label for="admin_password">
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

        <button type="submit" class="sign-in-btn">
          Sign In to Barista Admin ☕
        </button>

        <div style="text-align: center;">
          <a href="../login/index.php" class="back-store-link">
            ← Return
          </a>
        </div>

      </form>

    </div>

  </div>

  <script>
    const showPasswordButton = document.getElementById("showPassword");
    const passwordInput = document.getElementById("admin_password");

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