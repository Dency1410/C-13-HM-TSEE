<?php require_once '../check_login.php'; ?>
<?php
require '../includes/db.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['product_name'] ?? '');
    $category_id = (int)($_POST['product_category'] ?? 0);
    $gender = mysqli_real_escape_string($conn, $_POST['product_gender'] ?? '');
    $price = (float)($_POST['product_price'] ?? 0);
    $old_price = (float)($_POST['product_old_price'] ?? 0);
    $stock = (int)($_POST['product_stock'] ?? 0);
    $status = mysqli_real_escape_string($conn, $_POST['product_status'] ?? '');
    $description = mysqli_real_escape_string($conn, $_POST['product_description'] ?? '');

    // Server-side validation
    $errors = [];
    if (empty($name)) $errors[] = "Product name is required.";
    if ($category_id <= 0) $errors[] = "A valid category must be selected.";
    if (empty($gender)) $errors[] = "Gender is required.";
    if ($price <= 0) $errors[] = "Price must be greater than zero.";
    if ($stock < 0) $errors[] = "Stock cannot be negative.";
    if (empty($description)) $errors[] = "Description is required.";

    if (empty($errors)) {
        // Image upload
        $imagePath = '';
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
            $uploadDir = '../uploads/';
            if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

            $fileName = time() . '_' . basename($_FILES['product_image']['name']);
            $targetFilePath = $uploadDir . $fileName;

            $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
            $allowTypes = array('jpg', 'png', 'jpeg', 'webp');
            if (in_array(strtolower($fileType), $allowTypes)) {
                if (move_uploaded_file($_FILES["product_image"]["tmp_name"], $targetFilePath)) {
                    $imagePath = 'uploads/' . $fileName;
                }
            }
        }

        if (empty($imagePath)) {
            $errors[] = "Product image is required.";
        }
    }

    if (empty($errors)) {
        $insert_product = "INSERT INTO products (name, price, old_price, category_id, gender, image, stock, status, description) 
                           VALUES ('$name', $price, $old_price, $category_id, '$gender', '$imagePath', $stock, '$status', '$description')";

        if (mysqli_query($conn, $insert_product)) {
            $product_id = mysqli_insert_id($conn);

            // Insert sizes
            if (isset($_POST['sizes']) && is_array($_POST['sizes'])) {
                foreach ($_POST['sizes'] as $size_id) {
                    $size_id = (int)$size_id;
                    mysqli_query($conn, "INSERT INTO product_sizes (product_id, size_id) VALUES ($product_id, $size_id)");
                }
            }
            header("Location: products.php");
            exit;
        } else {
            $message = "Error: " . mysqli_error($conn);
        }
    } else {
        $message = "Validation Error: " . implode(" ", $errors);
    }
}

