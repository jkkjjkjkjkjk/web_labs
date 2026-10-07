<?php

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class QueueManager
{
    public const MAIN_QUEUE  = 'lab7_queue';
    public const ERROR_QUEUE = 'lab7_errors';

    private AMQPStreamConnection $connection;
    private $channel;
    private string $queueName;

    public function __construct(string $queueName = self::MAIN_QUEUE)
    {
        $this->queueName = $queueName;
        $this->connection = new AMQPStreamConnection('rabbitmq', 5672, 'guest', 'guest');
        $this->channel = $this->connection->channel();
        $this->channel->queue_declare($this->queueName, false, true, false, false);
    }

    public function publish(array $data): void
    {
        $msg = new AMQPMessage(
            json_encode($data, JSON_UNESCAPED_UNICODE),
            ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]
        );
        $this->channel->basic_publish($msg, '', $this->queueName);
    }

    public function count(): int
    {
        [, $messages] = $this->channel->queue_declare($this->queueName, false, true, false, false);
        return (int)$messages;
    }

    public function consume(callable $callback): void
    {
        $this->channel->basic_qos(null, 1, null);
        $this->channel->basic_consume($this->queueName, '', false, false, false, false, function ($msg) use ($callback) {
            $data = json_decode($msg->getBody(), true);
            $callback(is_array($data) ? $data : ['_raw' => $msg->getBody()]);
            $msg->ack();
        });

        while ($this->channel->is_consuming()) {
            $this->channel->wait();
        }
    }

    public function __destruct()
    {
        try {
            $this->channel->close();
            $this->connection->close();
        } catch (\Throwable $e) {
        }
    }
}