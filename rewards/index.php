<?php
// ============================================
// COZY REWARDS — HUB PAGE & VOUCHER STORE
// ============================================
session_start();
require_once '../includes/db_connect.php';

$isLoggedIn = isset($_SESSION['user_id']);
$userId = $isLoggedIn ? (int)$_SESSION['user_id'] : 0;
$member = null;
$message = '';
$message_type = '';

$vouchersConfig = [
    'COZY3OFF' => [
        'name' => 'RM3 Off Any Coffee Order',
        'cost' => 50, 
        'discount' => 3.00, 
        'min_spend' => 10.00, 
        'terms' => "• Valid for RM3 off any coffee or food order with minimum spend RM10.00.\n• Applicable for both Dine-In and Takeaway Pickup orders.\n• Coupon valid for 30 days from redemption date.\n• Non-refundable and cannot be combined with other promos."
    ],
    'COZYPASTRY' => [
        'name' => 'Free Fresh Bakery Pastry (RM8 Off)',
        'cost' => 100, 
        'discount' => 8.00, 
        'min_spend' => 15.00, 
        'terms' => "• Valid for 1 complimentary fresh bakery pastry item up to RM8.00 value.\n• Requires minimum cart total of RM15.00.\n• Valid for 30 days from date of redemption.\n• Limited to 1 voucher per order transaction."
    ],
    'COZY50OFF' => [
        'name' => '50% Off Specialty Coffee',
        'cost' => 150, 
        'discount' => 12.00, 
        'min_spend' => 20.00, 
        'terms' => "• Enjoy 50% discount on any specialty beverage item (max discount RM12.00).\n• Minimum spend of RM20.00 required.\n• Valid for 30 days from redemption.\n• Cannot be combined with other promotional codes."
    ]
];

// Birthday Vouchers Config
$birthdayVouchersConfig = [
    'BDAYCAKEFREE' => [
        'name' => '🎂 Free Slice of Bakery Cake',
        'cost' => 0,
        'discount' => 12.00,
        'min_spend' => 0.00,
        'terms' => "• Complimentary slice of handcrafted bakery cake during your birthday month!\n• No minimum spend required.\n• Exclusive birthday gift for Cozy Rewards members."
    ],
    'BDAY50OFF' => [
        'name' => '🎉 50% Off Total Bill (Birthday Special)',
        'cost' => 0,
        'discount' => 25.00,
        'min_spend' => 15.00,
        'terms' => "• Enjoy 50% discount on your total order bill during your birthday month (max discount RM25.00).\n• Requires minimum spend of RM15.00.\n• Valid for 30 days during your birthday month."
    ]
];

