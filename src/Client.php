<?php

namespace OramaCloud;

use GuzzleHttp\Client as HttpClient;
use OramaCloud\Client\Cache;
use OramaCloud\Client\Query;
use OramaCloud\Manager\Endpoints;
use OramaCloud\Telemetry\Collector;

class Client
{
    private $answersApiBaseURL;
    private $apiKey;
    private $endpoint;
    private $http;
    private $id;
    private $collector;
    private $cache = true;

    public function __construct(array $params)
    {
        $params = $this->validate($params);

        $this->id = uniqid('p', true);
        $this->http = new HttpClient();
        $this->apiKey = $params['api_key'];
        $this->endpoint = $params['endpoint'];
        $this->answersApiBaseURL = $params['answersApiBaseURL'];
        $this->cache = $params['cache'];

        // Telemetry is enabled by default
        if ($params['telemetry'] !== false) {
            $this->collector = Collector::create([
                'id' => $this->id,
                'api_key' => $this->apiKey,
                'flushInterval' => $params['telemetry']['flushInterval'] ?? 5000,
                'flushSize' => $params['telemetry']['flushSize'] ?? 25
            ]);
        }

        // Cache is enabled by default
        if ($this->cache) {
            $this->cache = new Cache();
        }

        $this->init();
    }

    public function search(Query $query)
    {
        $cacheKey = "search-" . $query->toJson();

        if ($this->cache && $this->cache->has($cacheKey)) {
            $roundTripTime = 0;
            $searchResults = $this->cache->get($cacheKey);
            $cached = true;
        } else {
            $startTime = microtime(true);
            $endpoint = "{$this->endpoint}/search?api-key={$this->apiKey}";
            $response = $this->http->request('POST', $endpoint, [
                'form_params' => [
                    'q' => $query->toJson()
                ]
            ]);

            $searchResults = $response->getBody();

            $endTime = microtime(true);
            $roundTripTime = ($endTime - $startTime) * 1000;
            $cached = false;

            $this->cache->set($cacheKey, $searchResults);
        }

        if ($this->collector !== null) {
            $this->collector->add([
                'rawSearchString' => $query->toArray()['term'],
                'resultsCount' => $searchResults->hits ?? 0,
                'roundTripTime' => $roundTripTime,
                'query' => $query->toJson(),
                'cached' => $cached,
                'searchedAt' => time()
            ]);
        }

        return json_decode($searchResults, true);
    }

    private function validate($params)
    {
        if (empty($params['api_key'])) {
            throw new \InvalidArgumentException('API key is required');
        }

        if (empty($params['endpoint'])) {
            throw new \InvalidArgumentException('Endpoint is required');
        }

        if (isset($params['telemetry']) && $params['telemetry'] !== false && !is_array($params['telemetry'])) {
            throw new \InvalidArgumentException('Telemetry must be an array');
        }

        $params['telemetry'] = isset($params['telemetry']) ? $params['telemetry'] : [
            'flushInterval' => 5000,
            'flushSize' => 25
        ];

        $params['answersApiBaseURL'] = isset($params['answersApiBaseURL']) ? $params['answersApiBaseURL'] : Endpoints::ORAMA_ANSWER_ENDPOINT;

        $params['cache'] = isset($params['cache']) ? $params['cache'] : true;

        return $params;
    }

    private function init()
    {
        //
    }
}
