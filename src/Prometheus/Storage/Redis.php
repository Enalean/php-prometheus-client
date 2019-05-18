<?php

declare(strict_types=1);

namespace Prometheus\Storage;

use InvalidArgumentException;
use Prometheus\Counter;
use Prometheus\Exception\StorageException;
use Prometheus\Gauge;
use Prometheus\Histogram;
use Prometheus\MetricFamilySamples;
use RedisException;
use function array_keys;
use function array_map;
use function array_merge;
use function array_unique;
use function implode;
use function json_decode;
use function json_encode;
use function sort;
use function strcmp;
use function usort;

class Redis implements Adapter
{
    public const PROMETHEUS_METRIC_KEYS_SUFFIX = '_METRIC_KEYS';

    /**
     * @var array<string, (string|int|float|false|null)>
     * @psalm-var array{host?:string,port?:int,timeout?:float,read_timeout?:string,persistent_connections?:bool,password?:string|null}
     */
    private static $defaultOptions = [];
    /** @var string */
    private static $prefix = 'PROMETHEUS_';

    /**
     * @var array<string, (string|int|float|false|null)>
     * @psalm-var array{host:string,port:int,timeout:float,read_timeout:string,persistent_connections:bool,password:string|null}
     */
    private $options;
    /** @var \Redis */
    private $redis;

    /**
     * @param array<string, (string|int|float|false|null)> $options
     *
     * @psalm-param array{host?:string,port?:int,timeout?:float,read_timeout?:string,persistent_connections?:bool,password?:string|null} $options
     */
    public function __construct(array $options = [])
    {
        // with php 5.3 we cannot initialize the options directly on the field definition
        // so we initialize them here for now
        if (! isset(self::$defaultOptions['host'])) {
            self::$defaultOptions['host'] = '127.0.0.1';
        }
        if (! isset(self::$defaultOptions['port'])) {
            self::$defaultOptions['port'] = 6379;
        }
        if (! isset(self::$defaultOptions['timeout'])) {
            self::$defaultOptions['timeout'] = 0.1; // in seconds
        }
        if (! isset(self::$defaultOptions['read_timeout'])) {
            self::$defaultOptions['read_timeout'] = '10'; // in seconds
        }
        if (! isset(self::$defaultOptions['persistent_connections'])) {
            self::$defaultOptions['persistent_connections'] = false;
        }
        if (! isset(self::$defaultOptions['password'])) {
            self::$defaultOptions['password'] = null;
        }

        /** @psalm-suppress PropertyTypeCoercion */
        $this->options = array_merge(self::$defaultOptions, $options);
        $this->redis   = new \Redis();
    }

    /**
     * @param array<string, (string|int|float|false|null)> $options
     *
     * @psalm-param array{host?:string,port?:int,timeout?:float,read_timeout?:string,persistent_connections?:bool,password?:string|null} $options
     */
    public static function setDefaultOptions(array $options) : void
    {
        self::$defaultOptions = array_merge(self::$defaultOptions, $options);
    }

    public static function setPrefix(string $prefix) : void
    {
        self::$prefix = $prefix;
    }

    public function flushRedis() : void
    {
        $this->openConnection();
        $this->redis->eval(
            <<<LUA
for keyIndex,key in ipairs(KEYS) do
    local members = redis.call('smembers', key)
    for memberIndex,member in ipairs(members) do
       redis.call('del', member)
    end
    redis.call('del', key)
end
LUA
            ,
            [
                self::$prefix . Counter::TYPE . self::PROMETHEUS_METRIC_KEYS_SUFFIX,
                self::$prefix . Gauge::TYPE . self::PROMETHEUS_METRIC_KEYS_SUFFIX,
                self::$prefix . Histogram::TYPE . self::PROMETHEUS_METRIC_KEYS_SUFFIX,
            ],
            3
        );
    }

    /**
     * @return MetricFamilySamples[]
     *
     * @throws StorageException
     */
    public function collect() : array
    {
        $this->openConnection();
        $metrics = $this->collectHistograms();
        $metrics = array_merge($metrics, $this->collectGauges());
        $metrics = array_merge($metrics, $this->collectCounters());

        return array_map(
            static function (array $metric) {
                return new MetricFamilySamples($metric);
            },
            $metrics
        );
    }

