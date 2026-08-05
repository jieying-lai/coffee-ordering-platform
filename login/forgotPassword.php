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
</head>

<body class="login-page">

    <!-- 2-COLUMN MAIN WRAPPER -->
    <main class="login-page">

        <!-- LEFT COLUMN: BRANDING & TITLE -->
        <div class="login-brand-section">
            <a href="index.php" class="back-link">&larr; Back to Sign In</a>

            <h1>Reset Password</h1>

            <!-- Hero Image space (Display on laptop / Header banner on half screen) -->
            <div class="brand-hero-image">
                <img src="../images/coffee-hero.jpg" alt="Cozy Coffee Banner">
            </div>
        </div>

        <!-- RIGHT COLUMN: RESET PASSWORD CARD -->
        <?php if (!$submitted): ?>

            <!-- STEP 1: REQUEST RESET -->
            <form class="login-card" action="forgotPassword.php" method="POST">

                <p class="reset-instructions">
                    Please key in the email address you used to register to proceed.
                </p>

                <div class="input-group">
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder=" "
                        required
                    >
                    <label for="email">Email</label>
                </div>

                <p class="reset-hint">
                    ( This is the email you used to register )
                </p>

                <div class="button-section">
                    <button type="submit" class="sign-in-button reset-button">
                        Request Password Reset
                    </button>
                </div>

            </form>

        <?php else: ?>

            <!-- STEP 2: CONFIRMATION -->
            <div class="login-card">

                <p class="reset-instructions">
                    To complete the reset of your Cozy Coffee Co. account, an automated
                    email has been sent to <strong><?php echo $emailSent; ?></strong>.
                    To continue, please click "Reset Password" in that email.
                </p>

                <p class="reset-note">
                    <strong>Note:</strong> Sometimes spam filters may block automated
                    emails. If you do not find the email in your inbox, please check
                    your spam filter or junk email folder. If you still need help,
                    please contact Cozy Coffee Co. Customer Care.
                </p>

                <div class="help-links">
                    <a href="index.php">Back to Sign In</a>
                </div>

            </div>

            <!-- CUSTOM ALERT MODAL (replaces native browser alert) -->
            <div class="cc-modal-overlay" id="ccModalOverlay">
                <div class="cc-modal">
                    <h2 class="cc-modal-title">Cozy Coffee Co.</h2>
                    <p class="cc-modal-message">Request Sent to your Email !</p>
                    <div class="cc-modal-actions">
                        <button type="button" class="sign-in-button reset-button" id="ccModalOk">OK</button>
                    </div>
                </div>
            </div>

            <script>
                console.log("Request Sent to your Email !");

                const ccModalOverlay = document.getElementById("ccModalOverlay");
                const ccModalOk = document.getElementById("ccModalOk");

                ccModalOverlay.classList.add("cc-modal-open");

                ccModalOk.addEventListener("click", function () {
                    ccModalOverlay.classList.remove("cc-modal-open");
                });
            </script>

        <?php endif; ?>

    </main>

</body>
</html>
