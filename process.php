<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '';
    $phone = isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '';
    $exam = isset($_POST['target_exam']) ? htmlspecialchars($_POST['target_exam']) : '';
    
    // Save to Database
    require_once 'config/db.php';
    $conn = getDB();
    $stmt = $conn->prepare("INSERT INTO inquiries (candidate_name, parent_phone, target_exam) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $phone, $exam);
    $stmt->execute();
    
    echo "<!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Success | ABSS</title>
        <link href='https://fonts.googleapis.com/css2?family=Outfit:wght@600&display=swap' rel='stylesheet'>
        <style>
            body { font-family: 'Outfit', sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; background: #f8faff; color: #0d47a1; }
            .card { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); text-align: center; max-width: 400px; }
            h1 { margin-bottom: 10px; }
            p { opacity: 0.8; margin-bottom: 30px; }
            .btn { background: #0d47a1; color: white; padding: 12px 30px; border-radius: 50px; text-decoration: none; font-weight: bold; }
        </style>
    </head>
    <body>
        <div class='card'>
            <h1>Inquiry Sent!</h1>
            <p>Thank you, $name. We have received your interest in $exam preparation. Our team will contact you at $phone shortly.</p>
            <a href='index.php' class='btn'>Back to Home</a>
        </div>
    </body>
    </html>";
} else {
    header("Location: index.php");
    exit();
}
?>
