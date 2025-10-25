
<header class="header">
  <div class="header__container">
     
    <a href="../index.php"><img src="../harf.png" alt="Logo" class="header__logo"></a>
    <h1 class="header__title"><a href="../index.php">TekliKoltuk</a></h1>
    <div class="header__right">
    <?php if ($role === 'user'): ?>
                <?php if ($currentPage === 'profil.php'): ?>
                    <a href="../public/travlers.php" class="header__btn">Seferler</a>       
                    <a href="../public/logout.php" class="header__btn">Çıkış Yap</a>
                <?php elseif ($currentPage === 'travlers.php'): ?>
                    <a href="../public/profil.php" class="header__btn">Profilim</a> 
                    <a href="../public/logout.php" class="header__btn">Çıkış Yap</a>         
                <?php elseif ($currentPage === 'buy.php'): ?>
                    <a href="../public/travlers.php" class="header__btn">Seferler</a> 
                    <a href="../public/logout.php" class="header__btn">Çıkış Yap</a>                     
                    <?php endif?>
    <?php elseif ($role === 'company'): ?>
                <?php if ($currentPage === 'dashboard.php'): ?>
                    <a href="../public/travlers.php" class="header__btn">Seferler</a>        
                    <a href="../public/logout.php" class="header__btn">Çıkış Yap</a>
                <?php elseif ($currentPage === 'travlers.php'): ?>
                    <a href="../company/dashboard.php" class="header__btn">Yönetim Paneli</a>
                    <a href="../public/logout.php" class="header__btn">Çıkış Yap</a>                       
                    <?php endif?>            
    <?php elseif ($role === 'admin'): ?>
                <?php if ($currentPage === 'dashboard.php'): ?>                           
                    <a href="../public/logout.php" class="header__btn">Çıkış Yap</a>
                    <?php endif?>
        <?php else: ?>
                <?php if ($currentPage === 'register.php'): ?>
                    <a href="../index.php" class="header__btn">Ana Sayfa</a>
                    <a href="../public/login.php" class="header__btn">Giriş Yap</a>        
                    <a href="../public/travlers.php" class="header__btn">Seferler</a>
                <?php elseif ($currentPage === 'login.php'): ?>
                    <a href="../index.php" class="header__btn">Ana Sayfa</a>
                    <a href="../public/register.php" class="header__btn">Kayıt Ol</a>        
                    <a href="../public/travlers.php" class="header__btn">Seferler</a>
                <?php elseif ($currentPage === 'travlers.php'): ?>
                    <a href="../index.php" class="header__btn">Ana Sayfa</a>
                    <a href="../public/login.php" class="header__btn">Giriş Yap</a>  
                    <a href="../public/register.php" class="header__btn">Kayıt Ol</a>                        
                    <?php endif?>
    <?php endif ?>
   
    </div>
    </div>
</header> 