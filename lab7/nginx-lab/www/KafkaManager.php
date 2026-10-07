<?php

class KafkaManager
{
    public const MAIN_TOPIC  = 'lab7_topic';
    public const ERROR_TOPIC = 'lab7_errors_topic';
    private const BROKERS = 'kafka:9092';

    private string $topic;

    public function __construct(string $topic = self::MAIN_TOPIC)
    {
        $this->topic = $topic;
    }

    public function publish(array $data): void
    {
        $conf = new \RdKafka\Conf();
        $conf->set('metadata.broker.list', self::BROKERS);

        $producer = new \RdKafka\Producer($conf);
        $topic = $producer->newTopic($this->topic);
        $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode($data, JSON_UNESCAPED_UNICODE));
        $producer->poll(0);

        if ($producer->flush(5000) !== RD_KAFKA_RESP_ERR_NO_ERROR) {
            throw new \RuntimeException('Kafka: не удалось отправить сообщение');
        }
    }

    public function count(): int
    {
        $conf = new \RdKafka\Conf();
        $conf->set('metadata.broker.list', self::BROKERS);
        $producer = new \RdKafka\Producer($conf);

        $low = 0;
        $high = 0;
        try {
            $producer->queryWatermarkOffsets($this->topic, 0, $low, $high, 3000);
        } catch (\RdKafka\Exception $e) {
            return 0;
        }
        return max(0, $high - $low);
    }

    public function consume(callable $callback): void
    {
        $conf = new \RdKafka\Conf();
        $conf->set('metadata.broker.list', self::BROKERS);
        $conf->set('group.id', 'lab7_group');
        $conf->set('auto.offset.reset', 'earliest');

        $consumer = new \RdKafka\KafkaConsumer($conf);
        $consumer->subscribe([$this->topic]);

        while (true) {
            $message = $consumer->consume(10000);

            switch ($message->err) {
                case RD_KAFKA_RESP_ERR_NO_ERROR:
                    $data = json_decode($message->payload, true);
                    $callback(is_array($data) ? $data : ['_raw' => $message->payload]);
                    break;
                case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                case RD_KAFKA_RESP_ERR__TIMED_OUT:
                    break;
                default:
                    echo "Kafka: " . $message->errstr() . "\n";
                    sleep(2);
            }
        }
    }
}