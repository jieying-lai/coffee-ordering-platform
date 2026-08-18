<?php
$submitted = ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['email']));
$emailSent = $submitted ? htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8') : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../style/mystyle.css">
  <link rel="stylesheet" href="../style/login.css">
  <title>Cozy Coffee Co. — Reset Password</title>
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
      min-height: 540px;
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
      margin-top: 12px;
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
        <a href="index.php" class="back-home-link">← Back to Sign In</a>
        <span class="hero-badge-light">🔑 Password Recovery</span>
        <h1 class="hero-title-light">Reset Your Password</h1>
        <p class="hero-desc-light">Can't remember your password? Enter your registered email address and we will immediately send you a secure link to reset your account credentials.</p>

        <div class="hero-img-box">
          <img src="../images/coffee-hero.jpg" alt="Cozy Coffee Artisan Brews">
        </div>

        <ul class="hero-features-light">
          <li>🔒 <span>SSL Encrypted &amp; Secure Verification</span></li>
          <li>⏱️ <span>Instant automated email link dispatch</span></li>
          <li>💬 <span>Need help? Contact Customer Support</span></li>
        </ul>
      </div>

      <div style="margin-top: 24px; font-size: 0.82rem; color: #8A7769;">
        Remembered your credentials? <a href="index.php" style="color: #C85A3E; font-weight: 700;">Sign in to Account →</a>
      </div>
    </div>

    <!-- RIGHT FORM CONTAINER -->
    <div class="cust-auth-form-wrap">
      <?php if (!$submitted): ?>

        <div class="form-header-light">
          <h2>Request Password Reset</h2>
          <p>Please enter the email address associated with your Cozy Coffee account.</p>
        </div>

        <form action="forgotPassword.php" method="POST">

          <div class="input-group" style="margin-bottom: 12px;">
            <input
              type="email"
              id="email"
              name="email"
              placeholder=" "
              required
              autocomplete="email"
            >
            <label for="email">
              <span class="required-star">*</span> Registered Email Address
            </label>
          </div>

          <p style="font-size: 0.82rem; color: #8A7769; margin: 0 0 20px;">
            ℹ️ Make sure to use the exact email address you registered with.
          </p>

          <button type="submit" class="cust-btn-primary">
            Send Reset Instructions ✉️
          </button>

          <div style="text-align: center; margin-top: 24px; font-size: 0.9rem; color: #666;">
            <a href="index.php" style="color: #7A695D; font-weight: 700; text-decoration: none;">← Return to Sign In</a>
          </div>

        </form>

      <?php else: ?>

        <!-- STEP 2: CONFIRMATION SUCCESS MESSAGE -->
        <div class="form-header-light">
          <div style="width: 50px; height: 50px; border-radius: 50%; background: rgba(5, 150, 105, 0.12); color: #059669; font-size: 1.6rem; display: flex; align-items: center; justify-content: center; margin-bottom: 14px;">
            ✓
          </div>
          <h2>Reset Email Sent!</h2>
          <p>We've sent password reset instructions to your inbox.</p>
        </div>

        <div style="background: #FAF7F2; border-left: 4px solid #059669; padding: 16px; border-radius: 12px; margin-bottom: 20px; font-size: 0.9rem; color: #3C2A21; line-height: 1.6;">
          An automated verification link has been dispatched to <strong><?php echo $emailSent; ?></strong>. Please open the email and click <strong>Reset Password</strong> to choose a new password.
        </div>

        <div style="background: rgba(235, 226, 214, 0.4); padding: 14px 16px; border-radius: 10px; font-size: 0.84rem; color: #665447; line-height: 1.5; margin-bottom: 24px;">
          <strong>💡 Don't see the email?</strong> Check your spam or junk folder. Automated emails may occasionally be filtered by email providers.
        </div>

        <a href="index.php" class="cust-btn-primary" style="display: flex; align-items: center; justify-content: center; text-decoration: none;">
          Back to Sign In Page
        </a>

      <?php endif; ?>
    </div>

  </div>

</body>
</html>
