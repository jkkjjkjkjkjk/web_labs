# Лабораторная работа №6: Нереляционные базы данных (ClickHouse, Redis) и работа через API

## 👩‍💻 Автор
ФИО: Чалов Егор Александрович  
Группа: ПМИ3

---

## 📌 Описание задания
Познакомиться с нереляционными СУБД и научиться работать с ними из PHP: с ClickHouse через HTTP API с помощью Guzzle и с Redis через библиотеку Predis.

**Вариант 18: «Аналитика», база ClickHouse.** Приложение хранит события аналитики (просмотры, клики, покупки), позволяет добавлять их через форму и показывает статистику.

Результат доступен по адресу [http://localhost:8080](http://localhost:8080).

---

## ⚙️ Как запустить проект

1. Клонировать репозиторий:
   ```bash
   git clone https://github.com/jkkjjkjkjkjk/web_labs
   cd web_labs/lab6/nginx-lab
   ```
2. Собрать образ и установить зависимости Composer внутри контейнера:
   ```bash
   docker compose build
   docker compose run --rm php composer install
   ```
3. Запустить контейнеры:
   ```bash
   docker compose up -d
   ```
4. Открыть [http://localhost:8080](http://localhost:8080). При первом запуске ClickHouse стартует несколько секунд, поэтому страницу можно обновить.

Полезные команды для проверки:
```bash
docker compose exec clickhouse clickhouse-client --user lab6_user --password lab6_pass -q "SELECT * FROM lab6.analytics_events"
docker compose exec redis redis-cli TTL analytics:stats
```

---

## 📂 Содержимое проекта

```
nginx-lab/
├── Dockerfile
├── docker-compose.yml
├── nginx/default.conf
└── www/
    ├── index.php
    ├── composer.json
    └── src/
        ├── Helpers/ClientFactory.php
        ├── ClickhouseExample.php
        └── RedisExample.php
```

- `docker-compose.yml`: сервисы `web` (Nginx), `php`, `redis`, `clickhouse`
- `www/composer.json`: зависимости `guzzlehttp/guzzle` и `predis/predis`, автозагрузка `App\` из `src/`
- `www/src/Helpers/ClientFactory.php`: фабрика клиентов Guzzle
- `www/src/ClickhouseExample.php`: запросы к ClickHouse по HTTP (`query`, `select`, `insert`)
- `www/src/RedisExample.php`: работа с Redis через Predis
- `www/index.php`: страница с формой, таблицами и статистикой

Elasticsearch в этой работе не используется, так как для варианта 18 предусмотрена база ClickHouse.

---

## 🧩 Реализация

- **ClickHouse.** Таблица `lab6.analytics_events` (движок `MergeTree`) хранит время события, пользователя, страницу, тип события и длительность. Таблица создаётся автоматически. Данные добавляются запросом `INSERT ... FORMAT JSONEachRow` (значения передаются в теле запроса, а не подставляются в SQL). Статистика считается агрегирующими запросами: `count()`, `avg()`, `GROUP BY`.
- **Доступ.** ClickHouse настроен с пользователем `lab6_user`, запросы идут с базовой авторизацией. Из контейнера PHP база доступна по имени сервиса `clickhouse`.
- **Redis.** Подключение `tcp://redis:6379`. На странице выводится демонстрационная пара ключ-значение (`framework = predis`).
- **Проверка данных.** Имя не должно быть пустым, страница и тип события выбираются из списка, длительность должна быть числом от 0 до 86400.

### ⭐ Штрафное задание
Кеширование статистики в **Redis** на 60 секунд (команда `SETEX`). При открытии страницы данные сначала берутся из Redis, а если кеша нет, считаются в ClickHouse и записываются в кеш. При добавлении нового события кеш сбрасывается. На странице отображается источник данных: «ClickHouse (свежий запрос)» или «Redis (кеш на 60 секунд)». Если Redis недоступен, статистика считается напрямую в ClickHouse.

---

## ✅ Результат
ClickHouse и Redis работают в Docker, PHP обращается к ним через Guzzle и Predis, события аналитики добавляются и агрегируются. Штрафное задание (кеширование статистики в Redis) выполнено.
