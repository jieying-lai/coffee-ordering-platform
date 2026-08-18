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

// Handle Voucher Redemption
if ($isLoggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['redeem_voucher'])) {
    $voucherCode = trim($_POST['voucher_code'] ?? '');

    if (isset($vouchersConfig[$voucherCode])) {
        $v = $vouchersConfig[$voucherCode];
        $pStmt = $conn->prepare("SELECT rewards_points FROM users WHERE id = ?");
        $pStmt->bind_param("i", $userId);
        $pStmt->execute();
        $userPoints = (int)($pStmt->get_result()->fetch_assoc()['rewards_points'] ?? 0);
        $pStmt->close();

        if ($userPoints >= $v['cost']) {
            $deduct = $conn->prepare("UPDATE users SET rewards_points = rewards_points - ? WHERE id = ?");
            $deduct->bind_param("ii", $v['cost'], $userId);
            $deduct->execute();
            $deduct->close();

            $pLog = $conn->prepare("INSERT INTO points_history (user_id, points, description) VALUES (?, ?, ?)");
            $negPoints = -$v['cost'];
            $desc = "Redeemed Coupon: " . $voucherCode;
            $pLog->bind_param("iis", $userId, $negPoints, $desc);
            $pLog->execute();
            $pLog->close();

            $vIns = $conn->prepare("INSERT INTO user_vouchers (user_id, voucher_code, discount_amount, min_spend, terms) VALUES (?, ?, ?, ?, ?)");
            $vIns->bind_param("isdds", $userId, $voucherCode, $v['discount'], $v['min_spend'], $v['terms']);
            $vIns->execute();
            $vIns->close();

            $message = "🎉 Successfully redeemed coupon '{$voucherCode}'! Use it in your Cart.";
            $message_type = "success";
        } else {
            $message = "You need at least {$v['cost']} Cozy Points to redeem this voucher.";
            $message_type = "error";
        }
    }
}

if ($isLoggedIn) {
    $stmt = $conn->prepare("SELECT fullname, is_rewards_member, rewards_points, rewards_member_no, rewards_joined_at FROM users WHERE id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $member = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$pointsLogs = [];
if ($isLoggedIn) {
    $logStmt = $conn->prepare("SELECT * FROM points_history WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
    $logStmt->bind_param("i", $userId);
    $logStmt->execute();
    $resLog = $logStmt->get_result();
    while ($r = $resLog->fetch_assoc()) {
        $pointsLogs[] = $r;
    }
    $logStmt->close();
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

<body style="background: linear-gradient(135deg, #F9F4EC 0%, #EFE5D6 50%, #F5ECDF 100%); min-height: 100vh;">
<?php 
  $activePage = 'profile';
  require_once '../includes/header_nav.php'; 
?>

<div class="container" style="max-width: 1200px; margin: 30px auto; padding: 0 20px;">

  <?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $message_type; ?>" style="margin-bottom: 20px; padding: 14px 18px; border-radius: 12px; background: <?php echo $message_type === 'success' ? '#d1fae5' : '#fee2e2'; ?>; color: <?php echo $message_type === 'success' ? '#065f46' : '#991b1b'; ?>;">
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
                   <<div style="font-weight: 600; color: #333;"><?php echo htmlspecialchars($log['description'] ?? ''); ?></div>
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

      <!-- RIGHT COLUMN: REDEEM VOUCHERS STORE -->
      <div style="background: #ffffff; border: 1px solid var(--color-border); border-radius: 20px; padding: 26px; box-shadow: 0 4px 16px rgba(0,0,0,0.03);">
        <h2 style="font-size: 1.4rem; margin-bottom: 6px; color: var(--color-primary);">🎁 Points Redemption Store</h2>
        <p style="color: #666; font-size: 0.9rem; margin-bottom: 22px;">Use your Cozy Points to redeem food &amp; drink discount coupons!</p>

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
        <button type="submit" name="redeem_voucher" class="btn btn-orange btn-full" style="font-weight: 700;">Yes, Redeem Now! 🎉</button>
      </form>
    </div>
  </div>
</div>

<!-- FULL HISTORY MODAL -->
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

<script>
  const redeemModal = document.getElementById('redeemModal');
  const historyModal = document.getElementById('historyModal');

  function openRedeemModal(code, name, cost, terms, isJustTerms = false) {
    document.getElementById('modalVoucherName').textContent = name;
    document.getElementById('modalVoucherCost').textContent = 'Cost: ' + cost + ' Cozy Points ⭐';
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

</body>
</html>