if ($isLoggedIn) {
    $stmt = $conn->prepare("SELECT fullname, birthday, is_rewards_member, rewards_points, points, rewards_member_no, rewards_joined_at FROM users WHERE id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $member = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Check Birthday Month Status
$isBirthdayMonth = false;
if ($member && !empty($member['birthday']) && $member['birthday'] !== '0000-00-00') {
    $bMonth = date('m', strtotime($member['birthday']));
    if ($bMonth === date('m')) {
        $isBirthdayMonth = true;
    }
}

// Handle Voucher Redemption (Standard & Birthday Vouchers)
if ($isLoggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['redeem_voucher'])) {
    $voucherCode = trim($_POST['voucher_code'] ?? '');
    $isBirthdayRedeem = isset($birthdayVouchersConfig[$voucherCode]);

    if (isset($vouchersConfig[$voucherCode]) || $isBirthdayRedeem) {
        $v = $isBirthdayRedeem ? $birthdayVouchersConfig[$voucherCode] : $vouchersConfig[$voucherCode];
        
        // Check if user already claimed this birthday voucher
        $alreadyClaimed = false;
        if ($isBirthdayRedeem) {
            $chkClaim = $conn->prepare("SELECT id FROM user_vouchers WHERE user_id = ? AND voucher_code = ?");
            $chkClaim->bind_param("is", $userId, $voucherCode);
            $chkClaim->execute();
            if ($chkClaim->get_result()->num_rows > 0) {
                $alreadyClaimed = true;
            }
            $chkClaim->close();
        }

        if ($alreadyClaimed) {
            $message = "You have already claimed your birthday perk '{$v['name']}'!";
            $message_type = "error";
        } else {
            $pStmt = $conn->prepare("SELECT rewards_points FROM users WHERE id = ?");
            $pStmt->bind_param("i", $userId);
            $pStmt->execute();
            $userPoints = (int)($pStmt->get_result()->fetch_assoc()['rewards_points'] ?? 0);
            $pStmt->close();

            if ($userPoints >= $v['cost']) {
                if ($v['cost'] > 0) {
                    $deduct = $conn->prepare("UPDATE users SET rewards_points = rewards_points - ?, points = points - ? WHERE id = ?");
                    $deduct->bind_param("iii", $v['cost'], $v['cost'], $userId);
                    $deduct->execute();
                    $deduct->close();
                    
                    // Refresh member points
                    $member['rewards_points'] -= $v['cost'];
                }

                $pLog = $conn->prepare("INSERT INTO points_history (user_id, points, description) VALUES (?, ?, ?)");
                $negPoints = -$v['cost'];
                $desc = $isBirthdayRedeem ? "Claimed Birthday Perk: " . $voucherCode : "Redeemed Coupon: " . $voucherCode;
                $pLog->bind_param("iis", $userId, $negPoints, $desc);
                $pLog->execute();
                $pLog->close();

                $vIns = $conn->prepare("INSERT INTO user_vouchers (user_id, voucher_code, discount_amount, min_spend, terms, status) VALUES (?, ?, ?, ?, ?, 'ACTIVE')");
                $vIns->bind_param("isdds", $userId, $voucherCode, $v['discount'], $v['min_spend'], $v['terms']);
                $vIns->execute();
                $vIns->close();

                $message = "🎉 Successfully claimed voucher '{$v['name']}'! Use coupon code '{$voucherCode}' at checkout.";
                $message_type = "success";
            } else {
                $message = "You need at least {$v['cost']} Cozy Points to redeem this voucher.";
                $message_type = "error";
            }
        }
    }
}

// Fetch Points History Logs
$pointsLogs = [];
if ($isLoggedIn) {
    $logStmt = $conn->prepare("SELECT * FROM points_history WHERE user_id = ? ORDER BY created_at DESC LIMIT 15");
    $logStmt->bind_param("i", $userId);
    $logStmt->execute();
    $resLog = $logStmt->get_result();
    while ($r = $resLog->fetch_assoc()) {
        $pointsLogs[] = $r;
    }
    $logStmt->close();
}

// Fetch User Redeemed Vouchers for Redemption History Modal
$userVouchersList = [];
$claimedVoucherCodes = [];
if ($isLoggedIn) {
    $vStmt = $conn->prepare("SELECT * FROM user_vouchers WHERE user_id = ? ORDER BY created_at DESC");
    $vStmt->bind_param("i", $userId);
    $vStmt->execute();
    $resV = $vStmt->get_result();
    while ($r = $resV->fetch_assoc()) {
        $userVouchersList[] = $r;
        $claimedVoucherCodes[] = $r['voucher_code'];
    }
    $vStmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../style/mystyle.css">
  <link rel="stylesheet" href="../style/rewards.css">
  <title>Cozy Coffee Co. — Cozy Rewards &amp; Voucher Store</title>
</head>

<body style="background: #FAF7F2; min-height: 100vh;">
<?php 
  $activePage = 'profile';
  require_once '../includes/header_nav.php'; 
?>

<!-- SUB NAVIGATION TAB BAR (SEAMLESS WARM BACKGROUND) -->
<div style="background: rgba(249, 244, 236, 0.95); border-bottom: 1.5px solid #E8DDD0; padding: 12px 5%; box-shadow: 0 4px 12px rgba(60,42,33,0.03);">
  <div style="max-width: 960px; margin: 0 auto; display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
    <a href="../profile/index.php" style="padding: 9px 22px; border-radius: 20px; font-size: 0.88rem; font-weight: 700; background: #FFFFFF; color: #665447; border: 1.5px solid #E5D9CC; text-decoration: none;">👤 Edit My Profile</a>
    <a href="../profile/orders.php" style="padding: 9px 22px; border-radius: 20px; font-size: 0.88rem; font-weight: 700; background: #FFFFFF; color: #665447; border: 1.5px solid #E5D9CC; text-decoration: none;">📦 My Orders &amp; Live Status</a>
    <a href="index.php" style="padding: 9px 22px; border-radius: 20px; font-size: 0.88rem; font-weight: 800; background: var(--color-accent-dark); color: #ffffff; text-decoration: none; box-shadow: 0 4px 12px rgba(140,109,88,0.3);">⭐ Cozy Rewards</a>
  </div>
</div>

<div class="container" style="max-width: 1200px; margin: 30px auto; padding: 0 20px;">

  <?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $message_type; ?>" style="margin-bottom: 20px; padding: 14px 18px; border-radius: 12px; background: <?php echo $message_type === 'success' ? '#d1fae5' : '#fee2e2'; ?>; color: <?php echo $message_type === 'success' ? '#065f46' : '#991b1b'; ?>; font-weight: 700;">
      <?php echo htmlspecialchars($message); ?>
    </div>
  <?php endif; ?>

  <?php if (!$isLoggedIn): ?>

    <div class="rewards-cta-box" style="text-align: center; padding: 40px; background: #fff; border-radius: 16px; border: 1px solid var(--color-border);">
      <div class="eyebrow" style="margin-bottom: 10px;">Cozy Rewards</div>
      <h2>Log in to join Cozy Rewards</h2>
      <p style="color: #666; max-width: 500px; margin: 10px auto 20px;">Create a free Cozy Coffee Co. account (or log in) to activate your Cozy Rewards membership and start earning points on every order.</p>
      <a href="../login/index.php" class="btn btn-orange">Login</a>
      <a href="../register/index.php" class="btn btn-outline" style="margin-left: 10px;">Create an Account</a>
    </div>

  <?php else: ?>

    <!-- 2-COLUMN LAYOUT -->
    <div style="display: grid; grid-template-columns: minmax(300px, 1fr) minmax(340px, 1.4fr); gap: 28px; align-items: start;">
      
      <!-- LEFT COLUMN: TOP POINTS CARD & BOTTOM HISTORY LOG -->
      <div style="display: flex; flex-direction: column; gap: 24px;">
        
          <!-- LEFT TOP: MY COZY POINTS CARD -->
          <div class="member-card" style="background: linear-gradient(135deg, #4a2c11 0%, #78350f 100%); color: #fff; padding: 26px; border-radius: 20px; box-shadow: 0 10px 25px rgba(74,44,17,0.25);">
            <div class="card-eyebrow" style="color: #fcd34d; font-size: 0.8rem; letter-spacing: 2px; text-transform: uppercase;">COZY REWARDS VIP MEMBER</div>
            <h2 style="font-size: 1.7rem; margin: 8px 0; font-family: var(--font-heading); color: #fff;"><?php echo htmlspecialchars($member['fullname'] ?? 'Cozy Member'); ?></h2>
            
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-top: 24px; border-top: 1px solid rgba(255,255,255,0.15); padding-top: 16px;">
              <div>
                <div style="font-size: 0.78rem; opacity: 0.8;">Member No.</div>
                <div style="font-family: monospace; font-size: 1.05rem; letter-spacing: 1px; color: #fef3c7;"><?php echo htmlspecialchars(!empty($member['rewards_member_no']) ? $member['rewards_member_no'] : 'CZ-PENDING'); ?></div>
              </div>
              <div style="text-align: right;">
                <div style="font-size: 2.3rem; font-weight: 800; color: #fcd34d; line-height: 1;"><?php echo (int)($member['rewards_points'] ?? 0); ?></div>
                <div style="font-size: 0.82rem; margin-top: 4px;">Cozy Points ⭐</div>
              </div>
            </div>
          </div>

        <!-- LEFT BOTTOM: POINTS HISTORY LOG -->
        <div style="background: #ffffff; border: 1px solid var(--color-border); border-radius: 16px; padding: 22px; box-shadow: 0 4px 14px rgba(0,0,0,0.03);">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
            <h3 style="margin: 0; font-size: 1.1rem; color: var(--color-primary);">📜 Points History</h3>
            <button type="button" class="btn btn-outline btn-small" onclick="openHistoryModal()" style="font-size: 0.78rem; padding: 4px 10px;">View Full Log</button>
          </div>

          <?php if (empty($pointsLogs)): ?>
            <p style="color: #777; font-size: 0.88rem; margin: 10px 0;">No point transactions yet. Order takeaway coffee to start earning!</p>
          <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 10px;">
              <?php foreach (array_slice($pointsLogs, 0, 5) as $log): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 8px; border-bottom: 1px dashed #e8ded2; font-size: 0.85rem;">
                  <div>
                    <div style="font-weight: 600; color: #333;"><?php echo htmlspecialchars($log['description'] ?? ''); ?></div>
                    <div style="font-size: 0.75rem; color: #888;"><?php echo date('M d, Y · h:i A', strtotime($log['created_at'])); ?></div>
                  </div>
                  <span style="font-weight: 800; font-size: 0.95rem; color: <?php echo $log['points'] >= 0 ? '#059669' : '#dc2626'; ?>;">
                    <?php echo $log['points'] >= 0 ? '+' . $log['points'] : $log['points']; ?>
                  </span>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

      </div>

      <!-- RIGHT COLUMN: REDEEM VOUCHERS STORE & BIRTHDAY PERKS -->
      <div style="background: #ffffff; border: 1px solid var(--color-border); border-radius: 20px; padding: 26px; box-shadow: 0 4px 16px rgba(0,0,0,0.03);">
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
          <div>
            <h2 style="font-size: 1.4rem; margin: 0 0 4px 0; color: var(--color-primary);">🎁 Points Redemption Store</h2>
            <p style="color: #666; font-size: 0.88rem; margin: 0;">Use your Cozy Points to redeem food &amp; drink discount coupons!</p>
          </div>
          <button type="button" class="btn btn-outline btn-small" onclick="openRedemptionHistoryModal()" style="font-size: 0.8rem; padding: 6px 12px; font-weight: 700; white-space: nowrap;">
            📜 Redemption History
          </button>
        </div>

        <!-- BIRTHDAY MONTH EXCLUSIVE PERKS (IF BIRTHDAY MONTH) -->
        <?php if ($isBirthdayMonth): ?>
          <div style="background: linear-gradient(135deg, #FFF5F5 0%, #FED7D7 100%); border: 2px solid #FCA5A5; border-radius: 16px; padding: 18px; margin-bottom: 22px; box-shadow: 0 6px 16px rgba(239, 68, 68, 0.12);">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
              <span style="font-size: 1.5rem;">🎂</span>
              <div>
                <h3 style="font-family: var(--font-heading); color: #991B1B; margin: 0; font-size: 1.15rem; font-weight: 800;">Happy Birthday Month!</h3>
                <p style="color: #7F1D1D; font-size: 0.82rem; margin: 0;">Claim your 2 exclusive birthday gifts below for FREE!</p>
              </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 12px;">
              <?php foreach ($birthdayVouchersConfig as $bCode => $bv): 
                $claimed = in_array($bCode, $claimedVoucherCodes);
              ?>
                <div style="background: #FFFFFF; border: 1.5px solid #FCA5A5; border-radius: 12px; padding: 14px; display: flex; justify-content: space-between; align-items: center; gap: 10px;">
                  <div>
                    <span style="background: #EF4444; color: #FFF; padding: 2px 8px; border-radius: 4px; font-size: 0.72rem; font-weight: 800; font-family: monospace;"><?php echo $bCode; ?></span>
                    <div style="font-weight: 800; color: #2C1C14; font-size: 0.95rem; margin-top: 4px;"><?php echo htmlspecialchars($bv['name']); ?></div>
                    <div style="font-size: 0.78rem; color: #7F1D1D;"><?php echo $bv['min_spend'] > 0 ? 'Min Spend RM' . number_format($bv['min_spend'], 2) : 'No Min Spend'; ?></div>
                  </div>

                  <div>
                    <?php if ($claimed): ?>
                      <span style="background: #ECFDF5; color: #065F46; border: 1px solid #6EE7B7; font-weight: 800; font-size: 0.82rem; padding: 6px 14px; border-radius: 20px; display: inline-block;">✓ Claimed</span>
                    <?php else: ?>
                      <button type="button" class="btn btn-orange redeem-confirm-btn" 
                              data-code="<?php echo $bCode; ?>"
                              data-name="<?php echo htmlspecialchars($bv['name']); ?>"
                              data-cost="0"
                              data-terms="<?php echo htmlspecialchars($bv['terms']); ?>"
                              style="padding: 6px 14px; font-size: 0.85rem; font-weight: 800; background: #EF4444; border: none;">
                        Claim Gift 🎁
                      </button>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

        <!-- STANDARD VOUCHERS LIST -->
        <div style="display: flex; flex-direction: column; gap: 18px;">
          
          <?php foreach ($vouchersConfig as $code => $v): 
            $userPoints = (int)$member['rewards_points'];
            $canRedeem = $userPoints >= $v['cost'];
          ?>
            <div style="background: #faf6f0; border: 1px solid #e8ded2; border-radius: 14px; padding: 18px; display: flex; flex-direction: column; gap: 10px; transition: transform 0.2s ease;">
              <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 10px;">
                <div>
                  <span style="background: #8c6d58; color: #fff; padding: 2px 8px; border-radius: 4px; font-size: 0.72rem; font-weight: 700; text-transform: uppercase;"><?php echo $code; ?></span>
                  <h3 style="font-size: 1.05rem; color: var(--color-primary); margin: 6px 0 4px;"><?php echo htmlspecialchars($v['name']); ?></h3>
                </div>
                <div style="text-align: right; white-space: nowrap;">
                  <span style="font-size: 1.25rem; font-weight: 800; color: var(--color-accent-dark);"><?php echo $v['cost']; ?></span>
                  <span style="font-size: 0.78rem; color: #666; display: block;">Points</span>
                </div>
              </div>

              <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 4px;">
                <button type="button" class="btn btn-outline btn-small view-terms-btn" 
                        data-code="<?php echo $code; ?>"
                        data-name="<?php echo htmlspecialchars($v['name']); ?>"
                        data-cost="<?php echo $v['cost']; ?>"
                        data-terms="<?php echo htmlspecialchars($v['terms']); ?>"
                        style="font-size: 0.78rem; padding: 4px 10px;">T&amp;C Details ℹ️</button>

                <button type="button" class="btn btn-orange redeem-confirm-btn" 
                        data-code="<?php echo $code; ?>"
                        data-name="<?php echo htmlspecialchars($v['name']); ?>"
                        data-cost="<?php echo $v['cost']; ?>"
                        data-terms="<?php echo htmlspecialchars($v['terms']); ?>"
                        <?php echo !$canRedeem ? 'disabled style="opacity: 0.45; cursor: not-allowed;"' : ''; ?>
                        style="padding: 6px 16px; font-size: 0.88rem; font-weight: 700;">
                  <?php echo $canRedeem ? 'Redeem Coupon' : 'Need ' . $v['cost'] . ' Points'; ?>
                </button>
              </div>
            </div>
          <?php endforeach; ?>

        </div>
      </div>

    </div>

  <?php endif; ?>

</div>

<!-- CONFIRMATION & T&C MODAL -->
<div id="redeemModal" class="modal-overlay">
  <div class="modal-content" style="max-width: 480px; padding: 26px; border-radius: 16px;">
    <button class="modal-close" onclick="closeRedeemModal()">&times;</button>
    
    <div style="font-size: 2.5rem; text-align: center; margin-bottom: 10px;">🎁</div>
    <h3 id="modalVoucherName" style="color: var(--color-primary); text-align: center; margin-bottom: 6px;"></h3>
    <p style="text-align: center; color: var(--color-accent-dark); font-weight: 800; font-size: 1.1rem; margin-bottom: 16px;" id="modalVoucherCost"></p>

    <div style="background: #faf5ee; padding: 14px; border-radius: 10px; border: 1px solid #e0d5c4; font-size: 0.85rem; color: #555; line-height: 1.6; margin-bottom: 20px;">
      <strong style="color: #333; display: block; margin-bottom: 6px;">📜 Detailed Terms &amp; Conditions:</strong>
      <div id="modalTermsText" style="white-space: pre-line;"></div>
    </div>

    <div id="confirmActionArea" style="display: flex; gap: 10px;">
      <button type="button" class="btn btn-outline" onclick="closeRedeemModal()" style="flex: 1;">Cancel</button>
      <form action="" method="POST" id="redeemForm" style="flex: 1;">
        <input type="hidden" name="voucher_code" id="modalVoucherCodeInput">
        <button type="submit" name="redeem_voucher" class="btn btn-orange btn-full" style="font-weight: 700;">Yes, Claim / Redeem Now! 🎉</button>
      </form>
    </div>
  </div>
</div>

<!-- FULL POINTS HISTORY MODAL -->
<div id="historyModal" class="modal-overlay">
  <div class="modal-content" style="max-width: 520px; padding: 26px; border-radius: 16px;">
    <button class="modal-close" onclick="closeHistoryModal()">&times;</button>
    <h3 style="color: var(--color-primary); margin-bottom: 16px;">📜 Full Points Transaction History</h3>
    
    <div style="max-height: 340px; overflow-y: auto; padding-right: 6px;">
      <table style="width: 100%; border-collapse: collapse; font-size: 0.88rem;">
        <thead>
          <tr style="background: #faf5ee; text-align: left;">
            <th style="padding: 10px;">Date &amp; Time</th>
            <th style="padding: 10px;">Transaction</th>
            <th style="padding: 10px; text-align: right;">Points</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($pointsLogs as $log): ?>
            <tr style="border-bottom: 1px dashed #e5dace;">
              <td style="padding: 10px; color: #777; font-size: 0.8rem;"><?php echo date('M d, Y · h:i A', strtotime($log['created_at'])); ?></td>
              <td style="padding: 10px; font-weight: 600;"><?php echo htmlspecialchars($log['description']); ?></td>
              <td style="padding: 10px; text-align: right; font-weight: 800; color: <?php echo $log['points'] >= 0 ? '#059669' : '#dc2626'; ?>;">
                <?php echo $log['points'] >= 0 ? '+' . $log['points'] : $log['points']; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- REDEMPTION HISTORY POPUP MODAL -->
<div id="redemptionHistoryModal" class="modal-overlay">
  <div class="modal-content" style="max-width: 560px; padding: 26px; border-radius: 18px;">
    <button class="modal-close" onclick="closeRedemptionHistoryModal()">&times;</button>
    
    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 14px;">
      <span style="font-size: 1.6rem;">📜</span>
      <div>
        <h3 style="color: var(--color-primary); margin: 0; font-family: var(--font-heading);">Redemption Voucher History</h3>
        <p style="color: #666; font-size: 0.82rem; margin: 0;">Overview of all your claimed &amp; redeemed vouchers.</p>
      </div>
    </div>
    
    <?php if (empty($userVouchersList)): ?>
      <div style="text-align: center; padding: 30px; color: #888; background: #FAF7F2; border-radius: 14px;">
        <div style="font-size: 2rem; margin-bottom: 6px;">🎟️</div>
        <div>No redeemed vouchers found yet.</div>
      </div>
    <?php else: ?>
      <div style="max-height: 360px; overflow-y: auto; display: flex; flex-direction: column; gap: 10px; padding-right: 4px;">
        <?php foreach ($userVouchersList as $uv): 
          $st = strtoupper($uv['status'] ?? 'ACTIVE');
          $stBg = ($st === 'USED') ? '#F3F4F6' : '#ECFDF5';
          $stCol = ($st === 'USED') ? '#6B7280' : '#065F46';
          $stBorder = ($st === 'USED') ? '#E5E7EB' : '#6EE7B7';
        ?>
          <div style="display: flex; justify-content: space-between; align-items: center; background: #FAF7F2; border: 1px solid #E8DDD0; padding: 12px 16px; border-radius: 12px;">
            <div>
              <div style="display: flex; align-items: center; gap: 8px;">
                <span style="background: #C85A3E; color: #FFF; font-weight: 800; font-size: 0.72rem; padding: 2px 8px; border-radius: 4px; font-family: monospace;"><?php echo htmlspecialchars($uv['voucher_code']); ?></span>
                <span style="font-weight: 800; color: #2C1C14; font-size: 0.9rem;">Discount: RM <?php echo number_format($uv['discount_amount'], 2); ?></span>
              </div>
              <div style="font-size: 0.76rem; color: #7A685A; margin-top: 4px;">
                Claimed: <?php echo date('M d, Y · h:i A', strtotime($uv['created_at'])); ?>
                <?php if ($uv['min_spend'] > 0): ?>
                  · Min Spend: RM <?php echo number_format($uv['min_spend'], 2); ?>
                <?php endif; ?>
              </div>
            </div>

            <div>
              <span style="background: <?php echo $stBg; ?>; color: <?php echo $stCol; ?>; border: 1px solid <?php echo $stBorder; ?>; font-weight: 800; font-size: 0.78rem; padding: 4px 10px; border-radius: 20px;">
                <?php echo $st === 'USED' ? 'USED [USED]' : 'ACTIVE'; ?>
              </span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
  const redeemModal = document.getElementById('redeemModal');
  const historyModal = document.getElementById('historyModal');
  const redemptionHistoryModal = document.getElementById('redemptionHistoryModal');

  function openRedeemModal(code, name, cost, terms, isJustTerms = false) {
    document.getElementById('modalVoucherName').textContent = name;
    document.getElementById('modalVoucherCost').textContent = cost > 0 ? ('Cost: ' + cost + ' Cozy Points ⭐') : 'Free Birthday Gift! 🎁';
    document.getElementById('modalTermsText').textContent = terms;
    document.getElementById('modalVoucherCodeInput').value = code;

    if (isJustTerms) {
      document.getElementById('confirmActionArea').style.display = 'none';
    } else {
      document.getElementById('confirmActionArea').style.display = 'flex';
    }

    redeemModal.style.display = 'flex';
  }

  function closeRedeemModal() { redeemModal.style.display = 'none'; }
  function openHistoryModal() { historyModal.style.display = 'flex'; }
  function closeHistoryModal() { historyModal.style.display = 'none'; }
  function openRedemptionHistoryModal() { redemptionHistoryModal.style.display = 'flex'; }
  function closeRedemptionHistoryModal() { redemptionHistoryModal.style.display = 'none'; }

  document.querySelectorAll('.view-terms-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      openRedeemModal(btn.dataset.code, btn.dataset.name, btn.dataset.cost, btn.dataset.terms, true);
    });
  });

  document.querySelectorAll('.redeem-confirm-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      if (btn.hasAttribute('disabled')) return;
      openRedeemModal(btn.dataset.code, btn.dataset.name, btn.dataset.cost, btn.dataset.terms, false);
    });
  });
</script>

<?php require_once '../includes/footer.php'; ?>

</body>
</html>
