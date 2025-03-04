<?php
session_start();
if (!isset($_SESSION['email'])) {
  header("Location: login.php");
  exit();
}

$userEmail = $_SESSION['email'];
$nickname = "";
$avatar = "images/default-avatar.png";

// Retrieve nickname from nicknames.json
$nicknamesFile = 'nicknames.json';
if (file_exists($nicknamesFile)) {
  $nicknames = json_decode(file_get_contents($nicknamesFile), true);
  if (is_array($nicknames)) {
    foreach ($nicknames as $entry) {
      if ($entry['email'] === $userEmail) {
        $nickname = $entry['nickname'];
        break;
      }
    }
  }
}

// Retrieve avatar from avatars.json
$avatarFile = 'avatars.json';
if (file_exists($avatarFile)) {
  $avatars = json_decode(file_get_contents($avatarFile), true);
  if (is_array($avatars)) {
    foreach ($avatars as $entry) {
      if ($entry['email'] === $userEmail && !empty($entry['avatar_url'])) {
        $avatar = $entry['avatar_url'];
        break;
      }
    }
  }
}

// AFFILIATE CODE LOGIC
$afFile = 'af.json';
$affiliate_data = [];
if (file_exists($afFile)) {
  $affiliate_data = json_decode(file_get_contents($afFile), true);
  if (!is_array($affiliate_data)) {
    $affiliate_data = [];
  }
}
$userAffiliateCode = "";
$userInviteCode = "";
$userAffiliateUsageCount = 0;
foreach ($affiliate_data as $entry) {
  if ($entry['email'] === $userEmail) {
    $userAffiliateCode = $entry['affiliate_code'];
    $userInviteCode = $entry['invite_code'];
    if (isset($entry['usage_count']) && is_numeric($entry['usage_count'])) {
      $userAffiliateUsageCount = $entry['usage_count'];
    }
    break;
  }
}
// Generate affiliate code if not set
if (empty($userAffiliateCode)) {
  function generateAffiliateCode($length = 14) {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()_+';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
      $code .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $code;
  }
  $userAffiliateCode = generateAffiliateCode(14);
  $affiliate_data[] = array(
    "email" => $userEmail, 
    "affiliate_code" => $userAffiliateCode, 
    "invite_code" => "",
    "usage_count" => 0
  );
  file_put_contents($afFile, json_encode($affiliate_data, JSON_PRETTY_PRINT));

// Read offers from offers.json
$offersData = [];
$offersFile = 'offers.json';
if (file_exists($offersFile)) {
  $offersData = json_decode(file_get_contents($offersFile), true);
  if (!is_array($offersData)) { $offersData = []; }
}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <link rel="icon" href="images/logo.png" type="image/png">
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>User Dashboard</title>
  <!-- Font Awesome for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
  <!-- Google Fonts for English (Quicksand) and Arabic (Tajawal) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;600&family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
  <!-- Google reCAPTCHA v3 -->
  <script src="https://www.google.com/recaptcha/api.js?render=6Lc5V-IqAAAAAHS8v3sJgrx1Wqx9iE1PcE21MVqP"></script>

  <style>
    /* ---------- Basic Reset & Body ---------- */
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      background: #f0f2f5; color: #333;
      transition: background 0.3s, color 0.3s;
      font-family: sans-serif;
    }
    body.dark-mode { background: #18191a; color: #ddd; }
    a { text-decoration: none; color: inherit; }
    ul { list-style: none; }

    /* ---------- Fixed Sidebar ---------- */
    .sidebar {
      position: fixed; top: 0; left: 0; bottom: 0;
      width: 250px; background: #5865f2; color: #fff; padding: 20px 0; transition: background 0.3s; overflow-y: auto;
      scrollbar-width: none; /* Firefox */
    }
    .sidebar::-webkit-scrollbar { display: none; /* Chrome, Safari */ }
    body.dark-mode .sidebar { background: #3a3b3c; }
    .sidebar h2 { text-align: center; margin-bottom: 30px; font-size: 24px; }
    .sidebar ul { margin: 0; padding: 0 20px; }
    .sidebar ul li {
      position: relative; padding: 15px 20px; margin: 5px 0; cursor: pointer; border-radius: 8px; display: flex; align-items: center; transition: background 0.3s; font-size: 16px;
    }
    .sidebar ul li i { margin-right: 10px; }
    .sidebar ul li:hover { background: rgba(255, 255, 255, 0.1); }
    .sidebar ul li.active { background: rgba(255, 255, 255, 0.2); }
    .sidebar-indicator {
      position: absolute; left: 0; width: 4px; height: 40px; background-color: #fff; border-radius: 2px; transition: transform 0.3s ease;
    }
    .sidebar-profile {
      position: relative; margin-top: 30px; padding: 0 20px; display: flex; align-items: center; flex-wrap: wrap;
    }
    .sidebar-avatar {
      width: 50px; height: 50px; border-radius: 50%; object-fit: cover; margin-right: 10px; border: 2px solid #fff;
    }
    .sidebar-user-info { display: flex; flex-direction: column; }
    .sidebar-nickname { font-weight: bold; font-size: 15px; margin-bottom: 3px; }
    .sidebar-email { font-size: 13px; color: #ccc; }
    #logoutBtn {
      flex-basis: 100%; margin-top: 10px; background: #e74c3c; border: none; border-radius: 5px; color: #fff; cursor: pointer; font-size: 13px; padding: 8px 12px; transition: background 0.3s; text-align: center;
    }
    #logoutBtn:hover { background: #d63b31; }

    /* ---------- Main Content ---------- */
    .main {
      margin-left: 250px; padding: 40px; transition: background 0.3s; min-height: 100vh; overflow-y: auto;
    }
    body.dark-mode .main { background: #242526; }
    .section { display: none; transition: opacity 0.3s; }
    .section.active { display: block; }

    /* ---------- Cards & Profile ---------- */
    .card {
      background: #fff; border-radius: 15px; box-shadow: 0 8px 20px rgba(0,0,0,0.1); padding: 30px; margin-bottom: 30px; transition: background 0.3s;
    }
    body.dark-mode .card { background: #3a3b3c; box-shadow: none; }
    .card h3 { margin-bottom: 20px; color: #333; }
    body.dark-mode .card h3 { color: #ddd; }
    .profile { display: flex; align-items: center; flex-wrap: wrap; }
    .avatar-container { position: relative; display: inline-block; margin-right: 20px; }
    .avatar-container img {
      width: 120px; height: 120px; border-radius: 50%; border: 4px solid #5865f2; object-fit: cover; transition: border 0.3s;
    }
    body.dark-mode .avatar-container img { border: 4px solid #888; }
    .edit-avatar-btn {
      position: absolute; bottom: 0; right: 0; background: #5865f2; border: 2px solid #fff; border-radius: 50%; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: background 0.3s;
    }
    .edit-avatar-btn:hover { background: #4752c4; }
    .edit-avatar-btn i { color: #fff; font-size: 16px; }
    .profile-details { flex: 1; min-width: 250px; }
    .profile-details label { font-weight: 600; margin-bottom: 5px; color: #555; display: block; }
    body.dark-mode .profile-details label { color: #ddd; }
    .profile-details input {
      width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 8px; font-size: 16px; transition: box-shadow 0.3s ease, border 0.3s;
    }
    .profile-details input:focus {
      outline: none; box-shadow: 0 0 10px #5865f2, 0 0 20px rgba(88,101,242,0.6); border-color: #5865f2;
    }
    .profile-details input[readonly] { background: #e9ecef; cursor: not-allowed; }
    .btn-save {
      padding: 10px 20px; background: #5865f2; color: #fff; border: none; border-radius: 8px; cursor: pointer; transition: background 0.3s; margin-right: 10px;
    }
    .btn-save:hover { background: #4752c4; }

    /* ---------- Loader & Blurred Content ---------- */
    #loader {
      position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: #f0f2f5; display: flex; justify-content: center; align-items: center; z-index: 9999; transition: opacity 0.3s;
    }
    body.dark-mode #loader { background: #18191a; }
    .spinner {
      width: 50px; height: 50px; border: 6px solid #fff; border-top: 6px solid #002147; border-radius: 50%; animation: spin 1s linear infinite;
    }
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    .blurred-content { filter: blur(5px); transition: filter 0.5s ease-in-out; }

    /* ---------- Affiliates & Support ---------- */
    .affiliates-card {
      background: linear-gradient(135deg, #6a11cb, #2575fc);
      color: #fff;
      border-radius: 15px;
      padding: 20px;
      margin: 20px auto;
      max-width: 500px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.2);
      transition: box-shadow 0.3s;
    }
    body.dark-mode .affiliates-card {
      background: linear-gradient(135deg, #333, #444);
      box-shadow: none;
    }
    .affiliates-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .affiliates-header h3 {
      margin: 0;
      font-size: 22px;
    }
    .affiliates-details {
      margin-top: 20px;
      text-align: center;
    }
    .affiliates-details .code {
      font-size: 18px;
      font-weight: bold;
      background: rgba(255, 255, 255, 0.2);
      padding: 10px;
      border-radius: 8px;
      letter-spacing: 2px;
      display: inline-block;
      position: relative;
    }
    .copy-btn {
      position: absolute;
      top: 50%;
      right: -40px;
      transform: translateY(-50%);
      background: #fff;
      border: none;
      border-radius: 4px;
      padding: 4px;
      cursor: pointer;
      transition: background 0.3s, color 0.3s;
    }
    .copy-btn:hover {
      background: #e0e0e0;
    }
    .copy-success {
      background: #28a745 !important;
      color: #fff !important;
    }
    .affiliates-details .usage {
      margin-top: 10px;
      font-size: 16px;
    }
    .affiliates-invite {
      margin-top: 20px;
      text-align: center;
    }
    .affiliates-invite label {
      font-size: 16px;
      margin-bottom: 5px;
      display: block;
    }
    .affiliates-invite input {
      width: 80%;
      padding: 10px;
      border-radius: 8px;
      border: 1px solid #ccc;
      margin-bottom: 10px;
      display: inline-block;
      transition: border 0.3s;
    }
    .affiliates-invite input:focus {
      border-color: #5865f2;
    }
    .affiliates-invite button {
      background: #fff;
      color: #2575fc;
      padding: 10px 20px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-weight: bold;
      transition: background 0.3s;
    }
    .affiliates-invite button:hover {
      background: #e0e0e0;
    }
    .support-box {
      background: #fff;
      border-radius: 15px;
      padding: 20px;
      margin: 20px auto;
      max-width: 500px;
      text-align: center;
      box-shadow: 0 8px 20px rgba(0,0,0,0.1);
      transition: background 0.3s;
    }
    body.dark-mode .support-box {
      background: #3a3b3c;
      box-shadow: none;
    }
    .support-box a {
      color: #2575fc;
      font-weight: bold;
      text-decoration: none;
      transition: color 0.3s;
    }
    .support-box a:hover {
      color: #6a11cb;
    }

    /* ---------- Wallet Section ---------- */
    .wallet-card {
      position: relative;
      text-align: left;
    }
    .wallet-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 20px;
    }
    .wallet-header .balance-label {
      font-size: 16px;
      font-weight: 600;
      color: #666;
    }
    .wallet-header .balance-amount {
      font-size: 32px;
      font-weight: bold;
      color: #333;
      margin-top: 5px;
    }
    .add-money-btn {
      display: inline-block;
      width: 50px;
      height: 50px;
      background: #f3f3f3;
      border-radius: 10px;
      font-size: 24px;
      color: #666;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: background 0.3s, color 0.3s;
    }
    .add-money-btn:hover {
      background: #ddd;
      color: #333;
    }
    body.dark-mode .add-money-btn {
      background: #555;
      color: #ccc;
    }
    body.dark-mode .add-money-btn:hover {
      background: #444;
      color: #fff;
    }
    .wallet-actions {
      display: flex;
      justify-content: space-between;
      margin-top: 10px;
    }
    .wallet-action {
      width: 48%;
      background: #fff;
      border-radius: 8px;
      text-align: center;
      padding: 10px 0;
      cursor: pointer;
      transition: background 0.3s;
    }
    body.dark-mode .wallet-action {
      background: #4a4b4c;
    }
    .wallet-action:hover {
      background: #f7f7f7;
    }
    body.dark-mode .wallet-action:hover {
      background: #3a3b3c;
    }
    .wallet-action .action-icon {
      font-size: 24px;
      margin-bottom: 5px;
    }
    .wallet-action .action-label {
      font-size: 14px;
      font-weight: 500;
      color: #333;
    }
    body.dark-mode .wallet-action .action-label {
      color: #ddd;
    }

    /* ---------- Transaction History + Filter Icon ---------- */
    .transaction-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-top: 30px;
    }
    .transaction-header h4 { margin: 0; }
    .filter-btn {
      background: none;
      border: none;
      color: #888;
      font-size: 20px;
      cursor: pointer;
      transition: color 0.3s;
    }
    .filter-btn:hover { color: #333; }
    body.dark-mode .filter-btn:hover { color: #fff; }
    .transaction-history {
      margin-top: 10px;
      max-height: 300px;
      overflow-y: auto;
      border-top: 1px solid #ccc;
      padding-top: 10px;
    }
    .transaction-history li {
      display: flex;
      align-items: center;
      padding: 10px;
      border-bottom: 1px solid #eee;
    }
    .transaction-history li:last-child { border-bottom: none; }
    .transaction-icon {
      font-size: 24px;
      margin-right: 15px;
      width: 40px;
      text-align: center;
    }
    .transaction-details { flex: 1; }
    .transaction-details span { display: block; }
    .transaction-date { font-weight: bold; }
    .transaction-type { font-style: italic; color: #555; }
    .transaction-id { font-size: 12px; color: #999; }
    .transaction-amount { font-size: 16px; font-weight: bold; }
    .transaction-history::-webkit-scrollbar { width: 6px; }
    .transaction-history::-webkit-scrollbar-track { background: #f0f0f0; }
    .transaction-history::-webkit-scrollbar-thumb { background: #888; border-radius: 3px; }
    .utc-offset { color: #888; font-size: 0.9em; }

    /* ---------- Modals ---------- */
    .modal {
      display: none;
      position: fixed;
      z-index: 10000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      overflow: auto;
      background-color: rgba(0,0,0,0.5);
      transition: opacity 0.3s;
    }
    .modal-content {
      background-color: #fff;
      margin: 15% auto;
      padding: 20px;
      border-radius: 8px;
      width: 300px;
      text-align: center;
      position: relative;
      transition: transform 0.3s;
    }
    body.dark-mode .modal-content { background-color: #3a3b3c; color: #ddd; }
    .modal-content input {
      width: 100%;
      padding: 10px;
      margin-top: 10px;
      border: 1px solid #ccc;
      border-radius: 8px;
      transition: box-shadow 0.3s;
    }
    .modal-content input:focus { outline: none; box-shadow: 0 0 10px #5865f2; }
    .modal-content button {
      margin-top: 15px;
      padding: 8px 16px;
      background: #5865f2;
      border: none;
      color: #fff;
      border-radius: 8px;
      cursor: pointer;
      transition: background 0.3s;
    }
    .modal-content button:hover { background: #4752c4; }
    .close-button { position: absolute; top: 10px; right: 15px; font-size: 24px; cursor: pointer; }
    #filterModal .modal-content { width: 350px; }
    .filter-section { margin: 15px 0; text-align: left; }
    .filter-section h5 { margin-bottom: 10px; font-size: 16px; }
    .filter-options { display: flex; flex-direction: column; gap: 5px; }
    .filter-options label { cursor: pointer; }
    .date-input { margin-top: 5px; }
    /* Offers Section (unchanged) */
    .offers-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
    .offer-box { border: 1px solid #ccc; border-radius: 8px; overflow: hidden; cursor: pointer; transition: transform 0.2s; background: #fff; }
    .offer-box:hover { transform: scale(1.02); }
    .offer-image { width: 100%; height: 150px; object-fit: cover; }
    .offer-content { padding: 10px; }
    .no-offers { text-align: center; font-size: 24px; padding: 50px 0; color: #666; }
    .offer-modal { display: none; position: fixed; z-index: 11000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); }
    .offer-modal-content { background: #fff; margin: 10% auto; padding: 20px; border-radius: 8px; width: 90%; max-width: 500px; position: relative; }
  </style>
</head>
<body>
  <!-- Loader -->
  <div id="loader">
    <div class="spinner"></div>
  </div>

  <!-- Avatar Modal -->
  <div id="avatarModal" class="modal">
    <div class="modal-content">
      <span class="close-button" onclick="closeAvatarModal()">×</span>
      <h3>Edit Avatar</h3>
      <input type="text" id="modalAvatarURL" placeholder="Enter new avatar URL">
      <button onclick="submitAvatarModal()">Save</button>
    </div>
  </div>

  <!-- Logout Confirmation Modal -->
  <div id="logoutModal" class="modal">
    <div class="modal-content">
      <span class="close-button" onclick="closeLogoutModal()">×</span>
      <h3 data-translate="confirmLogoutTitle">Confirm Logout</h3>
      <p data-translate="confirmLogoutText">Are you sure you want to logout?</p>
      <button onclick="confirmLogout()" data-translate="confirmLogoutBtn">Logout</button>
      <button onclick="closeLogoutModal()" data-translate="cancelBtn">Cancel</button>
    </div>
  </div>

  <!-- Transfer Modal -->
  <div id="transferModal" class="modal">
    <div class="modal-content">
      <span class="close-button" onclick="closeTransferModal()">×</span>
      <h3 data-translate="transferModalTitle">Transfer Money</h3>
      <input type="text" id="transferRecipient" placeholder="Recipient Email" data-translate-placeholder="transferRecipientPlaceholder">
      <input type="number" id="transferAmount" placeholder="Amount" data-translate-placeholder="transferAmountPlaceholder">
      <button onclick="initiateTransfer()" data-translate="transferConfirmBtn">Confirm Transfer</button>
      <button onclick="closeTransferModal()" data-translate="cancelBtn">Cancel</button>
      <div id="transferMessage" style="margin-top:10px;"></div>
    </div>
  </div>

  <!-- OTP Modal -->
  <div id="otpModal" class="modal">
    <div class="modal-content" style="max-width:400px;">
      <span class="close-button" onclick="closeOtpModal()">×</span>
      <h3 data-translate="otpModalTitle">Enter OTP</h3>
      <div id="otpInputs" style="display:flex; justify-content:center; gap:10px;">
        <input type="text" maxlength="1" class="otp-input" />
        <input type="text" maxlength="1" class="otp-input" />
        <input type="text" maxlength="1" class="otp-input" />
        <input type="text" maxlength="1" class="otp-input" />
        <input type="text" maxlength="1" class="otp-input" />
        <input type="text" maxlength="1" class="otp-input" />
      </div>
      <button onclick="verifyOtp()" data-translate="verifyOtpBtn">Verify OTP</button>
      <div id="otpMessage" style="margin-top:10px;"></div>
    </div>
  </div>

  <!-- Filter/Adjust Modal -->
  <div id="filterModal" class="modal">
    <div class="modal-content">
      <span class="close-button" onclick="closeFilterModal()">×</span>
      <h3 data-translate="filterAdjustTitle">Filter & Adjust</h3>
      <!-- Sorting -->
      <div class="filter-section">
        <h5 data-translate="sortByTitle">Sort By:</h5>
        <div class="filter-options">
          <label><input type="radio" name="sortOption" value="newToOld" checked> <span data-translate="sortNewToOld">New to Old</span></label>
          <label><input type="radio" name="sortOption" value="oldToNew"> <span data-translate="sortOldToNew">Old to New</span></label>
          <label><input type="radio" name="sortOption" value="specificDate"> <span data-translate="sortSpecificDate">Specific Date</span></label>
          <input type="date" id="specificDateInput" class="date-input" style="display:none;">
        </div>
      </div>
      <!-- Filtering -->
      <div class="filter-section">
        <h5 data-translate="filterByTitle">Filter By:</h5>
        <div class="filter-options">
          <label><input type="radio" name="filterOption" value="all" checked> <span data-translate="filterAll">All</span></label>
          <label><input type="radio" name="filterOption" value="transfare"> <span data-translate="filterTransfers">Transfers Only</span></label>
          <label><input type="radio" name="filterOption" value="purchase"> <span data-translate="filterWithdraw">Withdraw Only</span></label>
          <label><input type="radio" name="filterOption" value="deposit"> <span data-translate="filterDeposit">Deposit Only</span></label>
        </div>
      </div>
      <button onclick="resetFilter()" data-translate="filterResetBtn">Reset</button>
      <button onclick="saveFilterSettings()" data-translate="filterSaveBtn">Save Settings</button>
    </div>
  </div>

  <!-- Main Dashboard -->
  <div id="content" class="blurred-content">
    <!-- Sidebar -->
    <div class="sidebar">
      <h2 id="menuDashboardTitle" data-translate="dashboard">Dashboard</h2>
      <div class="sidebar-indicator" id="sidebarIndicator"></div>
      <ul>
        <li class="active" data-section="account" id="menuAccount">
          <i class="fas fa-user"></i> <span data-translate="account">Account</span>
        </li>
        <li data-section="orders" id="menuOrders">
          <i class="fas fa-box"></i> <span data-translate="orders">Orders</span>
        </li>
        <li data-section="wallet" id="menuWallet">
          <i class="fas fa-wallet"></i> <span data-translate="wallet">My Wallet</span>
        </li>
        <li data-section="affiliates" id="menuAffiliates">
          <i class="fas fa-users"></i> <span data-translate="affiliates">Affiliates</span>
        </li>
        <li data-section="support" id="menuSupport">
          <i class="fas fa-headset"></i> <span data-translate="support">Customer Support</span>
        </li>
        <li data-section="settings" id="menuSettings">
          <i class="fas fa-cog"></i> <span data-translate="settings">Settings</span>
        </li>
      </ul>
      <div class="sidebar-profile">
        <img src="<?php echo htmlspecialchars($avatar); ?>" alt="Profile" class="sidebar-avatar" id="sidebarAvatar" />
        <div class="sidebar-user-info">
          <div class="sidebar-nickname" id="sidebarNickname">
            <?php echo (!empty($nickname)) ? htmlspecialchars($nickname) : htmlspecialchars($userEmail); ?>
          </div>
          <div class="sidebar-email">
            <?php echo htmlspecialchars($userEmail); ?>
          </div>
        </div>
        <button id="logoutBtn" onclick="openLogoutModal()" data-translate="logoutBtn">Logout</button>
      </div>
    </div>
    
    <!-- Main Content Area -->
    <div class="main">
      <!-- Account Section -->
      <div id="section-account" class="section active">
        <div class="card">
          <h3 data-translate="accountDetails">Account Details</h3>
          <div class="profile">
            <div class="avatar-container">
              <img id="accountAvatar" src="<?php echo htmlspecialchars($avatar); ?>" alt="Avatar">
              <div class="edit-avatar-btn" onclick="openAvatarModal()">
                <i class="fas fa-pencil-alt"></i>
              </div>
            </div>
            <div class="profile-details">
              <label for="emailDisplay" data-translate="emailLabel">Email:</label>
              <input type="text" id="emailDisplay" value="<?php echo htmlspecialchars($userEmail); ?>" readonly>
              <label for="nicknameInput" data-translate="nicknameLabel">Nickname (max 12 chars):</label>
              <input type="text" id="nicknameInput" maxlength="12" value="<?php echo htmlspecialchars($nickname); ?>" placeholder="Enter your nickname">
              <button class="btn-save" onclick="saveNickname()" data-translate="editNicknameBtn">Edit Nickname</button>
              <div id="nicknameMessage" style="margin-top:10px;"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Orders Section -->
      <div id="section-orders" class="section">
        <div class="card">
          <h3 data-translate="ordersHeading">Orders</h3>
          <button class="btn-save" onclick="placeOrder()" data-translate="placeOrderBtn">Place New Order</button>
          <p>Your order details will appear here.</p>
        </div>
      </div>

      <!-- My Wallet Section -->
      <div id="section-wallet" class="section">
        <div class="card wallet-card">
          <?php 
            $moneyFile = 'money.json';
            $walletBalance = 0;
            if (file_exists($moneyFile)) {
              $moneyData = json_decode(file_get_contents($moneyFile), true);
              if (is_array($moneyData)) {
                foreach ($moneyData as $entry) {
                  if (isset($entry['email']) && $entry['email'] === $userEmail) {
                    $walletBalance = $entry['money'];
                    break;
                  }
                }
              }
            }
          ?>
          <div class="wallet-header">
            <div>
              <div class="balance-label" data-translate="walletBalance">Total Balance</div>
              <div class="balance-amount">
                <?php echo '$ ' . htmlspecialchars(number_format($walletBalance, 2)); ?>
              </div>
            </div>
            <div class="add-money-btn" onclick="depositMoney()">
              <i class="fas fa-plus"></i>
            </div>
          </div>

          <div class="wallet-actions">
            <div class="wallet-action" onclick="alert('Withdraw functionality coming soon!')">
              <div class="action-icon" style="color:#f65e7c;"><i class="fas fa-arrow-down"></i></div>
              <div class="action-label" data-translate="filterWithdraw">Withdraw</div>
            </div>
            <div class="wallet-action" onclick="openTransferModal()">
              <div class="action-icon" style="color:#7042f5;"><i class="fas fa-paper-plane"></i></div>
              <div class="action-label" data-translate="filterTransfers">Send</div>
            </div>
          </div>

          <div class="transaction-header">
            <h4 data-translate="transactionHistory">Transaction History</h4>
            <button class="filter-btn" onclick="openFilterModal()"><i class="fas fa-filter"></i></button>
          </div>

          <?php 
            $transFile = 'trans.json';
            $transactions = [];
            if (file_exists($transFile)) {
              $transactions = json_decode(file_get_contents($transFile), true);
            }
            $userTrans = [];
            if (is_array($transactions)) {
              foreach ($transactions as $trans) {
                if (isset($trans['email']) && $trans['email'] === $userEmail) {
                  $userTrans[] = $trans;
                }
              }
            }
          ?>
          <ul id="transactionList" class="transaction-history"></ul>
        </div>
      </div>
      <!-- Offers Section -->
      <div id="section-offers" class="section">
        <div class="card">
          <h3 data-translate="offers">Offers</h3>
          <div id="offersContainer" class="offers-container"></div>
          <div id="noOffersMsg" class="no-offers" style="display:none;" data-translate="noOffers">Sorry, there are no offers.</div>
        </div>
      </div>
      <!-- Affiliates Section -->
      <div id="section-affiliates" class="section">
        <div class="affiliates-card">
          <div class="affiliates-header">
            <h3 data-translate="affiliateDashboard">Affiliate Dashboard</h3>
            <i class="fas fa-link" style="font-size:24px;"></i>
          </div>
          <div class="affiliates-details">
            <p><strong data-translate="myAffiliateCode">My Affiliate Code:</strong></p>
            <div class="code" id="affiliateCode">
              <?php echo htmlspecialchars($userAffiliateCode); ?>
              <button class="copy-btn" id="copyBtn" onclick="copyAffiliateCode()">
                <i class="fas fa-copy"></i>
              </button>
            </div>
            <div class="usage">
              <span data-translate="usageCount">Usage Count:</span> 
              <?php echo htmlspecialchars($userAffiliateUsageCount); ?>
            </div>
          </div>
          <div class="affiliates-invite">
            <?php if (empty($userInviteCode)): ?>
              <label for="inviteCodeInput" data-translate="inviteCodeLabel">Enter Invite Code (only once):</label>
              <input type="text" id="inviteCodeInput" placeholder="Enter invite code">
              <button class="btn-save" onclick="saveInviteCode()" data-translate="submitInviteBtn">Submit Invite Code</button>
              <div id="inviteCodeMessage" style="margin-top:10px;"></div>
            <?php else: ?>
              <p><strong data-translate="inviteCodeUsed">Invite Code Used:</strong></p>
              <input type="text" id="inviteCodeInput" value="<?php echo htmlspecialchars($userInviteCode); ?>" readonly>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Customer Support Section -->
      <div id="section-support" class="section">
        <div class="support-box">
          <h3 data-translate="supportHeading">Customer Support</h3>
          <p data-translate="supportJoinDiscord">If you need help, please join our Discord server:</p>
          <a href="https://discord.gg/sDsZdhDgk6" target="_blank" data-translate="discordLinkText">Join our Discord</a>
        </div>
      </div>

      <!-- Settings Section -->
      <div id="section-settings" class="section">
        <div class="card">
          <h3 data-translate="settingsHeading">Settings</h3>
          <div class="settings-row">
            <label data-translate="languageLabel">Language:</label>
            <select id="languageSelect">
              <option value="en">English</option>
              <option value="ar">العربية</option>
            </select>
          </div>
          <div class="settings-row">
            <label data-translate="themeLabel">Theme:</label>
            <div class="theme-options">
              <input type="radio" name="theme" id="themeLight" value="light">
              <label for="themeLight" data-translate="lightTheme">Light</label>
              <input type="radio" name="theme" id="themeDark" value="dark">
              <label for="themeDark" data-translate="darkTheme">Dark</label>
            </div>
          </div>
          <button class="btn-save" onclick="saveUserSettings()" data-translate="saveSettingsBtn">Save Settings</button>
        </div>
      </div>
    </div>
  </div>

  <!-- JavaScript -->
  <script>
    var currentLang = localStorage.getItem('userLang') || 'en';
    var currentBalance = <?php echo isset($walletBalance) ? json_encode($walletBalance) : 0; ?>;
    var userTransactions = <?php echo json_encode($userTrans ?? []); ?>;
    let transferRecipient = "";
    let transferAmount = 0;
    let sortOption = "newToOld";
    let filterOption = "all";
    let specificDate = "";

    const translations = {
      en: {
        dashboard: "Dashboard", account: "Account", orders: "Orders", wallet: "My Wallet", affiliates: "Affiliates",
        support: "Customer Support", settings: "Settings", confirmLogoutTitle: "Confirm Logout", confirmLogoutText: "Are you sure you want to logout?",
        confirmLogoutBtn: "Logout", cancelBtn: "Cancel", logoutBtn: "Logout", accountDetails: "Account Details",
        emailLabel: "Email:", nicknameLabel: "Nickname (max 12 chars):", editNicknameBtn: "Edit Nickname",
        ordersHeading: "Orders", placeOrderBtn: "Place New Order", walletBalance: "Total Balance",
        depositInstructions: "To deposit money, please open a ticket in our Discord.", transactionHistory: "Transaction History",
        noTransactions: "No transactions yet.", deposit: "Deposit", purchase: "Purchase", transfare: "Transfer",
        affiliateDashboard: "Affiliate Dashboard", myAffiliateCode: "My Affiliate Code:", usageCount: "Usage Count:",
        inviteCodeLabel: "Enter Invite Code (only once):", submitInviteBtn: "Submit Invite Code", inviteCodeUsed: "Invite Code Used:",
        supportHeading: "Customer Support", supportJoinDiscord: "If you need help, please join our Discord server:",
        discordLinkText: "Join our Discord", settingsHeading: "Settings", languageLabel: "Language:", themeLabel: "Theme:",
        lightTheme: "Light", darkTheme: "Dark", saveSettingsBtn: "Save Settings", transferModalTitle: "Transfer Money",
        transferRecipientPlaceholder: "Recipient Email", transferAmountPlaceholder: "Amount", transferConfirmBtn: "Confirm Transfer",
        otpModalTitle: "Enter OTP", verifyOtpBtn: "Verify OTP", filterWithdraw: "Withdraw", filterTransfers: "Send",
        filterAdjustTitle: "Filter & Adjust", sortByTitle: "Sort By:", sortNewToOld: "New to Old", sortOldToNew: "Old to New",
        sortSpecificDate: "Specific Date", filterByTitle: "Filter By:", filterAll: "All", filterResetBtn: "Reset", filterSaveBtn: "Save Settings",
        offerDetailsTitle: "Offer Details",
        offerBy: "Offered by",
        offerStarted: "Started on",
        offerEnds: "Ends on"
      },
      ar: {
        dashboard: "لوحة التحكم", account: "الحساب", orders: "الطلبات", wallet: "محفظتي", affiliates: "التسويق",
        support: "الدعم الفني", settings: "الإعدادات", confirmLogoutTitle: "تأكيد تسجيل الخروج", confirmLogoutText: "هل أنت متأكد أنك تريد تسجيل الخروج؟",
        confirmLogoutBtn: "تسجيل الخروج", cancelBtn: "إلغاء", logoutBtn: "تسجيل الخروج", accountDetails: "بيانات الحساب",
        emailLabel: "البريد الإلكتروني:", nicknameLabel: "اللقب (12 حرف كحد أقصى):", editNicknameBtn: "تعديل اللقب",
        ordersHeading: "الطلبات", placeOrderBtn: "طلب جديد", walletBalance: "إجمالي الرصيد",
        depositInstructions: "لإيداع الأموال، يرجى فتح تذكرة في Discord.", transactionHistory: "سجل المعاملات",
        noTransactions: "لا توجد معاملات بعد.", deposit: "إيداع", purchase: "شراء", transfare: "تحويل",
        affiliateDashboard: "لوحة المسوق", myAffiliateCode: "كود المسوق:", usageCount: "عدد الاستخدامات:",
        inviteCodeLabel: "أدخل رمز الدعوة (مرة واحدة فقط):", submitInviteBtn: "إرسال رمز الدعوة", inviteCodeUsed: "تم استخدام رمز الدعوة:",
        supportHeading: "خدمة العملاء", supportJoinDiscord: "إذا كنت بحاجة للمساعدة، يرجى الانضمام إلى خادم ديسكورد:",
        discordLinkText: "انضم لديسكورد", settingsHeading: "الإعدادات", languageLabel: "اللغة:", themeLabel: "المظهر:",
        lightTheme: "فاتح", darkTheme: "داكن", saveSettingsBtn: "حفظ الإعدادات", transferModalTitle: "تحويل الأموال",
        transferRecipientPlaceholder: "البريد الإلكتروني للمستلم", transferAmountPlaceholder: "المبلغ", transferConfirmBtn: "تأكيد التحويل",
        otpModalTitle: "أدخل رمز التحقق", verifyOtpBtn: "تحقق من الرمز", filterWithdraw: "سحب", filterTransfers: "تحويل",
        filterAdjustTitle: "الفرز والتصفية", sortByTitle: "فرز حسب:", sortNewToOld: "الأحدث للأقدم", sortOldToNew: "الأقدم للأحدث",
        sortSpecificDate: "تاريخ محدد", filterByTitle: "تصفية حسب:", filterAll: "الكل", filterResetBtn: "إعادة ضبط", filterSaveBtn: "حفظ الإعدادات",offerDetailsTitle: "تفاصيل العرض",
        offerBy: "العرض من",
        offerStarted: "بدأ العرض",
        offerEnds: "ينتهي العرض"
      }
    };

    window.onload = function() {
      document.getElementById('loader').style.display = 'none';
      document.getElementById('content').classList.remove('blurred-content');
      applyUserSettings();
      moveSidebarIndicator(document.querySelector('.sidebar ul li.active'));
      renderTransactionList();
      renderOffers();
      document.querySelectorAll('input[name="sortOption"]').forEach(radio => {
        radio.addEventListener('change', function() {
          document.getElementById('specificDateInput').style.display = this.value === 'specificDate' ? 'block' : 'none';
        });
      });
    };

    function applyLanguage(lang) {
      document.querySelectorAll('[data-translate]').forEach(el => {
        const key = el.getAttribute('data-translate');
        if (translations[lang] && translations[lang][key]) el.textContent = translations[lang][key];
      });
      document.querySelectorAll('[data-translate-placeholder]').forEach(el => {
        const key = el.getAttribute('data-translate-placeholder');
        if (translations[lang] && translations[lang][key]) el.placeholder = translations[lang][key];
      });
      document.body.style.fontFamily = lang === 'ar' ? "'Tajawal', sans-serif" : "'Quicksand', sans-serif";
    }

    function applyTheme(theme) {
      if (theme === 'dark') document.body.classList.add('dark-mode');
      else document.body.classList.remove('dark-mode');
    }

    function applyUserSettings() {
      let userLang = localStorage.getItem('userLang') || 'en';
      let userTheme = localStorage.getItem('userTheme') || 'light';
      currentLang = userLang;
      applyLanguage(userLang);
      applyTheme(userTheme);
      document.getElementById('languageSelect').value = userLang;
      document.getElementById(userTheme === 'dark' ? 'themeDark' : 'themeLight').checked = true;
    }

    function saveUserSettings() {
      const selectedLang = document.getElementById('languageSelect').value;
      const selectedTheme = document.querySelector('input[name="theme"]:checked').value;
      localStorage.setItem('userLang', selectedLang);
      localStorage.setItem('userTheme', selectedTheme);
      location.reload();
    }

    const sidebarItems = document.querySelectorAll('.sidebar ul li');
    const sections = document.querySelectorAll('.section');
    sidebarItems.forEach(item => {
      item.addEventListener('click', function() {
        sidebarItems.forEach(i => i.classList.remove('active'));
        this.classList.add('active');
        sections.forEach(section => section.classList.remove('active'));
        document.getElementById('section-' + this.getAttribute('data-section')).classList.add('active');
        moveSidebarIndicator(this);
      });
    });

    function moveSidebarIndicator(activeItem) {
      const indicator = document.getElementById('sidebarIndicator');
      const offsetTop = activeItem.offsetTop - activeItem.parentElement.offsetTop;
      indicator.style.transform = `translateY(${offsetTop}px)`;
    }

    function saveNickname() {
      const nickname = document.getElementById('nicknameInput').value.trim();
      const email = document.getElementById('emailDisplay').value;
      const messageDiv = document.getElementById('nicknameMessage');
      if (nickname === "") {
        messageDiv.style.color = 'red';
        messageDiv.textContent = "Nickname cannot be empty.";
        return;
      }
      grecaptcha.ready(function() {
        grecaptcha.execute('6Lc5V-IqAAAAAHS8v3sJgrx1Wqx9iE1PcE21MVqP', {action: 'save_nickname'}).then(function(token) {
          const xhr = new XMLHttpRequest();
          xhr.open('POST', 'save_nickname.php', true);
          xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
          xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
              const response = JSON.parse(xhr.responseText);
              messageDiv.style.color = response.status === 'success' ? 'green' : 'red';
              messageDiv.textContent = response.message;
              if (response.status === 'success') {
                localStorage.setItem('userData', JSON.stringify({ email: email, nickname: nickname }));
                document.getElementById('sidebarNickname').textContent = nickname;
              }
            }
          };
          xhr.send(`email=${encodeURIComponent(email)}&nickname=${encodeURIComponent(nickname)}&token=${encodeURIComponent(token)}`);
        });
      });
    }

    function openAvatarModal() {
      document.getElementById('modalAvatarURL').value = "";
      document.getElementById('avatarModal').style.display = 'block';
    }
    function closeAvatarModal() { document.getElementById('avatarModal').style.display = 'none'; }
    function submitAvatarModal() {
      const avatarURL = document.getElementById('modalAvatarURL').value.trim();
      if (avatarURL === "") { alert("Please enter a valid URL."); return; }
      saveAvatar(avatarURL);
    }
    function saveAvatar(avatarURL) {
      const email = document.getElementById('emailDisplay').value;
      grecaptcha.ready(function() {
        grecaptcha.execute('6Lc5V-IqAAAAAHS8v3sJgrx1Wqx9iE1PcE21MVqP', {action: 'save_avatar'}).then(function(token) {
          const xhr = new XMLHttpRequest();
          xhr.open('POST', 'save_avatar.php', true);
          xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
          xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
              const response = JSON.parse(xhr.responseText);
              if (response.status === 'success') location.reload();
              else alert(response.message);
            }
          };
          xhr.send(`email=${encodeURIComponent(email)}&avatar_url=${encodeURIComponent(avatarURL)}&token=${encodeURIComponent(token)}`);
        });
      });
    }

    function openLogoutModal() { document.getElementById('logoutModal').style.display = 'block'; }
    function closeLogoutModal() { document.getElementById('logoutModal').style.display = 'none'; }
    function confirmLogout() {
      localStorage.removeItem('userData');
      window.location.href = 'login';
    }

    function placeOrder() { alert("Place order functionality coming soon!"); }
    function depositMoney() { alert(translations[currentLang]["depositInstructions"]); }

    function openTransferModal() {
      document.getElementById('transferRecipient').value = "";
      document.getElementById('transferAmount').value = "";
      document.getElementById('transferMessage').textContent = "";
      document.getElementById('transferModal').style.display = 'block';
    }
    function closeTransferModal() { document.getElementById('transferModal').style.display = 'none'; }
    function initiateTransfer() {
      transferRecipient = document.getElementById('transferRecipient').value.trim();
      transferAmount = parseFloat(document.getElementById('transferAmount').value.trim());
      const messageDiv = document.getElementById('transferMessage');
      if (transferRecipient === "" || isNaN(transferAmount) || transferAmount <= 0) {
        messageDiv.style.color = 'red';
        messageDiv.textContent = "Please enter a valid recipient and amount.";
        return;
      }
      grecaptcha.ready(function() {
        grecaptcha.execute('6Lc5V-IqAAAAAHS8v3sJgrx1Wqx9iE1PcE21MVqP', {action: 'transfer_money'}).then(function(token) {
          const xhr = new XMLHttpRequest();
          xhr.open('POST', 'transfer.php', true);
          xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
          xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
              const response = JSON.parse(xhr.responseText);
              if (response.status === 'success') {
                messageDiv.textContent = "";
                closeTransferModal();
                openOtpModal();
              } else {
                messageDiv.style.color = 'red';
                messageDiv.textContent = response.message;
              }
            }
          };
          const sender = document.getElementById('emailDisplay').value;
          xhr.send(`action=send_otp&sender=${encodeURIComponent(sender)}&recipient=${encodeURIComponent(transferRecipient)}&amount=${encodeURIComponent(transferAmount)}&token=${encodeURIComponent(token)}`);
        });
      });
    }

    function openOtpModal() {
      document.getElementById('otpModal').style.display = 'block';
      document.querySelector('.otp-input').focus();
    }
    function closeOtpModal() { document.getElementById('otpModal').style.display = 'none'; }
    function verifyOtp() {
      let otpCode = "";
      document.querySelectorAll('.otp-input').forEach(input => { otpCode += input.value.trim(); });
      if (otpCode.length !== 6) {
        document.getElementById('otpMessage').style.color = 'red';
        document.getElementById('otpMessage').textContent = "Please enter the complete 6-digit OTP.";
        return;
      }
      grecaptcha.ready(function() {
        grecaptcha.execute('6Lc5V-IqAAAAAHS8v3sJgrx1Wqx9iE1PcE21MVqP', {action: 'verify_otp'}).then(function(token) {
          const xhr = new XMLHttpRequest();
          xhr.open('POST', 'transfer.php', true);
          xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
          xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
              const response = JSON.parse(xhr.responseText);
              const otpMsg = document.getElementById('otpMessage');
              otpMsg.style.color = response.status === 'success' ? 'green' : 'red';
              otpMsg.textContent = response.message;
              if (response.status === 'success') setTimeout(() => location.reload(), 2000);
            }
          };
          const sender = document.getElementById('emailDisplay').value;
          xhr.send(`action=verify_otp&otp=${encodeURIComponent(otpCode)}&sender=${encodeURIComponent(sender)}&recipient=${encodeURIComponent(transferRecipient)}&amount=${encodeURIComponent(transferAmount)}&token=${encodeURIComponent(token)}`);
        });
      });
    }
    document.querySelectorAll('.otp-input').forEach((input, index, inputs) => {
      input.addEventListener('input', function() {
        if (this.value.length === this.maxLength && index < inputs.length - 1) inputs[index + 1].focus();
      });
      input.addEventListener('keydown', function(e) {
        if (e.key === 'Backspace' && this.value.length === 0 && index > 0) inputs[index - 1].focus();
      });
    });

    function saveInviteCode() {
      const inviteCode = document.getElementById('inviteCodeInput').value.trim();
      const messageDiv = document.getElementById('inviteCodeMessage');
      if (inviteCode === "") {
        messageDiv.style.color = 'red';
        messageDiv.textContent = "Invite code cannot be empty.";
        return;
      }
      grecaptcha.ready(function() {
        grecaptcha.execute('6Lc5V-IqAAAAAHS8v3sJgrx1Wqx9iE1PcE21MVqP', {action: 'save_invite'}).then(function(token) {
          const xhr = new XMLHttpRequest();
          xhr.open('POST', 'save_invite.php', true);
          xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
          xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
              const response = JSON.parse(xhr.responseText);
              messageDiv.style.color = response.status === 'success' ? 'green' : 'red';
              messageDiv.textContent = response.message;
              if (response.status === 'success') {
                document.getElementById('inviteCodeInput').value = inviteCode;
                document.getElementById('inviteCodeInput').setAttribute('readonly', 'true');
              }
            }
          };
          xhr.send(`email=${encodeURIComponent('<?php echo $userEmail; ?>')}&invite_code=${encodeURIComponent(inviteCode)}&token=${encodeURIComponent(token)}`);
        });
      });
    }

    function copyAffiliateCode() {
      const code = document.getElementById('affiliateCode').innerText.replace(/Copy[\s\S]*/i, '').trim();
      navigator.clipboard.writeText(code).then(() => {
        const copyBtn = document.getElementById('copyBtn');
        copyBtn.innerHTML = '<i class="fas fa-check"></i>';
        setTimeout(() => copyBtn.innerHTML = '<i class="fas fa-copy"></i>', 3000);
      }).catch(() => alert("Failed to copy code."));
    }

    function renderTransactionList() {
      const listElem = document.getElementById('transactionList');
      listElem.innerHTML = "";
      let sorted = [...userTransactions];
      if (sortOption === "newToOld") sorted.sort((a, b) => new Date(b.date) - new Date(a.date));
      else if (sortOption === "oldToNew") sorted.sort((a, b) => new Date(a.date) - new Date(b.date));
      else if (sortOption === "specificDate" && specificDate) {
        sorted = sorted.filter(tr => {
          const d = new Date(tr.date);
          return `${d.getFullYear()}-${d.getMonth()+1 < 10 ? '0' : ''}${d.getMonth()+1}-${d.getDate() < 10 ? '0' : ''}${d.getDate()}` === specificDate;
        });
        sorted.sort((a, b) => new Date(b.date) - new Date(a.date));
      }
      let filtered = filterOption === "all" ? sorted : sorted.filter(tr => tr.state === filterOption);
      filtered.forEach(tr => {
        const li = document.createElement('li');
        li.className = "transaction-item " + tr.state;
        const iconDiv = document.createElement('div');
        iconDiv.className = "transaction-icon";
        iconDiv.innerHTML = tr.state === 'deposit' ? '<i class="fas fa-arrow-up"></i>' : 
                            tr.state === 'purchase' ? '<i class="fas fa-arrow-down"></i>' : 
                            '<i class="fas fa-exchange-alt"></i>';
        const detailsDiv = document.createElement('div');
        detailsDiv.className = "transaction-details";
        const utcDateStr = tr.date.replace(/\//g, '-').replace(' ', 'T') + 'Z';
        const localDate = new Date(utcDateStr);
        if (!isNaN(localDate.getTime())) {
          let year = localDate.getFullYear();
          let month = localDate.getMonth() + 1;
          let day = localDate.getDate();
          let hours = localDate.getHours();
          let minutes = localDate.getMinutes();
          let ampm = hours >= 12 ? "pm" : "am";
          hours = (hours % 12) || 12;
          const offsetMinutes = localDate.getTimezoneOffset();
          const offsetHours = Math.floor(Math.abs(offsetMinutes) / 60);
          const offsetSign = offsetMinutes > 0 ? '-' : '+';
          const dateStr = `${year}/${month}/${day} | ${hours}:${minutes<10?"0"+minutes:minutes}${ampm} | <span class="utc-offset">UTC${offsetSign}${offsetHours}</span>`;
          const dateSpan = document.createElement('span');
          dateSpan.className = "transaction-date";
          dateSpan.innerHTML = dateStr;
          detailsDiv.appendChild(dateSpan);
        } else {
          const dateSpan = document.createElement('span');
          dateSpan.className = "transaction-date";
          dateSpan.textContent = tr.date;
          detailsDiv.appendChild(dateSpan);
        }
        const typeSpan = document.createElement('span');
        typeSpan.className = "transaction-type";
        typeSpan.setAttribute('data-state', tr.state);
        typeSpan.textContent = translations[currentLang][tr.state];
        detailsDiv.appendChild(typeSpan);
        const idSpan = document.createElement('span');
        idSpan.className = "transaction-id";
        idSpan.textContent = "ID: " + tr.transid;
        detailsDiv.appendChild(idSpan);
        const amountSpan = document.createElement('span');
        amountSpan.className = "transaction-amount";
        amountSpan.textContent = tr.state === 'deposit' ? "+ " + tr.amount : "- " + tr.amount;
        detailsDiv.appendChild(amountSpan);
        li.appendChild(iconDiv);
        li.appendChild(detailsDiv);
        listElem.appendChild(li);
      });
      if (filtered.length === 0) {
        listElem.innerHTML = `<p style="padding:10px;" data-translate="noTransactions">${translations[currentLang]["noTransactions"]}</p>`;
      }
    }

    function openFilterModal() { document.getElementById('filterModal').style.display = 'block'; }
    function closeFilterModal() { document.getElementById('filterModal').style.display = 'none'; }
    function resetFilter() {
      document.querySelector('input[name="sortOption"][value="newToOld"]').checked = true;
      document.querySelector('input[name="filterOption"][value="all"]').checked = true;
      document.getElementById('specificDateInput').style.display = 'none';
      document.getElementById('specificDateInput').value = "";
    }
    function saveFilterSettings() {
      const sortRadio = document.querySelector('input[name="sortOption"]:checked');
      if (sortRadio) sortOption = sortRadio.value;
      if (sortOption === 'specificDate') specificDate = document.getElementById('specificDateInput').value;
      else specificDate = "";
      const filterRadio = document.querySelector('input[name="filterOption"]:checked');
      if (filterRadio) filterOption = filterRadio.value;
      closeFilterModal();
      renderTransactionList();
    }
  </script>
</body>
</html>