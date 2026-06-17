# استخدام نسخة رسمية ومستقرة من PHP مدمج معها مخدم Apache
FROM php:8.2-apache

# تفعيل مود الـ Rewrite الخاص بـ Apache إذا كنت تستخدم روابط مخصصة
RUN a2enmod rewrite

# تثبيت إضافات قاعدة البيانات MySQL الأكثر استخداماً في PHP
RUN docker-php-ext-install pdo pdo_mysql mysqli

# نسخ كافة ملفات مشروعك الحالي إلى مسار مخدم الويب الافتراضي داخل السيرفر
COPY . /var/www/html/

# إعطاء الصلاحيات المناسبة للمجلد لقراءة الملفات بأمان
RUN chown -R www-data:www-data /var/www/html/

# فتح المنفذ الافتراضي الذي تطلبه منصة Render
EXPOSE 80
