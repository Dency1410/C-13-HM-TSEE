<?php
require 'includes/db.php';

$sql = "CREATE TABLE IF NOT EXISTS contact_options (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    phone VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    availability VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $sql)) {
    echo "Table 'contact_options' created successfully.\n";

    // Insert initial data if the table is empty
    $check = mysqli_query($conn, "SELECT id FROM contact_options LIMIT 1");
    if (mysqli_num_rows($check) == 0) {
        $contacts = [
            [
                'title' => 'Chat with us',
                'description' => 'Our AI chatbot is there for you around the clock and answers your questions quickly and reliably. Whether it\'s about your order, delivery, return, refund or any other topic - just ask your question directly in the chat. When you\'re logged in, it can give you even better answers.',
                'phone' => '',
                'email' => '',
                'availability' => ''
            ],
            [
                'title' => 'Chat',
                'description' => 'Click on the speech bubble icon bottom in the right corner to start the chat.',
                'phone' => '',
                'email' => '',
                'availability' => 'Monday – Sunday: 8.00 – 22.00'
            ],
            [
                'title' => 'Call us',
                'description' => 'Free of charge',
                'phone' => '1800-889-8000',
                'email' => '',
                'availability' => 'Monday – Sunday: 8.00 – 22.00'
            ]
        ];

        $stmt = $conn->prepare("INSERT INTO contact_options (title, description, phone, email, availability) VALUES (?, ?, ?, ?, ?)");
        
        foreach ($contacts as $c) {
            $stmt->bind_param("sssss", $c['title'], $c['description'], $c['phone'], $c['email'], $c['availability']);
            $stmt->execute();
        }
        
        echo "Initial contact data inserted successfully.\n";
    } else {
        echo "Data already exists.\n";
    }
} else {
    echo "Error creating table: " . mysqli_error($conn) . "\n";
}
?>
