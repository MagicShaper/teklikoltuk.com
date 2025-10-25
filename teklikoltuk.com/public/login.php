<?php include("../includes/login.php")?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="description" content="Otobüs bileti">
    <meta name="keywords" content="otobüs,rahat,bilet,tatil">
    <meta name="author" content="MagicShaper">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="/images/favicon.ico">
    <link rel="stylesheet" href="../styles2.css">
<title>TekliKoltuk</title>
</head>
<body>
<?php include("../includes/header.php")?>  
<section class="login-section">
  <div class="login-card">
    <h1>Giriş Yap Tatili Kaçırma!</h1>
    <p>Hayal ettiğin yolculuk bir adım ileride!</p>
    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
      <input type="text" name="email" placeholder="E-mail adresiniz" required>
      <input type="password" name="password" placeholder="Şifreniz" required>
      <input type="submit" value="Giriş Yap">
    </form>
  </div>
</section>
<?php include("../includes/footer.php");?>
</body>
</html>

