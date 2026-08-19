<?php
// Compute relative path prefix dynamically for includes/footer.php if needed
$scriptPath = $_SERVER['SCRIPT_NAME'] ?? '';
$footerBasePrefix = './';
if (strpos($scriptPath, '/home/') !== false ||
    strpos($scriptPath, '/menu/') !== false ||
    strpos($scriptPath, '/blog/') !== false ||
    strpos($scriptPath, '/benefits/') !== false ||
    strpos($scriptPath, '/offers/') !== false ||
    strpos($scriptPath, '/activities/') !== false ||
    strpos($scriptPath, '/contact/') !== false ||
    strpos($scriptPath, '/profile/') !== false ||
    strpos($scriptPath, '/cart/') !== false ||
    strpos($scriptPath, '/checkout/') !== false ||
    strpos($scriptPath, '/rewards/') !== false ||
    strpos($scriptPath, '/details/') !== false ||
    strpos($scriptPath, '/orders/') !== false ||
    strpos($scriptPath, '/login/') !== false ||
    strpos($scriptPath, '/register/') !== false ||
    strpos($scriptPath, '/admin/') !== false) {
    $footerBasePrefix = '../';
}
?>

<style>
  .footer-policy-link {
    color: #C4B5A5;
    text-decoration: none;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.2s ease;
    display: inline-block;
  }
  .footer-policy-link:hover {
    font-weight: 800 !important;
    color: #FFFFFF !important;
    transform: translateY(-1px);
    text-shadow: 0 0 8px rgba(255, 255, 255, 0.4);
  }
</style>

<footer style="background: linear-gradient(145deg, #180F0A 0%, #110B07 100%); color: #FAF7F2; border-top: 1.5px solid rgba(200, 90, 62, 0.25); padding: 24px 5%; font-family: var(--font-body, 'Plus Jakarta Sans', sans-serif); margin-top: 60px; display: block !important; width: 100% !important; clear: both !important;">
  <div style="max-width: 1280px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; font-size: 0.86rem; color: #A09080;">
    
    <!-- LEFT: COPYRIGHT NOTICE -->
    <div style="color: #D6C7B8; font-weight: 500;">
      © 2026 Cozy Coffee Co. All rights reserved.
    </div>

    <!-- RIGHT: INTERACTIVE POLICY LINKS -->
    <div style="display: flex; align-items: center; gap: 14px;">
      <span class="footer-policy-link" onclick="openFooterPolicyModal('privacy')">Privacy Policy</span>
      <span style="color: #665447;">•</span>
      <span class="footer-policy-link" onclick="openFooterPolicyModal('terms')">Terms of Service</span>
      <span style="color: #665447;">•</span>
      <span class="footer-policy-link" onclick="openFooterPolicyModal('refund')">Refund Policy</span>
    </div>

  </div>
</footer>

<!-- FOOTER POLICY POPUP MODAL -->
<div id="footerPolicyModal" class="modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999; align-items: center; justify-content: center;">
  <div style="background: #FFFFFF; max-width: 600px; width: 92%; border-radius: 24px; padding: 30px; border: 1.5px solid #E8DDD0; position: relative; max-height: 85vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.3); font-family: var(--font-body, sans-serif);">
    
    <button type="button" onclick="closeFooterPolicyModal()" style="position: absolute; right: 18px; top: 18px; background: #FAF7F2; border: 1px solid #E5D9CC; border-radius: 50%; width: 36px; height: 36px; font-size: 1.3rem; cursor: pointer; display: flex; align-items: center; justify-content: center; color: #2C1C14; font-weight: 700;">&times;</button>
    
    <div id="footerPolicyModalContent">
      <!-- Loaded dynamically via JS -->
    </div>

  </div>
</div>