$categories = mysqli_query($conn, "SELECT * FROM categories");
$sizes = mysqli_query($conn, "SELECT * FROM sizes");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - H&M Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="../js/form-validation.js"></script>
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

        .form-card-title i {
            color: #E50914;
        }

        .field-group {
            margin-bottom: 22px;
        }

        .field-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #000;
        }

        .field-label .req {
            color: #E50914;
            margin-left: 2px;
        }

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

        .two-col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .upload-zone {
            border: 2px dashed #d0d0d0;
            padding: 36px 20px;
            text-align: center;
            cursor: pointer;
            background: #fafafa;
            transition: all 0.2s;
            position: relative;
        }

        .upload-zone:hover {
            border-color: #000;
            background: #f5f5f5;
        }

        .upload-zone.has-image {
            border-style: solid;
            border-color: #000;
            padding: 0;
        }

        .upload-zone i.upload-icon {
            font-size: 32px;
            color: #bbb;
            display: block;
            margin-bottom: 10px;
        }

        .upload-zone p {
            margin: 0 0 5px;
            font-size: 14px;
            color: #555;
        }

        .upload-zone small {
            font-size: 12px;
            color: #aaa;
        }

        #previewImg {
            width: 100%;
            max-height: 240px;
            object-fit: cover;
            display: none;
        }

        .upload-overlay {
            display: none;
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .upload-zone.has-image:hover .upload-overlay {
            display: flex;
        }

        .upload-overlay button {
            background: #fff;
            border: none;
            padding: 8px 14px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
        }

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
        }

        .btn-publish:hover {
            background: #b00710;
        }

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
            text-decoration: none;
        }

        .btn-cancel-link:hover {
            border-color: #000;
            color: #000;
        }

        .is-invalid {
            border-color: #E50914 !important;
        }

        .is-valid {
            border-color: #28a745 !important;
        }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <div class="top-bar">
            <div style="display:flex;align-items:center;gap:14px;">
                <a href="products.php" style="color:#999;font-size:20px;text-decoration:none;"><i class="fas fa-arrow-left"></i></a>
                <h1>Add New Product</h1>
            </div>
            <?php include 'header.php'; ?>
        </div>

        <div class="form-card">
            <div class="form-card-title"><i class="fas fa-box"></i> Product Details</div>


            <form method="POST" enctype="multipart/form-data" id="addProductForm">
                <div class="field-group">
                    <label class="field-label">Product Image <span class="req">*</span></label>
                    <div class="upload-zone" id="uploadZone" onclick="triggerUpload()">
                        <i class="fas fa-cloud-upload-alt upload-icon" id="uploadIcon"></i>
                        <p id="uploadText">Click to upload product image</p>
                        <small id="uploadHint">JPG, PNG, WebP · Max 5 MB</small>
                        <img id="previewImg" src="">
                        <div class="upload-overlay" id="uploadOverlay"></div>
                    </div>
                    <input type="file" id="imageInput" name="product_image" accept="image/*"
                        onchange="previewImage(event)"
                        data-validation="required,filesize,filetype"
                        data-filesize="5"
                        data-filetype=".jpg,.png,.jpeg,.webp">
                    <small id="product_image_error" class="small text-danger"></small>
                </div>

                <div class="field-group">
                    <label class="field-label">Product Name <span class="req">*</span></label>
                    <input type="text" name="product_name" class="field-input"
                        data-validation="required,min" data-min="3">
                    <small id="product_name_error" class="small text-danger"></small>
                </div>

                <div class="two-col">
                    <div class="field-group">
                        <label class="field-label">Category <span class="req">*</span></label>
                        <select name="product_category" class="field-input" data-validation="required,select">
                            <option value="">Select Category</option>
                            <?php while ($c = mysqli_fetch_assoc($categories)) echo "<option value='{$c['id']}'>{$c['name']}</option>"; ?>
                        </select>
                        <small id="product_category_error" class="small text-danger"></small>
                    </div>
                    <div class="field-group">
                        <label class="field-label">Gender <span class="req">*</span></label>
                        <select name="product_gender" class="field-input" data-validation="required,select">
                            <option value="">Select Gender</option>
                            <option value="Men">Men</option>
                            <option value="Ladies">Ladies</option>
                            <option value="Kids Girl">Kids Girl</option>
                            <option value="Kids Boy">Kids Boy</option>
                        </select>
                        <small id="product_gender_error" class="small text-danger"></small>
                    </div>
                </div>

                <div class="field-group">
                    <label class="field-label">Description <span class="req">*</span></label>
                    <textarea name="product_description" class="field-input" rows="4"
                        data-validation="required,min" data-min="10"></textarea>
                    <small id="product_description_error" class="small text-danger"></small>
                </div>

                <div class="field-group">
                    <label class="field-label">Sizes</label>
                    <div>
                        <?php while ($s = mysqli_fetch_assoc($sizes)): ?>
                            <label class="me-3"><input type="checkbox" data-validation="required" id="sizes" name="sizes[]" value="<?= $s['id'] ?>"> <?= htmlspecialchars($s['name']) ?></label>
                        <?php endwhile; ?>
                        <small id="sizes_error" class="small text-danger"></small>
                    </div>
                </div>

                <div class="two-col">
                    <div class="field-group">
                        <label class="field-label">Price ($) <span class="req">*</span></label>
                        <input type="number" name="product_price" class="field-input" step="0.01"
                            data-validation="required,number" min="0.01">
                        <small id="product_price_error" class="small text-danger"></small>
                    </div>
                    <div class="field-group">
                        <label class="field-label">Old Price ($)</label>
                        <input type="number" name="product_old_price" class="field-input" step="0.01"
                            data-validation="required,number" min="0">
                        <small id="product_old_price_error" class="small text-danger"></small>
                    </div>
                </div>

                <div class="two-col">
                    <div class="field-group">
                        <label class="field-label">Stock <span class="req">*</span></label>
                        <input type="number" name="product_stock" class="field-input"
                            data-validation="required,number" min="0">
                        <small id="product_stock_error" class="small text-danger"></small>
                    </div>
                </div>

                <div class="field-group">
                    <label class="field-label">Status <span class="req">*</span></label>
                    <select name="product_status" class="field-input" data-validation="required,select">
                        <option value="In Stock">In Stock</option>
                        <option value="Out of Stock">Out of Stock</option>
                        <option value="Coming Soon">Coming Soon</option>
                    </select>
                    <small id="product_status_error" class="small text-danger"></small>
                </div>

                <div class="btn-row">
                    <button type="submit" class="btn-publish"><i class="fas fa-plus"></i> Add Product</button>
                    <a href="products.php" class="btn-cancel-link"><i class="fas fa-times"></i> Cancel</a>
                </div>
            </form>
        </div>
    </div>
    <script>
        function triggerUpload() {
            document.getElementById('imageInput').click();
        }

        function previewImage(event) {
            const file = event.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('previewImg').src = e.target.result;
                document.getElementById('previewImg').style.display = 'block';
                document.getElementById('uploadIcon').style.display = 'none';
                document.getElementById('uploadText').style.display = 'none';
                document.getElementById('uploadHint').style.display = 'none';
                document.getElementById('uploadZone').classList.add('has-image');
            };
            reader.readAsDataURL(file);
        }
    </script>
</body>

</html>