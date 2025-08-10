<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://avatars0.githubusercontent.com/u/993323" height="100px">
    </a>
    <h1 align="center">Yii 2 Advanced Project Template</h1>
    <br>
</p>

Yii 2 Advanced Project Template is a skeleton [Yii 2](https://www.yiiframework.com/) application best for
developing complex Web applications with multiple tiers.

The template includes three tiers: front end, back end, and console, each of which
is a separate Yii application.

The template is designed to work in a team development environment. It supports
deploying the application in different environments.

Documentation is at [docs/guide/README.md](docs/guide/README.md).

[![Latest Stable Version](https://img.shields.io/packagist/v/yiisoft/yii2-app-advanced.svg)](https://packagist.org/packages/yiisoft/yii2-app-advanced)
[![Total Downloads](https://img.shields.io/packagist/dt/yiisoft/yii2-app-advanced.svg)](https://packagist.org/packages/yiisoft/yii2-app-advanced)
[![build](https://github.com/yiisoft/yii2-app-advanced/workflows/build/badge.svg)](https://github.com/yiisoft/yii2-app-advanced/actions?query=workflow%3Abuild)

DIRECTORY STRUCTURE
-------------------

```
common
    config/              contains shared configurations
    mail/                contains view files for e-mails
    models/              contains model classes used in both backend and frontend
    tests/               contains tests for common classes    
console
    config/              contains console configurations
    controllers/         contains console controllers (commands)
    migrations/          contains database migrations
    models/              contains console-specific model classes
    runtime/             contains files generated during runtime
backend
    assets/              contains application assets such as JavaScript and CSS
    config/              contains backend configurations
    controllers/         contains Web controller classes
    models/              contains backend-specific model classes
    runtime/             contains files generated during runtime
    tests/               contains tests for backend application    
    views/               contains view files for the Web application
    web/                 contains the entry script and Web resources
frontend
    assets/              contains application assets such as JavaScript and CSS
    config/              contains frontend configurations
    controllers/         contains Web controller classes
    models/              contains frontend-specific model classes
    runtime/             contains files generated during runtime
    tests/               contains tests for frontend application
    views/               contains view files for the Web application
    web/                 contains the entry script and Web resources
    widgets/             contains frontend widgets
vendor/                  contains dependent 3rd-party packages
environments/            contains environment-based overrides
```

# 📰 YII2 Content Parser & Export Tool

Проєкт для парсингу новин з SQL дампів баз даних, з веб-інтерфейсом на Yii2 та можливістю експорту у **CSV**, **TXT** та **XML**.  

---

## 📌 Можливості
- Вибір однієї або кількох БД зі списку (зчитуються `.sql` файли з папки).
- Завантаження та видалення SQL дампів через інтерфейс.
- Вибір таблиці, поля заголовка та поля тексту.
- Перегляд новин у браузері.
- Експорт новин у CSV, TXT або XML (з можливістю об’єднання).
- Очищення контенту від посилань та зображень з збереженням базового форматування.

---

## 1. Необхідні інструменти
- PHP ≥ 8.0 (рекомендовано 8.1) з розширеннями:
  - `pdo_mysql`
  - `mbstring`
  - `intl`
  - `simplexml`
  - `zip`
  - `fileinfo`
- Composer
- MySQL/MariaDB
- Git
- Node.js + npm (якщо потрібна збірка фронтенду)
- Docker (опційно)

---

## 2. Завантаження проєкту

### З GitHub:
```bash
git clone https://github.com/mihalts/YII.git
cd YII
```

### З архіву:
[ Завантажити з Google Drive](https://drive.google.com/file/d/1111ifbqWJ0YqhNtD1vA29mJy4AT6qunp/view?usp=drive_link)  
```bash
unzip YII-main.zip -d ~/projects/YII
cd ~/projects/YII
```

---

## 3. Встановлення залежностей
```bash
composer install
```

---

## 4. Налаштування конфігурацій
Файл: `common/config/main-local.php`
```php
'components' => [
    'db' => [
        'class' => 'yii\db\Connection',
        'dsn' => 'mysql:host=127.0.0.1;dbname=test',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8',
    ],
],
'aliases' => [
    '@storage' => dirname(__DIR__, 2) . '/storage',
],
```

---

## 5. Підготовка директорій
```bash
mkdir -p storage/databases backend/web/exports
chmod -R 777 storage backend/web/exports
```

---

## 6. Налаштування MySQL
Проєкт використовує `.sql` дампи з `storage/databases` та тимчасовий імпорт у MySQL під час перегляду/експорту.  
Перевірте доступність:
```bash
mysql --version
```
---------------------

## 7. Запуск проєкту

### Варіант 1 — без Docker:
```bash
php yii serve --port=8080
```
Відкрити:  
```
http://localhost:8080
```

### Варіант 2 — через Docker:
```bash
docker-compose up -d
```

---

## 8. Інтерфейс парсера

Головна сторінка інтерфейсу парсера доступна за адресою:
```
http://localhost:8080/index.php?r=parser/index
```
Або, якщо у `urlManager` увімкнено `enablePrettyUrl`:
```
http://localhost:8080/parser
```

На цій сторінці можна:
- переглянути список завантажених SQL дампів
- завантажити новий дамп
- видалити дамп
- вибрати БД, таблицю, поле заголовка та поле тексту
- переглянути контент або експортувати його у потрібному форматі

---

## 9. Перевірка роботи
1. Перейти на:
   ```
   http://localhost:8080/index.php?r=parser/index
   ```
2. Завантажити SQL дамп у `storage/databases` або через форму.
3. Вибрати файл, таблицю, поле заголовка та поле тексту.
4. Натиснути **Перегляд** або **Експорт**.
5. Перевірити результати у `backend/web/exports`.

---

## 10. Очищення контенту
Метод:
```php
ParserComponent::sanitize()
```
Видаляє:
- `<a>` (посилання)
- `<img>` (зображення)
- інші небажані теги

---

## 11. Корисні команди Yii2
- Очищення кешу:
```bash
php yii cache/flush-all
```
- Перегляд маршрутів:
```bash
php yii route
```
- Gii генератор:
```
http://localhost:8080/index.php?r=gii
```
