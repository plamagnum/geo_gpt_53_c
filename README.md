# Fullstack веб-додаток для шкільних тестів (5-11 класи)

Готовий приклад навчального fullstack застосунку для тестів зі стеком:
- **nginx**
- **php (php-fpm)**
- **javascript**
- **mysql**
- **phpmyadmin**
- деплой через **Docker Compose**

## Реалізовано

- Спрощена реєстрація та вхід учня.
- Ролі:
  - **Користувач**: CRUD лише своїх задач.
  - **Адмін**: керування всіма задачами, користувачами (через БД/phpMyAdmin), додавання класів/предметів/тем.
- Проходження тесту: 4 варіанти відповіді, після завершення:
  - правильна відповідь підсвічується **зеленим**,
  - неправильна обрана відповідь — **червоним**.
- Ручне додавання питань адміном.
- Імпорт питань через JSON API.
- Дві заготовки парсерів (PHP + Python), у яких для адаптації під сайт треба змінювати переважно HTML селектори/теги.
- Темна/світла тема.
- Адаптивний **mobile-first** UI.

---

## Структура

```text
geo_gpt_53_c/
├── docker-compose.yml
├── Dockerfile
├── nginx/
│   └── default.conf
├── php/
│   ├── db.php
│   └── api/
│       ├── _bootstrap.php
│       ├── register.php
│       ├── login.php
│       ├── logout.php
│       ├── me.php
│       ├── classes.php
│       ├── subjects.php
│       ├── topics.php
│       ├── tests.php
│       ├── submit_test.php
│       ├── tasks.php
│       ├── admin_meta.php
│       ├── admin_questions.php
│       └── import_questions.php
├── public/
│   ├── index.html
│   ├── styles.css
│   └── app.js
├── sql/
│   ├── schema.sql
│   └── seeder.sql
├── parsers/
│   ├── php/parser.php
│   └── python/
│       ├── parser.py
│       └── requirements.txt
└── data/
```

---

## Покроковий запуск

1. Клонуйте репозиторій.
2. У корені запустіть:
   ```bash
   docker compose up -d --build
   ```
3. Відкрийте застосунок:
   - App: http://localhost:8080
   - phpMyAdmin: http://localhost:8081
4. Дані для входу адміністратора за замовчуванням:
   - `admin / admin123`

> Порти можна змінити у `docker-compose.yml`.

---

## Основні API (приклади)

### Реєстрація
`POST /api/register.php`
```json
{
  "name": "Учень1",
  "password": "1234",
  "class_id": 1
}
```

### Вхід
`POST /api/login.php`
```json
{
  "name": "admin",
  "password": "admin123"
}
```

### JSON імпорт питань (адмін)
`POST /api/import_questions.php`
```json
{
  "class": "7",
  "subject": "Історія",
  "topic": "Київська Русь",
  "test_title": "Русь: базовий тест",
  "questions": [
    {
      "text": "Хто хрестив Русь?",
      "options": ["Володимир", "Ярослав", "Олег", "Святослав"],
      "correct_index": 0
    }
  ]
}
```

---

## Парсери

### 1) PHP парсер
```bash
php parsers/php/parser.php <url_або_html_файл> [output_json]
```
- Налаштування селекторів: масив `$config` у `parsers/php/parser.php`.
- Результат: JSON у папці `data/`.

### 2) Python парсер
```bash
pip install -r parsers/python/requirements.txt
python parsers/python/parser.py <url_або_html_файл> [output_json]
```
- Налаштування селекторів: словник `CONFIG` у `parsers/python/parser.py`.
- Результат: JSON у папці `data/`.

Після генерації JSON його можна відправити в `POST /api/import_questions.php`.

---

## Примітки

- У коді додані коментарі українською в ключових місцях.
- Це базова реалізація, яку легко розширити (журнал оцінок, аналітика, складніша авторизація, редактор тестів тощо).
