<?php include("../includes/register.php")?>
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

<title >TekliKoltuk</title>


</head>
<body>
<?php include("../includes/header.php")?>

<section class="login-section">
  <div class="login-card">    
<div class="responsive-form">
<h1 class="baslık">Kayıt Ol Fırsatları Yakala! </h1>
 <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">

   
   <input type="text" id="name" name="name" placeholder="Ad Soyad">
   <label for=""></label>
   <input type="text" id="email" name="email" placeholder="E-postanızı giriniz">
   <label for=""></label>
   <input type="text" id="password" name="password" placeholder="Şifre Belirleyiniz">

   

    <input type="submit" value="Kayıt Ol">
 </form>
</div>

</div>
</section>
<?php include("../includes/footer.php");?>
</body>
</html>