<script>
const footerPolicyTexts = {
  privacy: `
    <div style="text-align: center; margin-bottom: 20px; border-bottom: 1.5px dashed #E8DDD0; padding-bottom: 16px;">
      <span style="font-size: 2.2rem;">🔒</span>
      <h3 style="font-family: var(--font-heading, serif); color: #2C1C14; margin: 6px 0 2px; font-size: 1.45rem; font-weight: 800;">
        Privacy Policy
      </h3>
      <div style="font-size: 0.82rem; color: #7A685A; font-weight: 700;">Cozy Coffee Co. Data Protection Policy</div>
    </div>
    <div style="color: #4A3B32; font-size: 0.9rem; line-height: 1.65; display: flex; flex-direction: column; gap: 14px;">
      <p><strong>1. Data Collection & Security:</strong> We store your username, email, encrypted passwords, and transaction history solely to process orders and award Cozy Points.</p>
      <p><strong>2. Guest Live Tracker Privacy:</strong> Phone numbers of guest orders are strictly hidden on public tracking boards to protect customer identity and contact information.</p>
      <p><strong>3. Zero Data Sale:</strong> We never sell, rent, or lease your personal information or contact details to third-party advertisers or external agencies.</p>
      <p><strong>4. Account Rights:</strong> Members can update their profile information or update passwords anytime under their Account Profile page.</p>
    </div>
  `,
  terms: `
    <div style="text-align: center; margin-bottom: 20px; border-bottom: 1.5px dashed #E8DDD0; padding-bottom: 16px;">
      <span style="font-size: 2.2rem;">📜</span>
      <h3 style="font-family: var(--font-heading, serif); color: #2C1C14; margin: 6px 0 2px; font-size: 1.45rem; font-weight: 800;">
        Terms of Service
      </h3>
      <div style="font-size: 0.82rem; color: #7A685A; font-weight: 700;">Cozy Coffee Co. Store & Online Ordering Terms</div>
    </div>
    <div style="color: #4A3B32; font-size: 0.9rem; line-height: 1.65; display: flex; flex-direction: column; gap: 14px;">
      <p><strong>1. Order Placement:</strong> All coffee, food, and dessert orders placed via our web platform or dine-in QR codes are transmitted directly to our kitchen barista team.</p>
      <p><strong>2. Voucher Code Rules:</strong> Only 1 promo voucher code can be applied per checkout transaction. Minimum spend and category rules (e.g. Birthday Cake Vouchers) apply.</p>
      <p><strong>3. Cozy Rewards Points:</strong> Points earned from completed orders and BYO eco bonuses (+10 points for Tumbler / +10 points for Container) cannot be transferred or exchanged for cash.</p>
      <p><strong>4. Live Kitchen Status:</strong> Order statuses (Pending, Handcrafting, Ready) are updated live by store baristas.</p>
    </div>
  `,
  refund: `
    <div style="text-align: center; margin-bottom: 20px; border-bottom: 1.5px dashed #E8DDD0; padding-bottom: 16px;">
      <span style="font-size: 2.2rem;">☕</span>
      <h3 style="font-family: var(--font-heading, serif); color: #2C1C14; margin: 6px 0 2px; font-size: 1.45rem; font-weight: 800;">
        Refund & Quality Policy
      </h3>
      <div style="font-size: 0.82rem; color: #7A685A; font-weight: 700;">100% Barista Craftsmanship Guarantee</div>
    </div>
    <div style="color: #4A3B32; font-size: 0.9rem; line-height: 1.65; display: flex; flex-direction: column; gap: 14px;">
      <p><strong>1. Barista Quality Guarantee:</strong> If your coffee beverage or dish does not meet your expectations or customization preference (temperature, sweetness, ice), notify our counter team immediately for a complimentary remake.</p>
      <p><strong>2. Incorrect Items / Wrong Order:</strong> If an item is missing or prepared incorrectly, we will immediately provide the correct item or process a counter refund.</p>
      <p><strong>3. Online Order Cancellations:</strong> Orders in "Pending" status may be modified or cancelled by contacting store staff before preparation begins.</p>
    </div>
  `
};

function openFooterPolicyModal(policyKey) {
  const content = footerPolicyTexts[policyKey];
  if (!content) return;
  document.getElementById('footerPolicyModalContent').innerHTML = content;
  document.getElementById('footerPolicyModal').style.display = 'flex';
}

function closeFooterPolicyModal() {
  document.getElementById('footerPolicyModal').style.display = 'none';
}
</script>
