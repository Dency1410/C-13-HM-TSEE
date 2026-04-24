<?php
require 'includes/db.php';

$sql = "CREATE TABLE IF NOT EXISTS faqs (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    question TEXT NOT NULL,
    answer TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $sql)) {
    echo "Table 'faqs' created successfully.\n";

    // Insert dummy data if table is empty
    $check = mysqli_query($conn, "SELECT id FROM faqs LIMIT 1");
    if (mysqli_num_rows($check) == 0) {
        $faqs = [
            [
                'q' => 'Where is my refund?',
                'a' => 'Once we receive your return, we will process it within 5-7 business days. Your refund will be credited to your original payment method. Please allow an additional 5-10 business days for the refund to appear in your account, depending on your bank or card issuer.'
            ],
            [
                'q' => 'Where is my return?',
                'a' => 'You can track your return by using the tracking number provided when you shipped your package. Once we receive your return at our warehouse, you\'ll receive a confirmation email. Returns typically take 7-14 business days depending on your location and chosen shipping method.'
            ],
            [
                'q' => 'I am missing items from my order',
                'a' => 'We apologize for any inconvenience. If you\'re missing items from your order, please contact our customer service team with your order number and details of the missing items. We\'ll investigate immediately and arrange for the missing items to be sent or issue a refund.'
            ],
            [
                'q' => 'I have received a faulty item',
                'a' => 'We\'re sorry you received a faulty item. Please contact our customer service with photos of the fault and your order number. We\'ll arrange for a replacement or full refund, including return shipping costs. Quality is important to us, and we\'ll make this right.'
            ],
            [
                'q' => 'I want to cancel/adjust my order',
                'a' => 'Orders can be cancelled or modified within 1 hour of placement by logging into your account and visiting "My Orders." After this time, orders are processed and cannot be changed. If your order has already shipped, you can return it using our standard return process once you receive it.'
            ]
        ];

        $stmt = $conn->prepare("INSERT INTO faqs (question, answer) VALUES (?, ?)");
        foreach ($faqs as $faq) {
            $stmt->bind_param("ss", $faq['q'], $faq['a']);
            $stmt->execute();
        }
        echo "Initial faqs data inserted successfully.\n";
    } else {
        echo "FAQs data already exists.\n";
    }
} else {
    echo "Error creating table: " . mysqli_error($conn) . "\n";
}
?>
