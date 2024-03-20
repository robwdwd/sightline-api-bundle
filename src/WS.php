<?php
/*
 * This file is part of the Sightline API Bundle.
 *
 * Copyright 2022-2024 Robert Woodward
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Robwdwd\SightlineApiBundle;

use Psr\Cache\CacheItemPoolInterface;
use Robwdwd\SightlineApiBundle\Exception\SightlineApiException;
use SimpleXMLElement;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Access the Sightline REST and web services API.
 *
 * @author Rob Woodward <rob@twfmail.uk>
 */
class WS extends AbstractAPI
{
    private readonly string $wsKey;

    private readonly string $url;

    private readonly int $cacheTtl;

    private bool $shouldCache = true;

    /**
     * @param CacheInterface $cache
     */
    public function __construct(private readonly HttpClientInterface $client, private readonly CacheItemPoolInterface $cache, array $config)
    {
        $this->url = 'https://' . $config['hostname'] . '/arborws/';
        $this->wsKey = $config['wskey'];

        $this->shouldCache = $config['cache'];
        $this->cacheTtl = $config['cache_ttl'];
    }

    /**
     * Get traffic graph from Sightline using the web services API.
     *
     * @param string $queryXML Query XML string
     * @param string $graphXML Graph format XML string
     *
     * @return string returns a PNG image
     */
    public function getTrafficGraph(string $queryXML, string $graphXML)
    {
        $cachedItem = null;
        $url = $this->url . '/traffic/';

        $args = [
            'graph' => $graphXML,
            'query' => $queryXML,
        ];

        if (true === $this->shouldCache) {
            $cachedItem = $this->cache->getItem($this->getCacheKey($url, $args));
            if ($cachedItem->isHit()) {
                return $cachedItem->get();
            }
        }

        $output = $this->doHTTPRequest($url, $args);

        $fileInfo = finfo_open();
        $mimeType = finfo_buffer($fileInfo, $output, FILEINFO_MIME_TYPE);

        if ('image/png' === $mimeType) {
            if (true === $this->shouldCache) {
                $cachedItem->expiresAfter($this->cacheTtl);
                $cachedItem->set($output);
                $this->cache->save($cachedItem);
            }

            return $output;
        }

        $this->handleResult($output);
    }

    /**
     * Get traffic XML from Sightline using the web services API.
     *
     * @param string $queryXML Query XML string
     *
     * @return SimpleXMLElement XML traffic data
     */
    public function getTrafficXML(string $queryXML)
    {
        $cachedItem = null;
        $url = $this->url . '/traffic/';

        $args = [
            'query' => $queryXML,
        ];

        if (true === $this->shouldCache) {
            $cachedItem = $this->cache->getItem($this->getCacheKey($url, $args));

            if ($cachedItem->isHit()) {
                return new SimpleXMLElement($cachedItem->get());
            }
        }

        $outXML = $this->handleResult($this->doHTTPRequest($url, $args));

        if (true === $this->shouldCache) {
            $cachedItem->expiresAfter($this->cacheTtl);
            $cachedItem->set($outXML->asXml());
            $this->cache->save($cachedItem);
        }

        return $outXML;
    }

    /**
     * Perform HTTP Web Services request against the sightline API.
     *
     * @return string Request output content
     */
    private function doHTTPRequest(string $url, array $args)
    {
        $args['api_key'] = $this->wsKey;

        try {
            $response = $this->client->request('GET', $url, ['query' => $args]);
            $content = $response->getContent();
        } catch (ExceptionInterface $e) {
            throw new SightlineApiException('Error in HTTP request', 0, $e);
        }

        if (empty($content)) {
            throw new SightlineApiException('API Server returned no data.');
        }

        return $content;
    }

    /**
     * Get the cache key.
     *
     * @param string $url  URL to make the request against
     * @param array  $args URL args
     *
     * @return string cache key
     */
    private function getCacheKey(string $url, array $args)
    {
        return 'sightline_ws_' . sha1($url . http_build_query($args));
    }
}
