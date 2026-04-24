<?php require_once '../check_login.php'; ?>
<?php
require_once '../includes/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $coupon_code = strtoupper(trim($_POST['coupon_code'] ?? ''));
    $coupon_type = $_POST['coupon_type'] ?? 'Percentage';
    $coupon_discount = (float)($_POST['coupon_discount'] ?? 0);
    $valid_until = $_POST['valid_until'] ?? '';
    $max_uses = (int)($_POST['max_uses'] ?? 0);
    $coupon_status = $_POST['coupon_status'] ?? 'ACTIVE';

    if (empty($coupon_code) || $coupon_discount <= 0 || empty($valid_until) || $max_uses <= 0) {
        $error = 'Please fill out all required fields with valid amounts.';
    } else {
        // check duplicate
        $stmt_check = $conn->prepare("SELECT id FROM coupons WHERE coupon_code = ?");
        $stmt_check->bind_param("s", $coupon_code);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            $error = 'This coupon code already exists!';
        } else {
            $stmt = $conn->prepare("INSERT INTO coupons (coupon_code, discount_type, discount_value, valid_until, max_uses, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssdsis", $coupon_code, $coupon_type, $coupon_discount, $valid_until, $max_uses, $coupon_status);
            if ($stmt->execute()) {
                $_SESSION['toast'] = "Coupon code $coupon_code added successfully!";
                header("Location: offer-coupon.php");
                exit;
            } else {
                $error = 'Failed to add coupon. ' . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Coupon - H&M Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="validation.js"></script>
    <style>
        .form-card {
            background: #fff;
            border: 1px solid #e0e0e0;
            padding: 36px 40px;
            max-width: 680px;
            margin: 0 auto;
        }

        .form-card-title {
            font-size: 13px;
            font-weight: 700;
            color: #000;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 28px;
            padding-bottom: 16px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-card-title i { color: #E50914; }

        .field-group { margin-bottom: 22px; }

        .field-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #000;
        }

        .field-label .req { color: #E50914; margin-left: 2px; }

        .field-input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #e0e0e0;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            color: #333;
            background: #fff;
            transition: border-color 0.2s;
            box-sizing: border-box;
        }

        .field-input:focus {
            outline: none;
            border-color: #000;
        }

        select.field-input {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23333' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
            padding-right: 38px;
        }

        /* Buttons */
        .btn-row {
            display: flex;
            gap: 12px;
            margin-top: 30px;
        }

        .btn-publish {
            flex: 1;
            background: #E50914;
            color: #fff;
            border: none;
            padding: 14px 20px;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            cursor: pointer;
            transition: background 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-publish:hover { background: #b00710; }

        .btn-cancel-link {
            flex: 1;
            background: #fff;
            color: #666;
            border: 1px solid #e0e0e0;
            padding: 14px 20px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-cancel-link:hover { border-color: #000; color: #000; }

        @media (max-width: 768px) {
            .form-card { padding: 24px 16px; }
        }

        .field-input.is-invalid {
            border-color: #E50010 !important;
        }

        small.text-danger {
            color: #E50010;
            font-size: 11px;
            margin-top: 5px;
            display: block;
            font-weight: 500;
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">

    <!-- Top Bar -->
    <div class="top-bar">
        <div style="display:flex;align-items:center;gap:14px;">
            <a href="offer-coupon.php" style="color:#999;font-size:20px;text-decoration:none;transition:color .2s;"
               onmouseover="this.style.color='#000'" onmouseout="this.style.color='#999'">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1>Add New Coupon</h1>
        </div>
        <?php include 'header.php'; ?>
    </div>

    <div class="form-card">
        <div class="form-card-title">
            <i class="fas fa-ticket-alt"></i> Coupon Details
        </div>

        <?php if ($error): ?>
            <div style="background: #ffebee; color: #c62828; padding: 12px 16px; border-radius: 4px; margin-bottom: 20px; font-size: 14px; font-weight: 500;">
                <i class="fas fa-exclamation-circle" style="margin-right: 6px;"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" novalidate id="addCouponForm">

            <!-- Coupon Code -->
            <div class="field-group">
                <label class="field-label" for="couponCode">Coupon Code <span class="req">*</span></label>
                <input type="text" id="couponCode" name="coupon_code" class="field-input"
                       placeholder="e.g., SUMMER25" maxlength="50" 
                       data-validation="required,alphanumeric,min" data-min="3" style="text-transform: uppercase;">
                <small id="coupon_code_error" class="small text-danger" style="display: none;"></small>
            </div>

            <!-- Type -->
            <div class="field-group">
                <label class="field-label" for="couponType">Discount Type <span class="req">*</span></label>
                <select id="couponType" name="coupon_type" class="field-input" data-validation="required,select">
                    <option value="">— Select Type —</option>
                    <option value="Percentage">Percentage (%)</option>
                    <option value="Fixed">Fixed Amount ($)</option>
                </select>
                <small id="coupon_type_error" class="small text-danger" style="display: none;"></small>
            </div>

            <!-- Discount Value -->
            <div class="field-group">
                <label class="field-label" for="couponDiscount">Discount Value <span class="req">*</span></label>
                <input type="number" id="couponDiscount" name="coupon_discount" class="field-input"
                       placeholder="e.g., 25" min="1" max="99999" step="0.01" data-validation="required,number">
                <small id="coupon_discount_error" class="small text-danger" style="display: none;"></small>
            </div>

            <!-- Valid Until -->
            <div class="field-group">
                <label class="field-label" for="validUntil">Valid Until <span class="req">*</span></label>
                <input type="date" id="validUntil" name="valid_until" class="field-input" data-validation="required">
                <small id="valid_until_error" class="small text-danger" style="display: none;"></small>
            </div>

            <!-- Max Uses -->
            <div class="field-group">
                <label class="field-label" for="maxUses">Max Uses <span class="req">*</span></label>
                <input type="number" id="maxUses" name="max_uses" class="field-input"
                       placeholder="e.g., 1000" min="1" max="999999" step="1" data-validation="required,number">
                <small id="max_uses_error" class="small text-danger" style="display: none;"></small>
            </div>

            <!-- Status -->
            <div class="field-group">
                <label class="field-label" for="couponStatus">Status <span class="req">*</span></label>
                <select id="couponStatus" name="coupon_status" class="field-input" data-validation="required,select">
                    <option value="">— Select Status —</option>
                    <option value="ACTIVE">Active</option>
                    <option value="INACTIVE">Inactive</option>
                </select>
                <small id="coupon_status_error" class="small text-danger" style="display: none;"></small>
            </div>

            <!-- Buttons -->
            <div class="btn-row">
                <button type="submit" class="btn-publish">
                    <i class="fas fa-plus"></i> Add Coupon
                </button>
                <a href="offer-coupon.php" class="btn-cancel-link">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>

        </form>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="script.js"></script>
</body>
</html>
