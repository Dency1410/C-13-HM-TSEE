<?php
session_start();
require '../includes/db.php';
require_once '../check_login.php';


$error = '';
$faq = [];

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $query = "SELECT * FROM faqs WHERE id = $id";
    $result = mysqli_query($conn, $query);
    if (mysqli_num_rows($result) > 0) {
        $faq = mysqli_fetch_assoc($result);
    } else {
        header("Location: manage_faqs.php");
        exit();
    }
} else {
    header("Location: manage_faqs.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $question = mysqli_real_escape_string($conn, $_POST['question']);
    $answer = mysqli_real_escape_string($conn, $_POST['answer']);

    if (empty($question) || empty($answer)) {
        $error = "All fields are required.";
    } else {
        $query = "UPDATE faqs SET 
                  question = '$question', 
                  answer = '$answer' 
                  WHERE id = $id";
        
        if (mysqli_query($conn, $query)) {
            header("Location: manage_faqs.php?msg=updated");
            exit();
        } else {
            $error = "Error updating record: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit FAQ - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="validation.js"></script>
    <style>
        .form-control.is-invalid {
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
        <div class="top-bar">
            <h1>Edit Customer Service FAQ</h1>
            <?php include 'header.php'; ?>
        </div>

        <div class="users-section">
            <div class="section-header">
                <h2>FAQ Details</h2>
            </div>
            
            <div class="form-container" style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); max-width: 800px; margin: 0 auto;">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>

                <form method="POST" action="" novalidate id="editFaqForm">
                    <div class="mb-3">
                        <label for="question" class="form-label">Question</label>
                        <input type="text" class="form-control" id="question" name="question" value="<?= htmlspecialchars($faq['question']) ?>" required
                               data-validation="required,min" data-min="5">
                        <small id="question_error" class="small text-danger" style="display:none;"></small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="answer" class="form-label">Answer</label>
                        <textarea class="form-control" id="answer" name="answer" rows="5" required
                                  data-validation="required,min" data-min="10"><?= htmlspecialchars($faq['answer']) ?></textarea>
                        <small id="answer_error" class="small text-danger" style="display:none;"></small>
                    </div>

                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update FAQ</button>
                    <a href="manage_faqs.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
