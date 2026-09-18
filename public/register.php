<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_PATH . '/src/Auth.php';

$error = null;
$successMsg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'full_name'     => $_POST['full_name'] ?? '',
        'username'      => $_POST['username'] ?? '',
        'email'         => $_POST['email'] ?? '',
        'phone'         => $_POST['phone'] ?? '',
        'password'      => $_POST['password'] ?? '',
        'customer_type' => $_POST['customer_type'] ?? 'individual',
        'company_name'  => $_POST['company_name'] ?? '',
        'gst_number'    => $_POST['gst_number'] ?? '',
        'otp'           => $_POST['otp'] ?? '',
        'address'       => $_POST['address'] ?? '',
        'city'          => $_POST['city'] ?? 'Jaipur',
        'state'         => $_POST['state'] ?? 'Rajasthan',
        'pincode'       => $_POST['pincode'] ?? ''
    ];

    $res = Auth::register($data);
    if ($res['success']) {
        header("Location: " . (function_exists('url') ? url('my_orders.php') : '/my_orders.php'));
        exit;
    } else {
        $error = $res['message'];
    }
}

$pageTitle = "Register Customer Account - " . APP_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<div class="section-wrapper" style="max-width:640px;margin:35px auto;">
  <div style="background:#ffffff;border:1px solid var(--border-color);border-radius:var(--radius-xl);padding:35px;box-shadow:var(--shadow-md);">
    
    <div style="text-align:center;margin-bottom:25px;">
      <h1 style="font-size:1.65rem;font-weight:900;color:var(--text-main);margin-bottom:6px;">Create Customer Account</h1>
      <p style="color:var(--text-muted);font-size:0.88rem;margin:0;">Join Sakshi Infotech for authentic IT hardware, corporate stationery &amp; GST tax invoices</p>
    </div>

    <?php if ($error): ?>
      <div style="background:#fee2e2;border:1px solid #f87171;color:#991b1b;padding:12px 16px;border-radius:var(--radius-md);margin-bottom:20px;font-size:0.88rem;font-weight:700;">
        <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form action="<?= url('register.php') ?>" method="POST" id="registerForm" onsubmit="return validateRegisterForm(event);">
      
      <!-- 1. Customer Type Selector (Individual vs Company) -->
      <div style="margin-bottom:22px;">
        <label class="form-label" style="font-weight:800;font-size:0.9rem;margin-bottom:8px;display:block;">
          Select Customer Type <span style="color:var(--danger);">*</span>
        </label>
        
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
          <!-- Individual Option -->
          <label id="lblIndividual" style="border:2px solid var(--primary);background:var(--primary-light);border-radius:12px;padding:14px;cursor:pointer;display:flex;align-items:center;gap:12px;transition:all 0.2s;">
            <input type="radio" name="customer_type" value="individual" <?= (!isset($_POST['customer_type']) || $_POST['customer_type'] === 'individual') ? 'checked' : '' ?> onchange="toggleCustomerType()" style="accent-color:var(--primary);">
            <div>
              <div style="font-weight:800;font-size:0.92rem;color:var(--text-main);">
                <i class="fas fa-user" style="color:var(--primary);margin-right:4px;"></i> Individual
              </div>
              <div style="font-size:0.75rem;color:var(--text-muted);margin-top:2px;">Retail / Personal Buyer</div>
            </div>
          </label>

          <!-- Company Option -->
          <label id="lblCompany" style="border:2px solid var(--border-color);background:#ffffff;border-radius:12px;padding:14px;cursor:pointer;display:flex;align-items:center;gap:12px;transition:all 0.2s;">
            <input type="radio" name="customer_type" value="company" <?= (isset($_POST['customer_type']) && $_POST['customer_type'] === 'company') ? 'checked' : '' ?> onchange="toggleCustomerType()" style="accent-color:var(--primary);">
            <div>
              <div style="font-weight:800;font-size:0.92rem;color:var(--text-main);">
                <i class="fas fa-building" style="color:#d97706;margin-right:4px;"></i> Company (B2B)
              </div>
              <div style="font-size:0.75rem;color:var(--text-muted);margin-top:2px;">Business / GST ITC Benefits</div>
            </div>
          </label>
        </div>
      </div>

      <!-- 2. Company Specific Fields (Visible when Company selected) -->
      <div id="companyFieldsBox" style="display:none;background:#fffbeb;border:1px solid #fde68a;border-radius:12px;padding:18px;margin-bottom:22px;">
        <div style="font-size:0.82rem;font-weight:800;color:#92400e;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:12px;display:flex;align-items:center;gap:6px;">
          <i class="fas fa-file-invoice"></i> Corporate B2B GST Information
        </div>

        <div class="form-group" style="margin-bottom:14px;">
          <label class="form-label" style="font-weight:700;">Registered Company / Business Name <span style="color:var(--danger);">*</span></label>
          <input type="text" name="company_name" id="company_name" class="form-control" placeholder="e.g. Acme Tech Solutions Pvt Ltd" value="<?= htmlspecialchars($_POST['company_name'] ?? '') ?>">
        </div>

        <div class="form-group" style="margin-bottom:0;">
          <label class="form-label" style="font-weight:700;display:flex;justify-content:space-between;">
            <span>GST Identification Number (GSTIN) <span style="color:var(--danger);">*</span></span>
            <span style="font-size:0.75rem;color:#b45309;">15-Digit Alphanumeric</span>
          </label>
          <input type="text" name="gst_number" id="gst_number" class="form-control" placeholder="e.g. 08AAAAA0000A1Z5" maxlength="15" style="text-transform:uppercase;letter-spacing:1px;font-family:monospace;font-weight:700;" value="<?= htmlspecialchars($_POST['gst_number'] ?? '') ?>" oninput="this.value = this.value.toUpperCase();">
          <span style="font-size:0.74rem;color:#78350f;margin-top:4px;display:block;">
            <i class="fas fa-info-circle"></i> Required for commercial B2B invoices and claiming Input Tax Credit (ITC).
          </span>
        </div>
      </div>

      <!-- 3. Name & Username -->
      <div class="form-group">
        <label class="form-label">Full Name / Authorized Contact <span style="color:var(--danger);">*</span></label>
        <input type="text" name="full_name" id="regFullName" class="form-control" required placeholder="e.g. Rahul Verma" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
      </div>

      <div class="form-group">
        <label class="form-label">Desired Username (Login ID) <span style="color:var(--danger);">*</span></label>
        <input type="text" name="username" id="regUsername" class="form-control" required placeholder="e.g. rahul_v" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
      </div>

      <!-- 4. Email with Email OTP Verification -->
      <div class="form-group" style="margin-bottom:18px;">
        <label class="form-label" style="display:flex;justify-content:space-between;align-items:center;">
          <span>Email Address <span style="color:var(--danger);">*</span></span>
          <span id="emailVerifiedBadge" style="display:none;background:#dcfce7;color:#15803d;padding:2px 8px;border-radius:4px;font-size:0.75rem;font-weight:800;">
            <i class="fas fa-check-circle"></i> Email Verified
          </span>
        </label>
        
        <div style="display:flex;gap:10px;">
          <input type="email" name="email" id="regEmail" class="form-control" required placeholder="e.g. rahul@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" style="flex:1;">
          <button type="button" class="btn-outline-si" id="btnSendOtp" onclick="handleSendOtp()" style="white-space:nowrap;padding:8px 16px;font-size:0.86rem;">
            <i class="fas fa-paper-plane"></i> Send OTP
          </button>
        </div>
        <span style="font-size:0.75rem;color:var(--text-muted);margin-top:4px;display:block;">
          Verification OTP will be sent from <strong>noreply@sitindia.in</strong>
        </span>

        <!-- OTP Input Strip (Hidden initially until OTP is sent) -->
        <div id="otpInputRow" style="display:none;margin-top:12px;background:#f8fafc;border:1px dashed #93c5fd;border-radius:10px;padding:12px;">
          <label style="font-size:0.82rem;font-weight:800;color:#1e3a8a;display:block;margin-bottom:6px;">
            Enter 6-Digit Email OTP:
          </label>
          <div style="display:flex;gap:10px;align-items:center;">
            <input type="text" name="otp" id="inputOtp" class="form-control" placeholder="••••••" maxlength="6" style="letter-spacing:4px;text-align:center;font-weight:900;font-size:1.1rem;max-width:160px;">
            <button type="button" class="btn-primary-si" id="btnVerifyOtp" onclick="handleVerifyOtp()" style="padding:8px 18px;font-size:0.86rem;">
              Verify OTP
            </button>
            <span id="otpTimerText" style="font-size:0.78rem;color:var(--text-muted);"></span>
          </div>
          <div id="otpFeedbackMsg" style="margin-top:8px;font-size:0.82rem;font-weight:700;"></div>
        </div>
      </div>

      <!-- 5. Phone & Password -->
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Phone Number</label>
          <input type="text" name="phone" class="form-control" placeholder="9829011122" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Password (Min 6 chars) <span style="color:var(--danger);">*</span></label>
          <input type="password" name="password" id="regPassword" class="form-control" required placeholder="••••••••">
        </div>
      </div>

      <!-- 6. Delivery Address Details -->
      <div class="form-group">
        <label class="form-label">Delivery / Office Address</label>
        <input type="text" name="address" class="form-control" placeholder="Plot No, Street, Landmark" value="<?= htmlspecialchars($_POST['address'] ?? '') ?>">
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">City</label>
          <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($_POST['city'] ?? 'Jaipur') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Pincode</label>
          <input type="text" name="pincode" class="form-control" placeholder="302001" value="<?= htmlspecialchars($_POST['pincode'] ?? '') ?>">
        </div>
      </div>

      <!-- Submit Button -->
      <button type="submit" class="btn-primary-si" id="btnSubmitRegister" style="width:100%;justify-content:center;padding:14px;font-size:1rem;margin-top:14px;box-shadow:var(--shadow-glow);">
        <i class="fas fa-user-plus"></i> Register Customer Account
      </button>
    </form>

    <div style="text-align:center;margin-top:22px;font-size:0.88rem;color:var(--text-muted);">
      Already have an account? <a href="<?= url('login.php') ?>" style="color:var(--primary);font-weight:700;">Sign In Here</a>
    </div>

  </div>
