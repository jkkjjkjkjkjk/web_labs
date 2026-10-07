# Лабораторная работа №7: Асинхронная обработка данных через очереди сообщений (RabbitMQ / Kafka)

## 👩‍💻 Автор
ФИО: Чалов Егор Александрович  
Группа: ПМИ3

---

## 📌 Описание задания
Научиться работать с очередями сообщений и реализовывать асинхронную обработку данных в PHP: создавать producer (отправитель) и consumer (обработчик, worker).

**Вариант 18 (чётный): RabbitMQ.** Заявка на ремонт техники отправляется в очередь, а worker обрабатывает её отдельно от веб-запроса и сохраняет в MySQL.

Результат доступен по адресу [http://localhost:8080](http://localhost:8080).

---

## ⚙️ Как запустить проект

1. Клонировать репозиторий:
   ```bash
   git clone https://github.com/jkkjjkjkjkjk/web_labs
   cd web_labs/lab7/nginx-lab
   ```
2. Собрать образ и установить зависимости Composer:
   ```bash
   docker compose build
   docker compose run --rm --no-deps php composer install
   ```
3. Запустить контейнеры:
   ```bash
   docker compose up -d
   docker compose ps
   ```
4. Подождать около минуты (запуск Kafka и MySQL) и открыть:
   - сайт: [http://localhost:8080](http://localhost:8080)
   - форма: [http://localhost:8080/form.html](http://localhost:8080/form.html)
   - панель RabbitMQ: [http://localhost:15672](http://localhost:15672) (логин и пароль: `guest` / `guest`)

Логи обработчиков:
```bash
docker compose logs -f worker
docker compose logs -f worker_kafka
```

---

## 📂 Содержимое проекта

- `Dockerfile`: PHP-FPM с расширениями `pdo_mysql`, `sockets`, `rdkafka` и Composer
- `docker-compose.yml`: сервисы `web`, `php`, `worker`, `worker_kafka`, `rabbitmq`, `kafka`, `db`
- `nginx/default.conf`: конфигурация Nginx
- `www/composer.json`: зависимость `php-amqplib/php-amqplib`
- `www/form.html`: форма заявки (отправляет данные в `send.php`)
- `www/send.php`: producer, отправляет сообщение в очередь
- `www/worker.php`: consumer RabbitMQ
- `www/worker_kafka.php`: consumer Kafka
- `www/QueueManager.php`: класс для работы с RabbitMQ
- `www/KafkaManager.php`: класс для работы с Kafka
- `www/RequestValidator.php`: проверка сообщений
- `www/db.php`, `www/RepairRequest.php`: подключение к MySQL и работа с таблицей заявок
- `www/index.php`: статистика очередей и таблица обработанных заявок
- `screenshots/`: скриншоты работы

---

## 🧩 Реализация

1. Пользователь отправляет форму, `send.php` кладёт сообщение в очередь `lab7_queue` и сразу возвращает пользователя на главную страницу. Долгая обработка не блокирует веб-запрос.
2. `worker.php` забирает сообщение, проверяет его, имитирует длительную обработку (`sleep(2)`), сохраняет заявку в MySQL и записывает строку в лог `processed_rabbit.log`.
3. Сообщения помечаются как доставленные (`ack`) только после успешной обработки, поэтому при сбое worker'а они не теряются. Очереди и сообщения устойчивые (`durable`, `delivery_mode = 2`).
4. Главная страница обновляется каждые 5 секунд и показывает, как заявки появляются в таблице после обработки worker'ом.

### ⭐ Штрафное задание
- **Обе системы в одном `docker-compose.yml`.** Кроме RabbitMQ поднимается Kafka (официальный образ `apache/kafka`, режим KRaft без Zookeeper). Расширение `rdkafka` используется для работы с ней из PHP.
- **Две очереди и два топика:**
  - RabbitMQ: `lab7_queue` (основная) и `lab7_errors` (ошибки);
  - Kafka: `lab7_topic` (основной) и `lab7_errors_topic` (ошибки).
- **Обработка ошибок в worker.** Если сообщение не прошло проверку или не удалось сохранить его в базу, worker отправляет его вместе с текстом ошибки во вторую очередь (топик) и продолжает работу. Это реализовано в обоих worker'ах.
- **Статистика на `index.php`.** Для всех четырёх очередей и топиков выводится число сообщений: в RabbitMQ это сообщения, ожидающие обработки, а в Kafka общее количество записей в топике (Kafka не удаляет прочитанные сообщения).
- Кнопка «Отправить тестовую ошибку» на главной странице отправляет заведомо некорректное сообщение, чтобы показать работу очереди ошибок.

---

## ✅ Результат
Заявки отправляются в RabbitMQ и обрабатываются worker'ом асинхронно, результат сохраняется в MySQL. Штрафное задание (Kafka, вторая очередь и топик для ошибок, обработка ошибок, статистика на странице) выполнено.
