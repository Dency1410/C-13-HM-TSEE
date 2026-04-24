
<?php
$header_name   = $_SESSION['admin_name']   ?? ($_SESSION['user_name'] ?? 'Admin User');
$header_role   = $_SESSION['user_role']    ?? 'Administrator';
$header_avatar = !empty($_SESSION['admin_avatar'])
    ? '../' . $_SESSION['admin_avatar']
    : 'https://ui-avatars.com/api/?name=' . urlencode($header_name) . '&background=000000&color=fff&size=40';
?>
<div class="user-info">
    <div class="user-details" style="text-align: right;">
        <h6 style="margin: 0; font-weight: 700; color: #111; font-size: 14px;"><?php echo htmlspecialchars($header_name); ?></h6>
        <p style="margin: 0; font-size: 11px; color: #888; text-transform: uppercase; letter-spacing: 0.5px;"><?php echo htmlspecialchars($header_role); ?></p>
    </div>
    <img src="<?php echo htmlspecialchars($header_avatar); ?>" alt="Admin" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 1px solid #eee;">
</div>