    /**
     * @throws StorageException
     */
    private function openConnection() : void
    {
        try {
            if ($this->options['persistent_connections']) {
                @$this->redis->pconnect($this->options['host'], $this->options['port'], $this->options['timeout']);
            } else {
                @$this->redis->connect($this->options['host'], $this->options['port'], $this->options['timeout']);
            }
            if ($this->options['password']) {
                $this->redis->auth($this->options['password']);
            }
            $this->redis->setOption(\Redis::OPT_READ_TIMEOUT, $this->options['read_timeout']);
        } catch (RedisException $e) {
            throw new StorageException("Can't connect to Redis server", 0, $e);
        }
    }

    /**
     * @param array<string,mixed> $data
     */
    public function updateHistogram(array $data) : void
    {
        $this->openConnection();
        $bucketToIncrease = '+Inf';
        foreach ($data['buckets'] as $bucket) {
            if ($data['value'] <= $bucket) {
                $bucketToIncrease = $bucket;
                break;
            }
        }
        $metaData = $data;
        unset($metaData['value']);
        unset($metaData['labelValues']);
        $this->redis->eval(
            <<<LUA
local increment = redis.call('hIncrByFloat', KEYS[1], KEYS[2], ARGV[1])
redis.call('hIncrBy', KEYS[1], KEYS[3], 1)
if increment == ARGV[1] then
    redis.call('hSet', KEYS[1], '__meta', ARGV[2])
    redis.call('sAdd', KEYS[4], KEYS[1])
end
LUA
            ,
            [
                $this->toMetricKey($data),
                json_encode(['b' => 'sum', 'labelValues' => $data['labelValues']]),
                json_encode(['b' => $bucketToIncrease, 'labelValues' => $data['labelValues']]),
                self::$prefix . Histogram::TYPE . self::PROMETHEUS_METRIC_KEYS_SUFFIX,
                $data['value'],
                json_encode($metaData),
            ],
            4
        );
    }

    /**
     * @param array<string,mixed> $data
     */
    public function updateGauge(array $data) : void
    {
        $this->openConnection();
        $metaData = $data;
        unset($metaData['value']);
        unset($metaData['labelValues']);
        unset($metaData['command']);
        $this->redis->eval(
            <<<LUA
local result = redis.call(KEYS[2], KEYS[1], KEYS[4], ARGV[1])

if KEYS[2] == 'hSet' then
    if result == 1 then
        redis.call('hSet', KEYS[1], '__meta', ARGV[2])
        redis.call('sAdd', KEYS[3], KEYS[1])
    end
else
    if result == ARGV[1] then
        redis.call('hSet', KEYS[1], '__meta', ARGV[2])
        redis.call('sAdd', KEYS[3], KEYS[1])
    end
end
LUA
            ,
            [
                $this->toMetricKey($data),
                $this->getRedisCommand($data['command']),
                self::$prefix . Gauge::TYPE . self::PROMETHEUS_METRIC_KEYS_SUFFIX,
                json_encode($data['labelValues']),
                $data['value'],
                json_encode($metaData),
            ],
            4
        );
    }

    /**
     * @param array<string,mixed> $data
     */
    public function updateCounter(array $data) : void
    {
        $this->openConnection();
        $metaData = $data;
        unset($metaData['value']);
        unset($metaData['labelValues']);
        unset($metaData['command']);

        $this->redis->eval(
            <<<LUA
local result = redis.call(KEYS[2], KEYS[1], KEYS[4], ARGV[1])
if result == tonumber(ARGV[1]) then
    redis.call('hMSet', KEYS[1], '__meta', ARGV[2])
    redis.call('sAdd', KEYS[3], KEYS[1])
end
return result
LUA
            ,
            [
                $this->toMetricKey($data),
                $this->getRedisCommand($data['command']),
                self::$prefix . Counter::TYPE . self::PROMETHEUS_METRIC_KEYS_SUFFIX,
                json_encode($data['labelValues']),
                $data['value'],
                json_encode($metaData),
            ],
            4
        );
    }

