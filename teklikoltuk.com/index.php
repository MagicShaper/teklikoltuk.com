<?php
session_start();
$isLoggedIn = isset($_SESSION['User']); // Kullanıcı giriş yapmış mı
$role = $isLoggedIn ? $_SESSION['User']['role'] : null;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="description" content="Otobüs bileti">
    <meta name="keywords" content="otobüs,rahat,bilet,tatil">
    <meta name="author" content="MagicShaper">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="stylesheet" href="styles.css">
<title>TekliKoltuk</title>
</head>
<body class="index">
<section class="hero" aria-label="Ana tanıtım">
  <div class="hero__overlay"></div>
  <div class="hero__container">
    <div class="hero__content">
      <h1 class="hero__title">TekliKoltuk</h1>
      <p class="hero__subtitle">Hayal ettiğin yolculuk bir adım ileride!</p>
      <div class="hero__actions">
        <?php if ($role === 'user'): ?>
        <a href="../public/profil.php" class="btn btn--secondary">Profilim</a>
        <a href="../public/travlers.php" class="btn btn--secondary">Seferler</a>
        <a href="../public/logout.php" class="btn btn--secondary">Çıkış Yap</a>
        <?php elseif ($role === 'company'): ?>
        <a href="../company/dashboard.php" class="btn btn--secondary">Yönetim Paneli</a>
        <a href="../public/travlers.php" class="btn btn--secondary">Seferler</a>
        <a href="../public/logout.php" class="btn btn--secondary">Çıkış Yap</a>
        <?php elseif ($role === 'admin'): ?>
        <a href="../admin/dashboard.php" class="btn btn--secondary">Yönetim Paneli</a>
        <a href="../public/logout.php" class="btn btn--secondary">Çıkış Yap</a>
        <?php else: ?>
        <a href="../public/login.php" class="btn btn--primary">Giriş Yap</a>
        <a href="../public/register.php" class="btn btn--ghost">Kayıt Ol</a>
        <a href="../public/travlers.php" class="btn btn--secondary">Seferler</a>
        <?php endif ?>
      </div>
    </div>
   
    <!-- Sayfa içi Logo Kısmı -->
    <img class="hero__illustration" src="harf.png" aria-hidden="true">
    
    
  </div>
</section>
<?php include("includes/footer.php");?>
</body>
</html>