</div>

<script>
let isEmailVerified = false;
let otpCountdown = 0;
let otpTimerInterval = null;

function toggleCustomerType() {
    const isCompany = document.querySelector('input[name="customer_type"]:checked').value === 'company';
    const compBox = document.getElementById('companyFieldsBox');
    const lblInd = document.getElementById('lblIndividual');
    const lblComp = document.getElementById('lblCompany');
    const compInput = document.getElementById('company_name');
    const gstInput = document.getElementById('gst_number');

    if (isCompany) {
        compBox.style.display = 'block';
        lblComp.style.borderColor = 'var(--primary)';
        lblComp.style.background = 'var(--primary-light)';
        lblInd.style.borderColor = 'var(--border-color)';
        lblInd.style.background = '#ffffff';
        compInput.setAttribute('required', 'required');
        gstInput.setAttribute('required', 'required');
    } else {
        compBox.style.display = 'none';
        lblInd.style.borderColor = 'var(--primary)';
        lblInd.style.background = 'var(--primary-light)';
        lblComp.style.borderColor = 'var(--border-color)';
        lblComp.style.background = '#ffffff';
        compInput.removeAttribute('required');
        gstInput.removeAttribute('required');
    }
}

// Trigger initial state
document.addEventListener('DOMContentLoaded', toggleCustomerType);

