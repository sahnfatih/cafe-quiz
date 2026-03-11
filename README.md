# Cafe Quiz Pro

Laravel 12, Livewire 4, Laravel Reverb ve Tailwind CSS kullanılarak geliştirilmiş,
**kafeler için gerçek zamanlı, uzaktan kumandalı quiz / etkinlik sistemi**.

## Özellikler

- Admin Paneli: Quiz ve soru yönetimi, medya (görsel / YouTube start-end) desteği
- Public Display View: Kafedeki büyük ekrana yansıtılan, tam ekran sunum ekranı
- Remote Controller View: Adminin telefonundan eriştiği kumanda arayüzü
- Participant View: QR / link ile bağlanan katılımcıların oy kullandığı ekran
- Gerçek zamanlı senkronizasyon (Laravel Reverb + Echo)
- Soru bazlı puanlama + cevap hızına göre ek **hız bonusu**
- Finalde büyük ekranda **ilk 3** ve konfeti efekti

## Kurulum

Proje yolu (senin makinen için):

```bash
cd C:\Users\sahnf\Desktop\quiz-app\cafe-quiz-pro
```

1. PHP bağımlılıkları

```bash
composer install
```

2. .env oluşturma ve key

```bash
copy .env.example .env   # Windows PowerShell'de: copy .env.example .env
php artisan key:generate
```

3. Veritabanı ayarları

`.env` içinde `DB_` ayarlarını kendi ortamına göre güncelle (örn. `DB_CONNECTION=sqlite` ve `database/database.sqlite` dosyasını oluşturabilirsin).

```bash
php artisan migrate
```

4. Reverb ve frontend

`.env` içine en azından şu değerleri ekle (geliştirme için basit ayarlar):

```env
REVERB_APP_ID=local
REVERB_APP_KEY=local-key
REVERB_APP_SECRET=local-secret
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY=${REVERB_APP_KEY}
VITE_REVERB_HOST=${REVERB_HOST}
VITE_REVERB_PORT=${REVERB_PORT}
VITE_REVERB_SCHEME=${REVERB_SCHEME}
```

Sonra:

```bash
npm install
npm run dev
```

Diğer bir terminalde:

```bash
php artisan serve
php artisan reverb:start --host=127.0.0.1 --port=8080
```

## Kullanım Akışı

1. Admin paneli
   - Tarayıcıdan `http://127.0.0.1:8000/admin/quizzes` adresine git.
   - Yeni quiz oluştur, ardından quiz detay sayfasından sorular ekle.

2. Sunumu başlatma
   - Quiz detay sayfasında **“Sunumu Başlat”** butonuna bas.
   - Bu işlem yeni bir `PresentationSession` oluşturur ve seni otomatik olarak **Remote Controller** ekranına atar.

3. Ekranlar
   - **Public Display (büyük ekran)**: Kumanda sayfasındaki linkte veya şu formatta açılır:  
     `http://127.0.0.1:8000/display/{OTURUM_KODU}`  
     Bu ekranı F11 ile tam ekrana al.
   - **Participant Join (müşteri katılım)**: `http://127.0.0.1:8000/join/{OTURUM_KODU}`  
     Müşteriler isim girip katılır; onları `play` sayfasına yönlendirir.

4. Kumanda
   - Kumanda ekranında 3 buton vardır:
     - **Önceki Soru**
     - **Sıradaki Soru**
     - **Sonuçları Göster**
   - Bu butonlar `QuizStateUpdated` event’ini Laravel Reverb üzerinden yayınlar.
   - Public Display ve Participant ekranları bu event’i dinler ve kendini gerçek zamanlı günceller.

5. Puanlama ve Hız Bonusu
   - Her soru için `points` alanı ile puan belirlenir.
   - Katılımcı doğru cevap verirse:
     - Temel puan = soru puanı
     - Hız bonusu: sorunun açıldığı andan itibaren geçen milisaniyeye göre yaklaşık `max(0, 50 - responseMs / 200)` formülüyle hesaplanır.
   - Puanlar katılımcının `total_score` ve `total_speed_bonus` alanlarına eklenir.

6. Final
   - Kumandadan **Sonuçları Göster** dendiğinde:
     - Public Display ekranında ilk 3 katılımcı (toplam puan + hız bonusu sırasına göre) listelenir.
     - Basit bir konfeti efekti gösterilir.

<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
