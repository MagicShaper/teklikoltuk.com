<?php
session_start();
$isLoggedIn = isset($_SESSION['User']);
$role = $isLoggedIn ? $_SESSION['User']['role'] : null;
$companyId= $isLoggedIn ? $_SESSION['User']['company_id'] : null;
$currentPage = basename($_SERVER['PHP_SELF']);
require(__DIR__ . '/../db/connect.php');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $db->prepare("SELECT * FROM User WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) 
    {
        $_SESSION['User'] = [
            'id' => $user['id'],
            'full_name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'company_id' => $user['company_id'] ?? null
        ];
        if ($user['role'] === 'admin') 
        {
            header("Location: ../admin/dashboard.php");#isimlendirmeleri kontrol et
        } 
        elseif ($user['role'] === 'company') 
        {
            header("Location: ../company/dashboard.php");
        } 
        else 
        {
            header("Location: ../public/travlers.php");
        }
        exit;

    } 
    else 
    {
        $error = "E-posta veya şifre hatalı.";
    }
}
?>