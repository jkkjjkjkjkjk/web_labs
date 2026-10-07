# Лабораторная работа №9: CI/CD для PHP-приложения с использованием GitHub Actions и Docker

## 👩‍💻 Автор
ФИО: Чалов Егор Александрович  
Группа: ПМИ3

![CI](https://github.com/jkkjjkjkjkjk/web_labs/actions/workflows/ci.yml/badge.svg)

---

## 📌 Описание задания
Настроить pipeline непрерывной интеграции в GitHub Actions: автоматически собирать Docker-образы, запускать контейнеры в CI, выполнять тесты PHPUnit и выявлять ошибки через статус pipeline.

**Вариант 18: «Заявка на ремонт техники».** В pipeline запускаются тесты из лабораторной работы №8.

---

## ⚙️ Как запустить проект

### Локально
1. Клонировать репозиторий:
   ```bash
   git clone https://github.com/jkkjjkjkjkjk/web_labs
   cd web_labs/lab9/nginx-lab
   ```
2. Создать файл `.env.test` (см. лабораторную работу №8):
   ```
   DB_HOST=db
   DB_NAME=test_db
   DB_USER=test_user
   DB_PASSWORD=test_pass
   APP_URL=http://web
   ```
3. Установить зависимости, запустить контейнеры и тесты:
   ```bash
   docker compose build
   docker compose run --rm --no-deps tests composer install
   docker compose up -d
   docker compose run --rm tests
   ```

### В GitHub Actions
Pipeline запускается автоматически при `push` в ветку `main` (если изменились файлы в `lab9/` или сам workflow), при pull request и вручную (кнопка **Run workflow** на вкладке **Actions**). Результат смотреть на вкладке **Actions** репозитория.

---

## 📂 Содержимое проекта

- `.github/workflows/ci.yml` (в **корне репозитория** `web_labs`): описание pipeline
- `lab9/nginx-lab/`: проект с тестами из лабораторной работы №8
  - `docker-compose.yml`, `Dockerfile`, `composer.json`, `phpunit.xml`
  - `www/`: код приложения
  - `tests/`: тесты PHPUnit
  - `db-init/`: скрипт создания тестовой базы
- `screenshots/`: скриншоты работы

---

## 🧩 Реализация pipeline

Pipeline состоит из двух job'ов: `build` и `test`. Второй запускается только после успешного завершения первого (`needs: build`).

### Job `build`
1. Клонирование репозитория (`actions/checkout`).
2. Проверка синтаксиса всех PHP-файлов (`php -l`).
3. Проверка корректности `docker-compose.yml` (`docker compose config`).
4. Сборка Docker-образов.

### Job `test`
1. Клонирование репозитория.
2. Создание `.env.test` из переменных окружения workflow.
3. Сборка образов.
4. Установка зависимостей Composer в контейнере.
5. Запуск контейнеров `web`, `php`, `db`.
6. Ожидание готовности MySQL (цикл проверки подключения тестовым пользователем, не более двух минут).
7. Вывод списка контейнеров (`docker ps`).
8. Запуск тестов `vendor/bin/phpunit --testdox --fail-on-skipped`. Флаг гарантирует, что пропущенные тесты (например, из-за недоступной БД) не дают ложный успех.
9. При ошибке вывод логов контейнеров (`docker compose logs`, `if: failure()`).
10. Остановка контейнеров и удаление томов (`if: always()`).

### ⭐ Штрафное задание
1. **Логирование ошибок:** шаг `Show logs on error` с условием `if: failure()`.
2. **Ожидание сервисов:** шаг `Wait for services` (проверка готовности БД вместо фиксированной паузы).
3. **Проверка контейнеров:** шаг `Check containers` с командой `docker ps`.
4. **Переменные окружения:** `.env.test` создаётся в CI из блока `env` workflow и используется тестами.
5. **Второй job:** pipeline разделён на `build` и `test`.

---

## 🔴 Проверка падения pipeline

Для демонстрации в `tests/ExampleTest.php` намеренно было заменено утверждение на неверное:
```php
$this->assertEquals(2, 1 + 2);
```
После `git push` pipeline завершился с ошибкой (красный статус ❌) на шаге `Run tests`, а в шаге `Show logs on error` были выведены логи контейнеров. После исправления теста pipeline снова стал зелёным ✅.

---

## ✅ Результат
Настроен pipeline GitHub Actions, который собирает Docker-образы, поднимает контейнеры и автоматически запускает тесты PHPUnit. Падение теста приводит к красному статусу pipeline. Штрафное задание выполнено.
