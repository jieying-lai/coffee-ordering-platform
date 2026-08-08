<?php
// ============================================
// COZY REWARDS — JOIN / ACTIVATE MEMBERSHIP
// Step 1: birthdate + mobile number -> Send Activation Code
// Step 2: enter the OTP (activation code) within 3 minutes
// This mirrors the IKEA Family registration flow, adapted
// for Cozy Coffee Co.
// ============================================
session_start();
require_once '../includes/db_connect.php';

// Must be logged in to join Cozy Rewards
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login/index.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT is_rewards_member FROM users WHERE id = ?");
$stmt->bind_param('i', $userId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Already a member — nothing to activate
if ($row && (int) $row['is_rewards_member'] === 1) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../style/mystyle.css">
  <link rel="stylesheet" href="../style/login.css">
  <link rel="stylesheet" href="../style/rewards.css">
  <title>Cozy Coffee Co. — Join Cozy Rewards</title>
</head>

<body class="login-page">
<main class="login-page">

  <!-- LEFT COLUMN: BRANDING -->
  <div class="login-brand-section">
    <a href="index.php" class="back-link">&larr; Back to Cozy Rewards</a>

    <h1>Activate your Cozy Rewards card</h1>
    <p class="brand-subtitle">Please fill in the information below to retrieve your activation code. Members earn points on every order, member-only discounts, and a free drink on their birthday.</p>

    <div class="brand-hero-image">
      <img src="../images/coffee-hero.jpg" alt="Cozy Coffee Co.">
    </div>
  </div>

  <!-- RIGHT COLUMN: ACTIVATION FORM -->
  <div class="login-card">

    <div class="otp-steps">
      <span class="step active" id="stepLabel1"><span class="step-dot">1</span> Your details</span>
      <span class="step-sep"></span>
      <span class="step" id="stepLabel2"><span class="step-dot">2</span> Verify OTP</span>
    </div>

    <!-- ============ STEP 1: BIRTHDATE + MOBILE NUMBER ============ -->
    <div class="otp-panel visible" id="panelStep1">
      <p class="required-message">
        <span class="required-star">*</span> indicates required field
      </p>

      <div class="input-group">
        <label for="birthdate" style="position: static; transform: none; display: block; margin-bottom: 6px;">
          <span class="required-star">*</span> Date of birth
        </label>
        <input type="date" id="birthdate" name="birthdate" required
               max="<?php echo date('Y-m-d'); ?>">
      </div>
      <p class="reset-hint" style="margin: -10px 0 16px;">Required to confirm the minimum age and to unlock your birthday reward.</p>

      <div class="input-group">
        <label for="phone" style="position: static; transform: none; display: block; margin-bottom: 6px;">
          <span class="required-star">*</span> Mobile number
        </label>
        <div style="display: flex; gap: 10px;">
          <input type="text" value="+60" disabled style="width: 70px; text-align: center; flex: none; background: var(--color-bg);">
          <input type="tel" id="phone" name="phone" placeholder="12 345 6789" required style="flex: 1;">
        </div>
      </div>

      <p class="otp-error" id="step1Error"></p>

      <div class="button-section">
        <button type="button" class="sign-in-button" id="sendCodeBtn">Send Activation Code</button>
      </div>
    </div>

    <!-- ============ STEP 2: OTP VERIFICATION ============ -->
    <div class="otp-panel" id="panelStep2">
      <div class="otp-sent-banner" id="otpBanner">
        An activation code has been sent to your mobile.
      </div>

      <div class="input-group">
        <label for="otpCode" style="position: static; transform: none; display: block; margin-bottom: 6px;">
          Enter 6-digit activation code
        </label>
        <input type="text" id="otpCode" inputmode="numeric" maxlength="6" pattern="[0-9]{6}"
               placeholder="••••••" class="otp-box" style="letter-spacing: 8px;">
      </div>

      <div class="otp-countdown">
        <span>Code expires in <span class="clock" id="otpClock">3:00</span></span>
        <button type="button" class="otp-resend-btn" id="resendBtn" disabled>Resend code</button>
      </div>

      <p class="otp-error" id="step2Error"></p>

      <div class="button-section">
        <button type="button" class="sign-in-button" id="verifyBtn">Verify &amp; Activate</button>
      </div>

      <a href="#" class="otp-back-step" id="backToStep1">&larr; Change birthdate / mobile number</a>
    </div>

  </div>

</main>

<div class="cc-modal-overlay" id="ccModalOverlay">
  <div class="cc-modal">
    <h3 class="cc-modal-title" id="ccModalTitle">Notice</h3>
    <p class="cc-modal-message" id="ccModalMessage"></p>
    <div class="cc-modal-actions">
      <button type="button" class="sign-in-button" id="ccModalOkBtn" style="width: auto; padding: 10px 28px;">OK</button>
    </div>
  </div>
</div>

<script>
  function showModal(title, message, onOk) {
    document.getElementById('ccModalTitle').textContent = title;
    document.getElementById('ccModalMessage').textContent = message;
    document.getElementById('ccModalOverlay').classList.add('cc-modal-open');
    document.getElementById('ccModalOkBtn').onclick = function () {
      document.getElementById('ccModalOverlay').classList.remove('cc-modal-open');
      if (onOk) onOk();
    };
  }

  const panelStep1 = document.getElementById('panelStep1');
  const panelStep2 = document.getElementById('panelStep2');
  const stepLabel1 = document.getElementById('stepLabel1');
  const stepLabel2 = document.getElementById('stepLabel2');

  const sendCodeBtn = document.getElementById('sendCodeBtn');
  const resendBtn   = document.getElementById('resendBtn');
  const verifyBtn   = document.getElementById('verifyBtn');
  const step1Error  = document.getElementById('step1Error');
  const step2Error  = document.getElementById('step2Error');
  const otpBanner   = document.getElementById('otpBanner');
  const otpClock    = document.getElementById('otpClock');

  let countdownTimer = null;

  function goToStep2() {
    panelStep1.classList.remove('visible');
    panelStep2.classList.add('visible');
    stepLabel1.classList.remove('active');
    stepLabel2.classList.add('active');
  }

  function goToStep1() {
    panelStep2.classList.remove('visible');
    panelStep1.classList.add('visible');
    stepLabel2.classList.remove('active');
    stepLabel1.classList.add('active');
    clearInterval(countdownTimer);
  }

  document.getElementById('backToStep1').addEventListener('click', function (e) {
    e.preventDefault();
    goToStep1();
  });

  function startCountdown(seconds) {
    clearInterval(countdownTimer);
    resendBtn.disabled = true;
    let remaining = seconds;

    function tick() {
      const m = Math.floor(remaining / 60);
      const s = remaining % 60;
      otpClock.textContent = m + ':' + String(s).padStart(2, '0');
      if (remaining <= 0) {
        clearInterval(countdownTimer);
        otpClock.textContent = '0:00';
        resendBtn.disabled = false;
      }
      remaining--;
    }
    tick();
    countdownTimer = setInterval(tick, 1000);
  }

  function requestActivationCode() {
    step1Error.textContent = '';
    const birthdate = document.getElementById('birthdate').value;
    const phone = document.getElementById('phone').value;

    if (!birthdate || !phone) {
      step1Error.textContent = 'Please fill in your date of birth and mobile number.';
      return;
    }

    sendCodeBtn.disabled = true;
    sendCodeBtn.textContent = 'Sending...';

    const formData = new FormData();
    formData.append('birthdate', birthdate);
    formData.append('phone', phone);

    fetch('send_code.php', { method: 'POST', body: formData })
      .then(res => res.json())
      .then(data => {
        sendCodeBtn.disabled = false;
        sendCodeBtn.textContent = 'Send Activation Code';

        if (data.status === 'success') {
          otpBanner.innerHTML =
            'An activation code has been sent to <strong>' + data.phone + '</strong>.' +
            '<br><span style="font-size:12px;">Demo mode — no SMS gateway configured, so your code is shown below for testing:</span>' +
            '<div class="otp-demo-code">' + data.demo_code + '</div>';
          document.getElementById('otpCode').value = '';
          step2Error.textContent = '';
          goToStep2();
          startCountdown(data.expires_in || 180);
        } else {
          step1Error.textContent = data.message;
        }
      })
      .catch(() => {
        sendCodeBtn.disabled = false;
        sendCodeBtn.textContent = 'Send Activation Code';
        step1Error.textContent = 'Network error. Please try again.';
      });
  }

  sendCodeBtn.addEventListener('click', requestActivationCode);
  resendBtn.addEventListener('click', requestActivationCode);

  verifyBtn.addEventListener('click', function () {
    step2Error.textContent = '';
    const code = document.getElementById('otpCode').value.trim();

    if (!/^[0-9]{6}$/.test(code)) {
      step2Error.textContent = 'Please enter the 6-digit code.';
      return;
    }

    verifyBtn.disabled = true;
    verifyBtn.textContent = 'Verifying...';

    const formData = new FormData();
    formData.append('code', code);

    fetch('verify_code.php', { method: 'POST', body: formData })
      .then(res => res.json())
      .then(data => {
        verifyBtn.disabled = false;
        verifyBtn.textContent = 'Verify & Activate';

        if (data.status === 'success') {
          showModal('Welcome to Cozy Rewards!', data.message, function () {
            window.location.href = data.redirect || 'index.php';
          });
        } else {
          step2Error.textContent = data.message;
          if (data.expired) {
            clearInterval(countdownTimer);
            resendBtn.disabled = false;
          }
        }
      })
      .catch(() => {
        verifyBtn.disabled = false;
        verifyBtn.textContent = 'Verify & Activate';
        step2Error.textContent = 'Network error. Please try again.';
      });
  });
</script>

</body>
</html>
