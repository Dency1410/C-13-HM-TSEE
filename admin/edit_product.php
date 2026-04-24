<?php
require '../includes/db.php';
require_once '../check_login.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id === 0) {
    header("Location: products.php");
    exit();
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['product_name']);
    $category_id = (int)$_POST['product_category'];
    $gender = mysqli_real_escape_string($conn, $_POST['product_gender']);
    $price = (float)$_POST['product_price'];
    $old_price = (float)$_POST['product_old_price'];
    $stock = (int)$_POST['product_stock'];
    $status = mysqli_real_escape_string($conn, $_POST['product_status']);
    $description = mysqli_real_escape_string($conn, $_POST['product_description']);
    // Check if new image is uploaded
    $imagePathQuery = "";
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
                $imagePathQuery = ", image='$imagePath'";
            }
        }
    }

    $update_product = "UPDATE products 
                       SET name='$name', price=$price, old_price=$old_price, category_id=$category_id, gender='$gender', stock=$stock, status='$status', description='$description' $imagePathQuery 
                       WHERE id=$id";
    
    if (mysqli_query($conn, $update_product)) {
        // Update sizes (delete old, insert new)
        mysqli_query($conn, "DELETE FROM product_sizes WHERE product_id=$id");

        if (isset($_POST['sizes']) && is_array($_POST['sizes'])) {
            foreach ($_POST['sizes'] as $size_id) {
                $size_id = (int)$size_id;
                mysqli_query($conn, "INSERT INTO product_sizes (product_id, size_id) VALUES ($id, $size_id)");
            }
        }
        $message = "Product updated successfully!";
        header("Location: products.php");
        exit;
    } else {
        $message = "Error: " . mysqli_error($conn);
    }
}

// Fetch current product
$product_query = mysqli_query($conn, "SELECT * FROM products WHERE id=$id");
if (mysqli_num_rows($product_query) === 0) {
    header("Location: products.php");
    exit();
}
$product = mysqli_fetch_assoc($product_query);

// Fetch product's current sizes
$current_sizes_query = mysqli_query($conn, "SELECT size_id FROM product_sizes WHERE product_id=$id");
$current_sizes = [];
while ($r = mysqli_fetch_assoc($current_sizes_query)) {
    $current_sizes[] = $r['size_id'];
}

