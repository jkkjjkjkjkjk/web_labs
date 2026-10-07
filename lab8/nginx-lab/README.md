# Лабораторная работа №8: Тестирование PHP-приложения с использованием PHPUnit и Guzzle

## 👩‍💻 Автор
ФИО: Чалов Егор Александрович  
Группа: ПМИ3

---

## 📌 Описание задания
Научиться устанавливать и использовать PHPUnit, писать unit-тесты, применять mock-объекты, тестировать HTTP-запросы через Guzzle, работать с переменными окружения (`.env.test`) и изолировать тестовую среду.

**Вариант 18: «Заявка на ремонт техники».** Тестируются классы `RequestValidator` и `RepairRequest` (добавление и получение данных) и HTTP-страницы приложения.

---

## ⚙️ Как запустить проект

1. Клонировать репозиторий:
   ```bash
   git clone https://github.com/jkkjjkjkjkjk/web_labs
   cd web_labs/lab8/nginx-lab
   ```
2. Создать файл `.env.test` в корне проекта (он не хранится в репозитории):
   ```
   DB_HOST=db
   DB_NAME=test_db
   DB_USER=test_user
   DB_PASSWORD=test_pass
   APP_URL=http://web
   ```
3. Собрать образы и установить зависимости:
   ```bash
   docker compose build
   docker compose run --rm --no-deps tests composer install
   ```
4. Запустить приложение и базу данных, подождать около минуты (при первом запуске MySQL создаёт тестовую БД):
   ```bash
   docker compose up -d
   ```
5. Запустить тесты:
   ```bash
   docker compose run --rm tests vendor/bin/phpunit --version
   docker compose run --rm tests
   docker compose run --rm tests vendor/bin/phpunit --testdox
   ```

Adminer для просмотра баз данных: [http://localhost:8081](http://localhost:8081) (сервер `db`, пользователь `test_user`, пароль `test_pass`, база `test_db`).

---

## 📂 Содержимое проекта

```
nginx-lab/
├── docker-compose.yml
├── Dockerfile
├── composer.json
├── phpunit.xml
├── .env.test            (не хранится в репозитории)
├── db-init/
│   └── init-test-db.sql
├── nginx/default.conf
├── www/
│   ├── db.php
│   ├── RepairRequest.php
│   ├── RequestValidator.php
│   └── ...
└── tests/
    ├── bootstrap.php
    ├── ExampleTest.php
    ├── RequestValidatorTest.php
    ├── RepairRequestMockTest.php
    ├── ApiTest.php
    └── RepairRequestIntegrationTest.php
```

- Для запуска тестов используется отдельный сервис `tests` в `docker-compose.yml` (профиль `test`).
- `tests/bootstrap.php` подключает автозагрузку и загружает `.env.test` в `$_ENV` через `vlucas/phpdotenv`.
- `.env.test`, `vendor/` и `.phpunit.cache/` добавлены в `.gitignore`.

---

## 🧩 Реализация

| Файл | Что проверяет |
| --- | --- |
| `ExampleTest.php` | Первый тест из методички (`assertTrue`) |
| `RequestValidatorTest.php` | **Unit-тесты** без БД: корректные данные проходят проверку, пустое имя вызывает исключение, `DataProvider` проверяет пустую модель, неизвестную услугу и неизвестный срок |
| `RepairRequestMockTest.php` | **Тесты с mock** объектов `PDO` и `PDOStatement` (метод `setUp`): добавление данных (проверка SQL `INSERT` и параметров), получение данных, фильтр «только с гарантией» |
| `ApiTest.php` | **HTTP-тесты через Guzzle:** реальные запросы к работающему Nginx (`/index.php`, `/form.html`) и запросы без сети через `MockHandler` (ответ 200 и ошибка 404) |

### ⭐ Штрафное задание
- **Отдельная тестовая БД.** Контейнер MySQL при первом запуске выполняет `db-init/init-test-db.sql`, который создаёт базу `test_db` и пользователя `test_user`. Рабочая база приложения не затрагивается.
- **Integration-тесты с реальной БД** (`RepairRequestIntegrationTest.php`): добавление и получение записей, фильтр по гарантии, обновление и удаление. Перед каждым тестом таблица очищается (`TRUNCATE`), а тесты проверяют, что используется именно база `test_db`.
- **`.env.test`.** Параметры подключения к тестовой БД и адрес приложения берутся из переменных окружения.
- **Тест на ошибку:** запись с пустым обязательным полем (`NULL` вместо имени) отклоняется базой данных с исключением `PDOException`, и в таблицу ничего не попадает.
- **Проверка количества записей:** после добавления трёх записей проверяется `COUNT(*)`, а также статистика «всего» и «с гарантией».

---

## ✅ Результат
Написаны unit-тесты, тесты с mock-объектами, HTTP-тесты через Guzzle и integration-тесты с отдельной тестовой базой. Все тесты проходят. Штрафное задание выполнено.