async function handleSendOtp() {
    const email = document.getElementById('regEmail').value.trim();
    const fullName = document.getElementById('regFullName').value.trim() || 'Customer';
    const btnSend = document.getElementById('btnSendOtp');
    const fb = document.getElementById('otpFeedbackMsg');

    if (!email || !email.includes('@')) {
        alert('Please enter a valid email address first.');
        document.getElementById('regEmail').focus();
        return;
    }

    btnSend.disabled = true;
    btnSend.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
    fb.innerHTML = '';

    try {
        const formData = new FormData();
        formData.append('email', email);
        formData.append('full_name', fullName);

        const resp = await fetch('<?= url('ajax/send_otp.php') ?>', {
            method: 'POST',
            body: formData
        });
        const data = await resp.json();

        if (data.success) {
            document.getElementById('otpInputRow').style.display = 'block';
            fb.style.color = '#15803d';
            fb.innerHTML = `<i class="fas fa-check"></i> ${data.message}` + (data.dev_otp ? ` (Test Code: <strong>${data.dev_otp}</strong>)` : '');
            
            // Start 60s cooldown timer
            startOtpTimer(60);
        } else {
            fb.style.color = '#b91c1c';
            fb.innerHTML = `<i class="fas fa-exclamation-triangle"></i> ${data.message}`;
            btnSend.disabled = false;
            btnSend.innerHTML = '<i class="fas fa-paper-plane"></i> Send OTP';
        }
    } catch (err) {
        fb.style.color = '#b91c1c';
        fb.innerHTML = 'Network error sending OTP. Please try again.';
        btnSend.disabled = false;
        btnSend.innerHTML = '<i class="fas fa-paper-plane"></i> Send OTP';
    }
}