$categories = mysqli_query($conn, "SELECT * FROM categories");
$sizes = mysqli_query($conn, "SELECT * FROM sizes");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - H&M Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="validation.js"></script>
    <style>
        .form-card {
            background: #fff; border: 1px solid #e0e0e0;
            padding: 36px 40px; max-width: 680px; margin: 0 auto;
        }
        .form-card-title {
            font-size: 13px; font-weight: 700; color: #000;
            text-transform: uppercase; letter-spacing: 1.5px;
            margin-bottom: 28px; padding-bottom: 16px; border-bottom: 1px solid #e0e0e0;
            display: flex; align-items: center; gap: 10px;
        }
        .form-card-title i { color: #E50914; }
        .field-group { margin-bottom: 22px; }
        .field-label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.8px; color: #000; }
        .field-label .req { color: #E50914; margin-left: 2px; }
        .field-input { width: 100%; padding: 12px 15px; border: 1px solid #e0e0e0; font-size: 14px; font-family: 'Inter', sans-serif; color: #333; background: #fff; transition: border-color 0.2s; box-sizing: border-box; }
        .field-input:focus { outline: none; border-color: #000; }
        select.field-input { cursor: pointer; appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23333' d='M6 9L1 4h10z'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 14px center; padding-right: 38px; }
        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .upload-zone { border: 2px dashed #d0d0d0; padding: 36px 20px; text-align: center; cursor: pointer; background: #fafafa; transition: all 0.2s; position: relative; }
        .upload-zone:hover { border-color: #000; background: #f5f5f5; }
        .upload-zone.has-image { border-style: solid; border-color: #000; padding: 0; }
        .upload-zone i.upload-icon { font-size: 32px; color: #bbb; display: block; margin-bottom: 10px; }
        .upload-zone p { margin: 0 0 5px; font-size: 14px; color: #555; }
        .upload-zone small { font-size: 12px; color: #aaa; }
        #previewImg { width: 100%; max-height: 240px; object-fit: cover; display: none; }
        .upload-overlay { display: none; position: absolute; inset: 0; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; gap: 10px; }
        .upload-zone.has-image:hover .upload-overlay { display: flex; }
        .upload-overlay button { background: #fff; border: none; padding: 8px 14px; font-size: 13px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 5px; }
        .btn-row { display: flex; gap: 12px; margin-top: 30px; }
        .btn-publish { flex: 1; background: #222; color: #fff; border: none; padding: 14px 20px; font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.2px; cursor: pointer;  }
        .btn-publish:hover { background: #000; }
        .btn-cancel-link { flex: 1; background: #fff; color: #666; border: 1px solid #e0e0e0; padding: 14px 20px; font-size: 13px; font-weight: 500; cursor: pointer; display: flex; align-items: center; justify-content: center; text-decoration: none; }
        .btn-cancel-link:hover { border-color: #000; color: #000; }

    </style>
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="main-content">
    <div class="top-bar">
        <div style="display:flex;align-items:center;gap:14px;">
            <a href="products.php" style="color:#999;font-size:20px;text-decoration:none;"><i class="fas fa-arrow-left"></i></a>
            <h1>Edit Product</h1>
        </div>
        <?php include 'header.php'; ?>
    </div>
    
    <div class="form-card">
        <div class="form-card-title"><i class="fas fa-edit"></i> Edit Product</div>
        <?php if($message) echo "<div class='alert alert-info'>$message</div>"; ?>
        
        <form method="POST" enctype="multipart/form-data" novalidate id="editProductForm">
            <div class="field-group">
                <label class="field-label">Product Image</label>
                <?php 
                    $hasImage = !empty($product['image']);
                    $imgUrl = $hasImage ? '../' . $product['image'] : '';
                ?>
                <div class="upload-zone <?= $hasImage ? 'has-image' : '' ?>" id="uploadZone" onclick="triggerUpload()">
                    <i class="fas fa-cloud-upload-alt upload-icon" id="uploadIcon" style="<?= $hasImage ? 'display:none;' : '' ?>"></i>
                    <p id="uploadText" style="<?= $hasImage ? 'display:none;' : '' ?>">Click to change product image</p>
                    <small id="uploadHint" style="<?= $hasImage ? 'display:none;' : '' ?>">JPG, PNG, WebP · Max 5 MB</small>
                    <img id="previewImg" src="<?= htmlspecialchars($imgUrl) ?>" style="<?= $hasImage ? 'display:block;' : '' ?>">
                    <div class="upload-overlay" id="uploadOverlay">
                        <button type="button" onclick="triggerUpload(event)"><i class="fas fa-camera"></i> Change Image</button>
                    </div>
                </div>
                <!-- Not required for editing -->
                <input type="file" id="imageInput" name="product_image" accept="image/*" style="display:none;" onchange="previewImage(event)"
                       data-validation="filesize,filetype" data-filesize="5" data-filetype="jpg,jpeg,png,webp">
                <small id="product_image_error" class="small text-danger" style="display:none;"></small>
            </div>
            
            <div class="field-group">
                <label class="field-label">Product Name <span class="req">*</span></label>
                <input type="text" name="product_name" class="field-input" value="<?= htmlspecialchars($product['name']) ?>" required
                       data-validation="required,min" data-min="3">
                <small id="product_name_error" class="small text-danger" style="display:none;"></small>
            </div>
            
            <div class="two-col">
                <div class="field-group">
                    <label class="field-label">Category <span class="req">*</span></label>
                    <select name="product_category" class="field-input" required data-validation="required,select">
                        <option value="">Select Category</option>
                        <?php 
                        while($c = mysqli_fetch_assoc($categories)) {
                            $sel = ($c['id'] == $product['category_id']) ? 'selected' : '';
                            echo "<option value='{$c['id']}' $sel>{$c['name']}</option>";
                        }
                        ?>
                    </select>
                    <small id="product_category_error" class="small text-danger" style="display:none;"></small>
                </div>
                <div class="field-group">
                    <label class="field-label">Gender <span class="req">*</span></label>
                    <select name="product_gender" class="field-input" required data-validation="required,select">
                        <option value="">Select Gender</option>
                        <option value="Men" <?= $product['gender'] == 'Men' ? 'selected' : '' ?>>Men</option>
                        <option value="Ladies" <?= $product['gender'] == 'Ladies' ? 'selected' : '' ?>>Ladies</option>
                        <option value="Kids Girl" <?= $product['gender'] == 'Kids Girl' ? 'selected' : '' ?>>Kids Girl</option>
                        <option value="Kids Boy" <?= $product['gender'] == 'Kids Boy' ? 'selected' : '' ?>>Kids Boy</option>
                    </select>
                    <small id="product_gender_error" class="small text-danger" style="display:none;"></small>
                </div>
            </div>

            <div class="field-group">
                <label class="field-label">Description <span class="req">*</span></label>
                <textarea name="product_description" class="field-input" rows="4" required 
                          data-validation="required,min" data-min="10"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
                <small id="product_description_error" class="small text-danger" style="display:none;"></small>
            </div>

            <div class="field-group">
                <label class="field-label">Sizes</label>
                <div>
                <?php while($s = mysqli_fetch_assoc($sizes)): 
                    $checked = in_array($s['id'], $current_sizes) ? 'checked' : '';
                ?>
                    <label class="me-3"><input type="checkbox" name="sizes[]" value="<?= $s['id'] ?>" <?= $checked ?>> <?= htmlspecialchars($s['name']) ?></label>
                <?php endwhile; ?>
                </div>
            </div>
            
            <div class="two-col">
                <div class="field-group">
                    <label class="field-label">Price ($) <span class="req">*</span></label>
                    <input type="number" name="product_price" class="field-input" step="0.01" value="<?= $product['price'] ?>" required
                           data-validation="required,number">
                    <small id="product_price_error" class="small text-danger" style="display:none;"></small>
                </div>
                <div class="field-group">
                    <label class="field-label">Old Price ($)</label>
                    <input type="number" name="product_old_price" class="field-input" step="0.01" value="<?= $product['old_price'] ?? '' ?>"
                           data-validation="number">
                    <small id="product_old_price_error" class="small text-danger" style="display:none;"></small>
                </div>
            </div>
            
            <div class="two-col">
                <div class="field-group">
                    <label class="field-label">Stock <span class="req">*</span></label>
                    <input type="number" name="product_stock" class="field-input" value="<?= $product['stock'] ?>" required
                           data-validation="required,number">
                    <small id="product_stock_error" class="small text-danger" style="display:none;"></small>
                </div>
            </div>
            
            <div class="field-group">
                <label class="field-label">Status <span class="req">*</span></label>
                <select name="product_status" class="field-input" required data-validation="required,select">
                    <option value="In Stock" <?= $product['status'] == 'In Stock' ? 'selected' : '' ?>>In Stock</option>
                    <option value="Out of Stock" <?= $product['status'] == 'Out of Stock' ? 'selected' : '' ?>>Out of Stock</option>
                    <option value="Coming Soon" <?= $product['status'] == 'Coming Soon' ? 'selected' : '' ?>>Coming Soon</option>
                </select>
                <small id="product_status_error" class="small text-danger" style="display:none;"></small>
            </div>
            
            <div class="btn-row">
                <button type="submit" class="btn-publish"><i class="fas fa-save"></i> Update Product</button>
                <a href="products.php" class="btn-cancel-link"><i class="fas fa-times"></i> Cancel</a>
            </div>
        </form>
    </div>
</div>
<script>
function triggerUpload(e) { 
    if(e) e.stopPropagation();
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
