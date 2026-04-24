<?php
// ============================================================
// mail_content.php — PHP_Project_2025-26
// HTML email body templates for all notification types.
// Include this file wherever you need to send emails.
// Usage: $body = getOtpEmailBody($otp);
//        $mail->Body = $body;
// ============================================================

// ---- Shared inline CSS for all email templates (used by _wrapEmail) ----
function _emailStyles()
{
    return '
        body { font-family: "Segoe UI", Arial, sans-serif; background:#f4f7f7; margin:0; padding:0; }
        .email-wrapper { max-width:620px; margin:40px auto; background:#ffffff;
            border-radius:10px; border:1px solid #e5e7eb;
            box-shadow:0 4px 12px rgba(0,0,0,0.08); overflow:hidden; }
        .header { background:#0d9488; padding:18px; text-align:center;
            color:white; font-size:22px; font-weight:700; }
        .content { padding:25px 30px; color:#0f3d3a; font-size:16px; }
        .highlight-box { margin:20px auto; padding:14px 0; width:60%;
            text-align:center; background:#d4af37; border-radius:8px;
            color:white; font-size:26px; font-weight:bold; letter-spacing:5px; }
        .info-table { width:100%; border-collapse:collapse; margin:16px 0; }
        .info-table td { padding:8px 12px; border-bottom:1px solid #e5e7eb; font-size:15px; }
        .info-table td:first-child { font-weight:600; color:#0d9488; width:40%; }
        .status-badge { display:inline-block; padding:4px 14px; border-radius:20px;
            font-size:13px; font-weight:600; background:#0d9488; color:white; }
        .note { font-size:14px; color:#d97706; margin-top:10px; }
        .footer { margin-top:24px; padding:16px; text-align:center;
            font-size:12px; color:#6b7280; background:#f1f5f9; }
    ';
}

// ---- Helper: wrap content in the standard email shell ----
function _wrapEmail($headerTitle, $bodyContent)
{
    $styles = _emailStyles();
    return "
    <html>
    <head><style>{$styles}</style></head>
    <body>
        <div class='email-wrapper'>
            <div class='header'>{$headerTitle}</div>
            <div class='content'>{$bodyContent}</div>
            <div class='footer'>This is an automated email from H&amp;M Store. Please do not reply.</div>
        </div>
</body>
    </html>";
}

function getForgotPasswordOtpEmailBody($otp, $name)
{
    $headerTitle = "H&M - Password Reset";
    $bodyContent = "
        <p>Hi <b>" . htmlspecialchars($name) . "</b>,</p>
        <p>We received a request to reset the password for your account.</p>
        <div class=\"highlight-box\">{$otp}</div>
        <p>Please enter this OTP to continue. It is valid for exactly <strong>2 minutes</strong>.</p>
        <p class=\"note\">If you didn't ask to reset your password, you can safely ignore this email.</p>
    ";
    return _wrapEmail($headerTitle, $bodyContent);
}
?>
