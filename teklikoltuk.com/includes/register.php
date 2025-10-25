<?php
    session_start();
    $currentPage = basename($_SERVER['PHP_SELF']);
    $isLoggedIn = isset($_SESSION['User']); // Kullanıcı giriş yapmış mı
    $role = $isLoggedIn ? $_SESSION['User']['role'] : null;
    require(__DIR__ . '/../db/connect.php');
    if($_SERVER['REQUEST_METHOD']==='POST')
    {
        $name =trim($_POST['name']);
        $email=trim($_POST['email']);
        $password = $_POST['password'];
        

        

        if($name && $email && $password)
        {
            $stmt = $db->prepare("SELECT * FROM User WHERE email = ? ");
            $stmt -> execute([$email]);

            if($stmt->fetch())
            {
                $error = "Bu e-posta zaten kayıtlıdır.";
                
            }
            else
            {
               
                    $hashed = password_hash($password,PASSWORD_DEFAULT);
                    $stmt = $db->prepare("INSERT INTO User (name, email, password) VALUES (?,?,?)");    
                    $stmt -> execute([$name,$email,$hashed]);
                    $_SESSION['success'] = "Kayıt başarılı , giriş yapabilirsiniz.";
                    header("Location: login.php");
                    exit;
             
                    
            }
        }
        else 
        {
            $error= "Lütfen zorunlu(*) alanları doldurun.";
        }

	}
?>