    /**
     * @return array<int,mixed>
     */
    private function collectHistograms() : array
    {
        $keys = $this->redis->sMembers(self::$prefix . Histogram::TYPE . self::PROMETHEUS_METRIC_KEYS_SUFFIX);

        sort($keys);
        $histograms = [];
        foreach ($keys as $key) {
            $raw       = $this->redis->hGetAll($key);
            $histogram = json_decode($raw['__meta'], true);
            unset($raw['__meta']);
            $histogram['samples'] = [];

            // Add the Inf bucket so we can compute it later on
            $histogram['buckets'][] = '+Inf';

            $allLabelValues = [];
            foreach (array_keys($raw) as $k) {
                $d = json_decode($k, true);
                if ($d['b'] === 'sum') {
                    continue;
                }
                $allLabelValues[] = $d['labelValues'];
            }

            // We need set semantics.
            // This is the equivalent of array_unique but for arrays of arrays.
            $allLabelValues = array_map('unserialize', array_unique(array_map('serialize', $allLabelValues)));
            sort($allLabelValues);

            foreach ($allLabelValues as $labelValues) {
                // Fill up all buckets.
                // If the bucket doesn't exist fill in values from
                // the previous one.
                $acc = 0;
                foreach ($histogram['buckets'] as $bucket) {
                    $bucketKey = json_encode(['b' => $bucket, 'labelValues' => $labelValues]);
                    if (! isset($raw[$bucketKey])) {
                        $histogram['samples'][] = [
                            'name' => $histogram['name'] . '_bucket',
                            'labelNames' => ['le'],
                            'labelValues' => array_merge($labelValues, [$bucket]),
                            'value' => $acc,
                        ];
                    } else {
                        $acc                   += $raw[$bucketKey];
                        $histogram['samples'][] = [
                            'name' => $histogram['name'] . '_bucket',
                            'labelNames' => ['le'],
                            'labelValues' => array_merge($labelValues, [$bucket]),
                            'value' => $acc,
                        ];
                    }
                }

                // Add the count
                $histogram['samples'][] = [
                    'name' => $histogram['name'] . '_count',
                    'labelNames' => [],
                    'labelValues' => $labelValues,
                    'value' => $acc,
                ];

                // Add the sum
                $histogram['samples'][] = [
                    'name' => $histogram['name'] . '_sum',
                    'labelNames' => [],
                    'labelValues' => $labelValues,
                    'value' => $raw[json_encode(['b' => 'sum', 'labelValues' => $labelValues])],
                ];
            }
            $histograms[] = $histogram;
        }

        return $histograms;
    }

    /**
     * @return array<int,mixed>
     */
    private function collectGauges() : array
    {
        $keys = $this->redis->sMembers(self::$prefix . Gauge::TYPE . self::PROMETHEUS_METRIC_KEYS_SUFFIX);

        sort($keys);
        $gauges = [];
        foreach ($keys as $key) {
            $raw   = $this->redis->hGetAll($key);
            $gauge = json_decode($raw['__meta'], true);
            unset($raw['__meta']);
            $gauge['samples'] = [];
            foreach ($raw as $k => $value) {
                $gauge['samples'][] = [
                    'name' => $gauge['name'],
                    'labelNames' => [],
                    'labelValues' => json_decode($k, true),
                    'value' => $value,
                ];
            }
            usort($gauge['samples'], static function (array $a, array $b) : int {
                return strcmp(implode('', $a['labelValues']), implode('', $b['labelValues']));
            });
            $gauges[] = $gauge;
        }

        return $gauges;
    }

    /**
     * @return array<int,mixed>
     */
    private function collectCounters() : array
    {
        $keys = $this->redis->sMembers(self::$prefix . Counter::TYPE . self::PROMETHEUS_METRIC_KEYS_SUFFIX);

        sort($keys);
        $counters = [];
        foreach ($keys as $key) {
            $raw     = $this->redis->hGetAll($key);
            $counter = json_decode($raw['__meta'], true);
            unset($raw['__meta']);
            $counter['samples'] = [];
            foreach ($raw as $k => $value) {
                $counter['samples'][] = [
                    'name' => $counter['name'],
                    'labelNames' => [],
                    'labelValues' => json_decode($k, true),
                    'value' => $value,
                ];
            }
            usort($counter['samples'], static function (array $a, array $b) : int {
                return strcmp(implode('', $a['labelValues']), implode('', $b['labelValues']));
            });
            $counters[] = $counter;
        }

        return $counters;
    }

    private function getRedisCommand(int $cmd) : string
    {
        switch ($cmd) {
            case Adapter::COMMAND_INCREMENT_INTEGER:
                return 'hIncrBy';
            case Adapter::COMMAND_INCREMENT_FLOAT:
                return 'hIncrByFloat';
            case Adapter::COMMAND_SET:
                return 'hSet';
            default:
                throw new InvalidArgumentException('Unknown command');
        }
    }

    /**
     * @param array<string,string> $data
     */
    private function toMetricKey(array $data) : string
    {
        return implode(':', [self::$prefix, $data['type'], $data['name']]);
    }
}
