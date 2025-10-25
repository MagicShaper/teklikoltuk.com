# teklikoltuk.com
# 🚌 Bilet Satın Alma Platformu

Bu proje modern web teknolojileri kullanılarak geliştirilmiş, dinamik ve çok kullanıcılı bir **otobüs bileti satış ve yönetim platformudur**.  
Proje mimarisi **Docker** kullanılarak konteyner tabanlı olarak oluşturulmuş ve teslim edilmiştir.

---

## 🎯 Proje Amacı

Bu projenin temel amacı, **çok kullanıcılı otobüs bileti satış platformunu veri tabanı destekli bir yapı** ile geliştirmektir.  
Kullanıcıların, şirketlerin ve yöneticilerin farklı yetkilerle sisteme erişebildiği, güvenli ve esnek bir mimari hedeflenmiştir.

---

## 🧩 Kullanılan Teknolojiler

| Katman | Teknoloji |
|--------|------------|
| Veritabanı | **SQLite** |
| Sunucu Tarafı | **PHP** |
| Arayüz | **HTML & CSS** |
| Konteyner Yönetimi | **Docker & Docker Compose** |

---

## 👥 Kullanıcı Rolleri ve Yetkilendirme

Projede temelde **4 farklı rol** bulunmaktadır: `Admin`, `Company`, `User` ve `Guest`.  
Her rolün sistem içerisindeki yetkileri aşağıdaki tabloda özetlenmiştir.

| Rol | Açıklama | Yetkiler |
|-----|-----------|-----------|
| **Admin** | Sistem yöneticisi | Kullanıcı yönetimi, şirket ekleme, şirkete yetkili atama, kupon oluşturma, sefer görüntüleme, kullanıcı bakiyelerini düzenleme |
| **Company** | Firma yetkilisi | Kendi firmasına özel sefer oluşturma, güncelleme, silme, kupon kodu oluşturma |
| **User** | Kayıtlı kullanıcı | Seferleri görüntüleme, bilet satın alma, sefer saatine 1 saat kalaya kadar iptal etme |
| **Guest** | Giriş yapmamış kullanıcı | Sadece seferleri görüntüleme (satın alma işlemleri için giriş gerekir) |

---

## ⚙️ Kurulum ve Çalıştırma

Projeyi yerel ortamınızda çalıştırmak için aşağıdaki adımları izleyin:

### 🧱 Gereksinimler
- Docker Desktop kurulu olmalıdır  
- Windows kullanıcıları için **WSL 2** etkin olmalıdır

### 🔧 Kurulum Adımları
```bash
# 1. Projeyi klonlayın
git clone https://github.com/magicshaper/teklikoltuk.com

# 2. Proje dizinine gidin
cd teklikoltuk.com

# 3. Servisleri başlatın
docker compose up -d

# 4. Tarayıcıdan erişim sağlayın
http://localhost:8080

# 5. Çalışmayı sonlandırmak için
docker compose down
```

---

## 🧪 Test Kullanıcıları ve Bilgileri

Aşağıda proje geliştirme ve test amaçlı oluşturulmuş **firma yöneticileri (company admin)** listelenmiştir.  
**Not:** Her kullanıcının test parolası, e‑posta adresindeki `@` karakterinden önceki (local-part) kısımla **aynıdır**.  
Örnek: `ayse.demir@turkuaz.com` için şifre `ayse.demir` olacaktır.

| İsim | E‑posta | Firma | Şifre (test) |
|------|--------|-------|--------------|
| Ayşe Demir | ayse.demir@turkuaz.com | Turkuaz Ekspres | `ayse.demir` |
| Mehmet Yıldız | mehmet.yildiz@jetbus.com | Anadolu JetBus | `mehmet.yildiz` |
| Elif Kaya | elif.kaya@yildizlar.com | Yıldızlar Turizm | `elif.kaya` |
| Can Aydın | can.aydin@mavihat.com | Mavi Hat Seyahat | `can.aydin` |
| Zeynep Koç | zeynep.koc@gokyuzu.com | Gökyüzü Yolculuk | `zeynep.koc` |
| Emre Şahin | emre.sahin@serhatlar.com | Serhatlar Otobüsleri | `emre.sahin` |
| Derya Aksoy | derya.aksoy@hizliadim.com | HızlıAdım Ulaşım | `derya.aksoy` |
| Burak Erdem | burak.erdem@efsane.com | Efsane Yol | `burak.erdem` |
| Selin Arslan | selin.arslan@kuzeyrota.com | Kuzey Rota | `selin.arslan` |
| Ahmet Çelik | ahmet.celik@altinkoltuk.com | AltınKoltuk Turizm | `ahmet.celik` |

> ⚠️ Bu hesaplar yalnızca **test amaçlı**dır. Gerçek dağıtımda bu tip kolay parolalar kullanılmamalı ve test hesapları silinmelidir.

---

### 👤 Kullanıcı (User) Rolüne Ait Test Hesapları

Aşağıdaki hesaplar **normal kullanıcı (User)** rolüne sahip test hesaplarıdır.  
Her birinin şifresi, e-posta adresindeki `@` karakterinden önceki kısmıdır.  
Örneğin: `elif.yildiz@mail.com` için şifre `elif.yildiz`.

| İsim | E-posta | Rol | Şifre (test) |
|------|----------|------|--------------|
| Elif Yıldız | elif.yildiz@mail.com | User | `elif.yildiz` |
| Zeynep Karaca | zeynep.karaca@mail.com | User | `zeynep.karaca` |
| Ayşe Koç | ayse.koc@mail.com | User | `ayse.koc` |
| Burak Demirtaş | burak.demirtas@mail.com | User | `burak.demirtas` |
| Caner Aydın | caner.aydin@mail.com | User | `caner.aydin` |
| Mehmet Özdemir | mehmet.ozdemir@mail.com | User | `mehmet.ozdemir` |

---

## 🧮 Rol Yetki Tablosu

| Rol | Sefer Görüntüleme | Bilet Satın Alma | Bilet İptali | Kupon Oluşturma | Sefer Yönetimi | Kullanıcı Yönetimi |
|------|------------------|------------------|----------------|------------------|------------------|------------------|
| Admin | ✅ | ❌ | ❌ | ✅ | ✅ | ✅ |
| Company | ✅ | ❌ | ❌ | ✅ | ✅ | ❌ |
| User | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| Guest | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |

---

## 🧑‍💻 Geliştirici

**Geliştirici:** [MagicShaper (Ömer Faruk Kömür)](https://github.com/magicshaper)

---


**Not:** Bu README test hesapları ve temel kullanım için düzenlenmiştir. 
