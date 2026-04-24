<?php
session_start();
require_once '../includes/db.php';
require_once '../check_login.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offers & Coupons - H&M Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">

    <style>
       
      
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-4px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .input-wrapper {
            position: relative;
        }
        .input-wrapper .validation-icon {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 13px;
            pointer-events: none;
        }
        .input-wrapper .validation-icon.success { color: #28a745; }
        .input-wrapper .validation-icon.error   { color: #E50914; }

        /* Discount type toggle hint */
        .discount-hint {
            font-size: 11px;
            color: #888;
            margin-top: 3px;
        }
        /* Coupon code badge preview */
        #codePreview, #editCodePreview {
            display: inline-block;
            background: #000;
            color: #fff;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1px;
            padding: 2px 10px;
            margin-top: 5px;
            border-radius: 2px;
            min-height: 22px;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php' ?>

    <div class="main-content">
        <div class="top-bar">
            <h1>Offers & Coupons</h1>
            <?php include 'header.php'?>
        </div>

        <div class="users-section">
            <div class="section-header">
                <h2>Active Coupons</h2>
                <a class="btn-add" href="add_coupon.php">
                    <i class="fas fa-plus"></i>Add New Coupon
                </a>
            </div>
            <div class="table-responsive-wrap">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Coupon Code</th>
                        <th>Discount</th>
                        <th>Type</th>
                        <th>Valid Until</th>
                        <th>Uses</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="couponTableBody">
                    <?php
                    $res = mysqli_query($conn, "SELECT * FROM coupons ORDER BY created_at DESC");
                    if ($res && mysqli_num_rows($res) > 0) {
                        while ($row = mysqli_fetch_assoc($res)) {
                            $discount_display = ($row['discount_type'] === 'Percentage') 
                                ? number_format($row['discount_value'], 0) . '%' 
                                : '$' . number_format($row['discount_value'], 2);
                            
                            $status_class = ($row['status'] === 'ACTIVE') ? 'badge-active' : 'badge-inactive';
                            
                            echo '<tr>';
                            echo '<td><strong>' . htmlspecialchars($row['coupon_code']) . '</strong></td>';
                            echo '<td>' . $discount_display . '</td>';
                            echo '<td>' . htmlspecialchars($row['discount_type']) . '</td>';
                            echo '<td>' . date('M d, Y', strtotime($row['valid_until'])) . '</td>';
                            echo '<td>' . (int)$row['current_uses'] . ' / ' . (int)$row['max_uses'] . '</td>';
                            echo '<td><span class="badge-status ' . $status_class . '">' . htmlspecialchars($row['status']) . '</span></td>';
                            echo '<td>';
                            echo '<button class="action-btn edit" onclick="window.location.href=\'edit_coupon.php?id=' . $row['id'] . '\'"><i class="fas fa-edit"></i></button>';
                            echo '<button class="action-btn delete" onclick="deleteCoupon(' . $row['id'] . ', \'' . addslashes($row['coupon_code']) . '\')"><i class="fas fa-trash"></i></button>';
                            echo '</td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="7" style="text-align:center; padding:30px;">No coupons found.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
            </div>
            
            <!-- DELETE SCRIPT -->
            <script>
            function deleteCoupon(id, code) {
                if(confirm('Are you sure you want to delete coupon ' + code + '?')) {
                    window.location.href = 'delete_coupon.php?id=' + id;
                }
            }
            </script>
        </div>
    </div>

    <!-- jQuery & jQuery Validate (must load before Bootstrap JS) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
</body>
</html>