function startOtpTimer(seconds) {
    const btnSend = document.getElementById('btnSendOtp');
    const timerSpan = document.getElementById('otpTimerText');
    otpCountdown = seconds;

    if (otpTimerInterval) clearInterval(otpTimerInterval);

    otpTimerInterval = setInterval(() => {
        otpCountdown--;
        if (otpCountdown > 0) {
            btnSend.disabled = true;
            btnSend.innerHTML = `Resend in ${otpCountdown}s`;
            timerSpan.innerText = `(${otpCountdown}s remaining)`;
        } else {
            clearInterval(otpTimerInterval);
            btnSend.disabled = false;
            btnSend.innerHTML = '<i class="fas fa-redo"></i> Resend OTP';
            timerSpan.innerText = '';
        }
    }, 1000);
}

async function handleVerifyOtp() {
    const email = document.getElementById('regEmail').value.trim();
    const otp = document.getElementById('inputOtp').value.trim();
    const fb = document.getElementById('otpFeedbackMsg');
    const btnVerify = document.getElementById('btnVerifyOtp');

    if (!otp || otp.length !== 6) {
        fb.style.color = '#b91c1c';
        fb.innerText = 'Please enter the 6-digit OTP.';
        return;
    }

    btnVerify.disabled = true;
    btnVerify.innerText = 'Verifying...';

    try {
        const formData = new FormData();
        formData.append('email', email);
        formData.append('otp', otp);

        const resp = await fetch('<?= url('ajax/verify_otp.php') ?>', {
            method: 'POST',
            body: formData
        });
        const data = await resp.json();

        if (data.success) {
            isEmailVerified = true;
            fb.style.color = '#15803d';
            fb.innerHTML = `<i class="fas fa-check-double"></i> ${data.message}`;
            document.getElementById('emailVerifiedBadge').style.display = 'inline-block';
            document.getElementById('regEmail').readOnly = true;
            document.getElementById('btnSendOtp').style.display = 'none';
            btnVerify.style.display = 'none';
            document.getElementById('inputOtp').readOnly = true;
        } else {
            fb.style.color = '#b91c1c';
            fb.innerHTML = `<i class="fas fa-times-circle"></i> ${data.message}`;
            btnVerify.disabled = false;
            btnVerify.innerText = 'Verify OTP';
        }
    } catch (err) {
        fb.style.color = '#b91c1c';
        fb.innerText = 'Verification failed. Please try again.';
        btnVerify.disabled = false;
        btnVerify.innerText = 'Verify OTP';
    }
}

function validateRegisterForm(e) {
    const isCompany = document.querySelector('input[name="customer_type"]:checked').value === 'company';
    
    if (isCompany) {
        const comp = document.getElementById('company_name').value.trim();
        const gst = document.getElementById('gst_number').value.trim().toUpperCase();
        if (!comp) {
            alert('Company Name is required for company accounts.');
            document.getElementById('company_name').focus();
            e.preventDefault();
            return false;
        }
        const gstRegex = /^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/;
        if (!gstRegex.test(gst)) {
            alert('Please enter a valid 15-character Indian GSTIN format (e.g. 08AAAAA0000A1Z5).');
            document.getElementById('gst_number').focus();
            e.preventDefault();
            return false;
        }
    }

    if (!isEmailVerified) {
        const otpVal = document.getElementById('inputOtp').value.trim();
        if (otpVal.length === 6) {
            // Allow form submit to verify server-side
            return true;
        }
        alert('Please verify your email address with OTP before creating your account.');
        document.getElementById('regEmail').focus();
        e.preventDefault();
        return false;
    }

    return true;
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
