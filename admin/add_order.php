<?php
session_start();
require '../includes/db.php';
require_once '../check_login.php';

$errors = [];
$success = false;

// Fetch all products for the item selector
$products_result = mysqli_query($conn, "SELECT id, name, price FROM products ORDER BY name ASC");
$products = [];
while ($p = mysqli_fetch_assoc($products_result)) {
    $products[] = $p;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_order'])) {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name']  ?? '');
    $email      = trim($_POST['email']      ?? '');
    $phone      = trim($_POST['phone']      ?? '');
    $address    = trim($_POST['address']    ?? '');
    $city       = trim($_POST['city']       ?? '');
    $zip_code   = trim($_POST['zip_code']   ?? '');
    $state      = trim($_POST['state']      ?? '');
    $country    = trim($_POST['country']    ?? '');
    $status     = trim($_POST['status']     ?? 'Pending');
    $shipping   = 5.99;

    // Validation
    if (!$first_name) $errors[] = 'First name is required.';
    if (!$last_name)  $errors[] = 'Last name is required.';
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
    if (!$phone)    $errors[] = 'Phone is required.';
    if (!$address)  $errors[] = 'Address is required.';
    if (!$city)     $errors[] = 'City is required.';
    if (!$zip_code) $errors[] = 'ZIP code is required.';
    if (!$state)    $errors[] = 'State is required.';
    if (!$country)  $errors[] = 'Country is required.';

    // Collect order items
    $item_product_ids = $_POST['item_product_id'] ?? [];
    $item_quantities  = $_POST['item_quantity']   ?? [];
    $item_prices      = $_POST['item_price']      ?? [];

    $order_items = [];
    $subtotal = 0;
    foreach ($item_product_ids as $i => $pid) {
        $pid = (int)$pid;
        $qty = max(1, (int)($item_quantities[$i] ?? 1));
        $price = (float)($item_prices[$i] ?? 0);
        if ($pid > 0 && $price > 0) {
            $order_items[] = ['product_id' => $pid, 'quantity' => $qty, 'price' => $price];
            $subtotal += $price * $qty;
        }
    }

    if (empty($order_items)) $errors[] = 'Please add at least one product item.';

    if (empty($errors)) {
        $tax   = round($subtotal * 0.08, 2);
        $total = $subtotal + $tax + $shipping;

        $stmt = $conn->prepare("INSERT INTO orders (first_name, last_name, email, phone, address, city, zip_code, state, country, subtotal, tax, shipping, total, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssssdddds", $first_name, $last_name, $email, $phone, $address, $city, $zip_code, $state, $country, $subtotal, $tax, $shipping, $total, $status);

        if ($stmt->execute()) {
            $order_id = $conn->insert_id;
            $stmt_item = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            foreach ($order_items as $item) {
                $stmt_item->bind_param("iiid", $order_id, $item['product_id'], $item['quantity'], $item['price']);
                $stmt_item->execute();
            }
            $_SESSION['toast'] = 'Order #HM' . str_pad($order_id, 6, '0', STR_PAD_LEFT) . ' created successfully.';
            header("Location: order.php");
            exit();
        } else {
            $errors[] = 'Database error: ' . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Order - H&M Admin</title>
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
            padding: 32px;
            margin-bottom: 28px;
        }
        .form-card-title {
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #000;
            margin-bottom: 24px;
            padding-bottom: 14px;
            border-bottom: 2px solid #000;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .form-card-title i { color: #E50010; font-size: 16px; }

        .field-label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #555;
            margin-bottom: 6px;
        }
        .field-input {
            width: 100%;
            padding: 11px 14px;
            border: 1px solid #e0e0e0;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            color: #000;
            background: #fff;
            transition: border-color 0.2s;
        }
        .field-input:focus { outline: none; border-color: #000; }
        .field-input.error { border-color: #E50010; }
        .field-input[readonly] { background: #f9f9f9; color: #666; }

        select.field-input { appearance: none; -webkit-appearance: none; cursor: pointer; }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        .items-table thead th {
            background: #000;
            color: #fff;
            padding: 10px 13px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .items-table tbody tr { border-bottom: 1px solid #e0e0e0; }
        .items-table tbody tr:hover { background: #fafafa; }
        .items-table tbody td { padding: 10px 8px; vertical-align: middle; }
        .items-table select,
        .items-table input[type="number"],
        .items-table input[type="text"] {
            width: 100%;
            padding: 8px 11px;
            border: 1px solid #e0e0e0;
            font-size: 13px;
            font-family: 'Inter', sans-serif;
            background: #fff;
        }
        .items-table select:focus,
        .items-table input:focus { outline: none; border-color: #000; }

        .add-item-btn {
            background: #000;
            color: #fff;
            border: none;
            padding: 9px 20px;
            font-size: 13px;
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .add-item-btn:hover { background: #E50010; }

        .remove-row-btn {
            background: none;
            border: 1px solid #e0e0e0;
            color: #E50010;
            padding: 6px 10px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.2s;
        }
        .remove-row-btn:hover { background: #E50010; color: #fff; border-color: #E50010; }

        /* Totals */
        .totals-box {
            background: #f9f9f9;
            border: 1px solid #e0e0e0;
            padding: 20px 24px;
        }
        .totals-row {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            color: #555;
            margin-bottom: 10px;
        }
        .totals-row.grand-total {
            font-size: 17px;
            font-weight: 700;
            color: #000;
            border-top: 2px solid #000;
            padding-top: 12px;
            margin-top: 6px;
            margin-bottom: 0;
        }

        /* Action Buttons */
        .action-row {
            display: flex;
            gap: 14px;
            align-items: center;
            margin-top: 10px;
        }
        .btn-submit {
            background: #000;
            color: #fff;
            border: none;
            padding: 13px 36px;
            font-size: 13px;
            font-family: 'Inter', sans-serif;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-submit:hover { background: #E50010; }
        .btn-cancel {
            color: #666;
            font-size: 14px;
            text-decoration: none;
            padding: 13px 20px;
            border: 1px solid #e0e0e0;
            display: inline-block;
            transition: all 0.2s;
        }
        .btn-cancel:hover { background: #f5f5f5; color: #000; }

        .field-input.is-invalid, .product-select.is-invalid, .item-qty.is-invalid {
            border-color: #E50010 !important;
        }

        small.text-danger {
            color: #E50010;
            font-size: 11px;
            margin-top: 5px;
            display: block;
            font-weight: 500;
        }

        /* Error list */
        .error-list {
            background: #fff5f5;
            border-left: 4px solid #E50010;
            padding: 14px 18px;
            margin-bottom: 24px;
            font-size: 13px;
            color: #c00;
        }
        .error-list ul { margin: 6px 0 0 16px; padding: 0; }
        .error-list li { margin-bottom: 4px; }

        .form-row-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }
        @media (max-width: 600px) {
            .form-row-grid { grid-template-columns: 1fr; }
        }
        .mb-form { margin-bottom: 18px; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <div class="top-bar">
            <h1>Add New Order</h1>
            <?php include 'header.php'; ?>
        </div>

        <div style="max-width: 960px;">

            <!-- Error Display -->
            <?php if (!empty($errors)): ?>
            <div class="error-list">
                <strong><i class="fas fa-exclamation-circle"></i> Please fix the following errors:</strong>
                <ul>
                    <?php foreach ($errors as $e): ?>
                        <li><?= htmlspecialchars($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <form method="POST" id="addOrderForm" novalidate>

                <!-- ── Customer Information ── -->
                <div class="form-card">
                    <div class="form-card-title">
                        <i class="fas fa-user"></i> Customer Information
                    </div>

                    <div class="form-row-grid mb-form">
                        <div>
                            <label class="field-label">First Name *</label>
                            <input type="text" name="first_name" class="field-input"
                                   data-validation="required,alphabetic,min" data-min="2"
                                   value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" placeholder="John">
                            <small id="first_name_error" class="small text-danger" style="display:none;"></small>
                        </div>
                        <div>
                            <label class="field-label">Last Name *</label>
                            <input type="text" name="last_name" class="field-input"
                                   data-validation="required,alphabetic,min" data-min="2"
                                   value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>" placeholder="Doe">
                            <small id="last_name_error" class="small text-danger" style="display:none;"></small>
                        </div>
                    </div>

                    <div class="form-row-grid mb-form">
                        <div>
                            <label class="field-label">Email Address *</label>
                            <input type="email" name="email" class="field-input"
                                   data-validation="required,email"
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="john@example.com">
                            <small id="email_error" class="small text-danger" style="display:none;"></small>
                        </div>
                        <div>
                            <label class="field-label">Phone Number *</label>
                            <input type="text" name="phone" class="field-input"
                                   data-validation="required,number,min" data-min="10"
                                   value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" placeholder="+91 99999 00000">
                            <small id="phone_error" class="small text-danger" style="display:none;"></small>
                        </div>
                    </div>

                    <div class="mb-form">
                        <label class="field-label">Street Address *</label>
                        <input type="text" name="address" class="field-input"
                               data-validation="required,min" data-min="5"
                               value="<?= htmlspecialchars($_POST['address'] ?? '') ?>" placeholder="123 Fashion Street">
                        <small id="address_error" class="small text-danger" style="display:none;"></small>
                    </div>

                    <div class="form-row-grid mb-form">
                        <div>
                            <label class="field-label">City *</label>
                            <input type="text" name="city" class="field-input"
                                   data-validation="required,min" data-min="2"
                                   value="<?= htmlspecialchars($_POST['city'] ?? '') ?>" placeholder="Mumbai">
                            <small id="city_error" class="small text-danger" style="display:none;"></small>
                        </div>
                        <div>
                            <label class="field-label">ZIP / PIN Code *</label>
                            <input type="text" name="zip_code" class="field-input"
                                   data-validation="required,number,min" data-min="6"
                                   value="<?= htmlspecialchars($_POST['zip_code'] ?? '') ?>" placeholder="400001">
                            <small id="zip_code_error" class="small text-danger" style="display:none;"></small>
                        </div>
                    </div>

                    <div class="form-row-grid mb-form">
                        <div>
                            <label class="field-label">State *</label>
                            <input type="text" name="state" class="field-input"
                                   data-validation="required,min" data-min="2"
                                   value="<?= htmlspecialchars($_POST['state'] ?? '') ?>" placeholder="Maharashtra">
                            <small id="state_error" class="small text-danger" style="display:none;"></small>
                        </div>
                        <div>
                            <label class="field-label">Country *</label>
                            <input type="text" name="country" class="field-input"
                                   data-validation="required,min" data-min="2"
                                   value="<?= htmlspecialchars($_POST['country'] ?? '') ?>" placeholder="India">
                            <small id="country_error" class="small text-danger" style="display:none;"></small>
                        </div>
                    </div>

                    <div style="max-width: 260px;">
                        <label class="field-label">Order Status *</label>
                        <select name="status" class="field-input" data-validation="required,select">
                            <?php foreach (['Pending','Processing','Delivered','Cancelled'] as $s): ?>
                                <option value="<?= $s ?>" <?= ($_POST['status'] ?? 'Pending') === $s ? 'selected' : '' ?>><?= $s ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small id="status_error" class="small text-danger" style="display:none;"></small>
                    </div>
                </div>

                <!-- ── Order Items ── -->
                <div class="form-card">
                    <div class="form-card-title">
                        <i class="fas fa-shopping-bag"></i> Order Items
                    </div>

                    <table class="items-table" id="itemsTable">
                        <thead>
                            <tr>
                                <th style="width:40%">Product</th>
                                <th style="width:15%">Unit Price ($)</th>
                                <th style="width:15%">Quantity</th>
                                <th style="width:20%">Line Total</th>
                                <th style="width:10%"></th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody">
                            <!-- Rows injected by JS or repopulated on error -->
                            <?php
                            $repopulate = $_POST['item_product_id'] ?? [];
                            if (!empty($repopulate)):
                                foreach ($repopulate as $i => $pid):
                                    $qty   = (int)($_POST['item_quantity'][$i] ?? 1);
                                    $price = (float)($_POST['item_price'][$i] ?? 0);
                                    ?>
                                            <tr class="item-row">
                                <td>
                                    <select name="item_product_id[]" class="product-select" data-validation="required,select">
                                        <option value="">— Select Product —</option>
                                        <?php foreach ($products as $prod): ?>
                                            <option value="<?= $prod['id'] ?>"
                                                    data-price="<?= $prod['price'] ?>"
                                                    <?= $prod['id'] == $pid ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($prod['name']) ?> — $<?= number_format($prod['price'], 2) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td><input type="number" name="item_price[]" class="item-price" step="0.01" min="0" value="<?= $price ?>" readonly></td>
                                <td>
                                    <input type="number" name="item_quantity[]" class="item-qty" min="1" value="<?= $qty ?>" data-validation="required,number,min" data-min="1">
                                </td>
                                <td><input type="text" class="item-line-total" value="$<?= number_format($price * $qty, 2) ?>" readonly></td>
                                <td><button type="button" class="remove-row-btn" title="Remove"><i class="fas fa-times"></i></button></td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>

                    <button type="button" class="add-item-btn" id="addItemRow">
                        <i class="fas fa-plus"></i> Add Product
                    </button>
                </div>

                <!-- ── Order Totals ── -->
                <div class="form-card">
                    <div class="form-card-title">
                        <i class="fas fa-calculator"></i> Order Totals
                    </div>
                    <div class="totals-box">
                        <div class="totals-row"><span>Subtotal</span><span id="dispSubtotal">$0.00</span></div>
                        <div class="totals-row"><span>Tax (8%)</span><span id="dispTax">$0.00</span></div>
                        <div class="totals-row"><span>Shipping</span><span>$5.99</span></div>
                        <div class="totals-row grand-total"><span>Grand Total</span><span id="dispTotal">$5.99</span></div>
                    </div>
                </div>

                <!-- ── Actions ── -->
                <div class="action-row">
                    <button type="submit" name="add_order" class="btn-submit">
                        <i class="fas fa-check"></i> Create Order
                    </button>
                    <a href="order.php" class="btn-cancel">
                        <i class="fas fa-arrow-left"></i> Cancel
                    </a>
                </div>

            </form>
        </div><!-- /max-width -->
    </div><!-- /main-content -->

    <!-- Product data for JS -->
    <script>
    const PRODUCTS = <?= json_encode($products) ?>;
    const SHIPPING = 5.99;

    function buildProductOptions(selectedId = '') {
        let opts = '<option value="">— Select Product —</option>';
        PRODUCTS.forEach(p => {
            const sel = (p.id == selectedId) ? 'selected' : '';
            opts += `<option value="${p.id}" data-price="${p.price}" ${sel}>
                        ${p.name} — $${parseFloat(p.price).toFixed(2)}
                     </option>`;
        });
        return opts;
    }

    function makeRow() {
        const tr = document.createElement('tr');
        tr.className = 'item-row';
        tr.innerHTML = `
            <td>
                <select name="item_product_id[]" class="product-select" data-validation="required,select">
                    ${buildProductOptions()}
                </select>
            </td>
            <td><input type="number" name="item_price[]" class="item-price" step="0.01" min="0" value="0" readonly></td>
            <td><input type="number" name="item_quantity[]" class="item-qty" min="1" value="1" data-validation="required,number,min" data-min="1"></td>
            <td><input type="text" class="item-line-total" value="$0.00" readonly></td>
            <td><button type="button" class="remove-row-btn" title="Remove"><i class="fas fa-times"></i></button></td>
        `;
        return tr;
    }

    function recalcRow(row) {
        const select = row.querySelector('.product-select');
        const opt    = select.options[select.selectedIndex];
        const price  = parseFloat(opt ? (opt.dataset.price || 0) : 0);
        const qty    = parseInt(row.querySelector('.item-qty').value) || 1;
        row.querySelector('.item-price').value = price.toFixed(2);
        row.querySelector('.item-line-total').value = '$' + (price * qty).toFixed(2);
        recalcTotals();
    }

    function recalcTotals() {
        let subtotal = 0;
        document.querySelectorAll('.item-row').forEach(row => {
            const price = parseFloat(row.querySelector('.item-price').value) || 0;
            const qty   = parseInt(row.querySelector('.item-qty').value) || 1;
            subtotal += price * qty;
        });
        const tax   = subtotal * 0.08;
        const total = subtotal + tax + SHIPPING;
        document.getElementById('dispSubtotal').textContent = '$' + subtotal.toFixed(2);
        document.getElementById('dispTax').textContent      = '$' + tax.toFixed(2);
        document.getElementById('dispTotal').textContent    = '$' + total.toFixed(2);
    }

    function bindRowEvents(row) {
        row.querySelector('.product-select').addEventListener('change', () => recalcRow(row));
        row.querySelector('.item-qty').addEventListener('input', () => recalcRow(row));
        row.querySelector('.remove-row-btn').addEventListener('click', () => {
            if (document.querySelectorAll('.item-row').length > 1) {
                row.remove();
                recalcTotals();
            }
        });
    }

    document.getElementById('addItemRow').addEventListener('click', () => {
        const row = makeRow();
        document.getElementById('itemsBody').appendChild(row);
        bindRowEvents(row);
    });

    // Bind existing rows (repopulated on error)
    document.querySelectorAll('.item-row').forEach(row => {
        bindRowEvents(row);
        // Refresh product select
        const pid = row.querySelector('.product-select').value;
        row.querySelector('.product-select').innerHTML = buildProductOptions(pid);
        bindRowEvents(row);
    });

    // Add first row if empty
    if (document.querySelectorAll('.item-row').length === 0) {
        const row = makeRow();
        document.getElementById('itemsBody').appendChild(row);
        bindRowEvents(row);
    }

    recalcTotals();
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
</body>
</html>
