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
use SoapClient;
use SoapFault;

/**
 * Access the Sightline SOAP API.
 *
 * @author Rob Woodward <rob@twfmail.uk>
 */
class SightlineSoap extends AbstractAPI
{
    private readonly string $hostname;

    private readonly string $username;

    private readonly string $password;

    private readonly string $wsdl;

    private readonly int $cacheTtl;

    private bool $shouldCache = true;

    /**
     * @param CacheInterface $cacheItemPool
     */
    public function __construct(private readonly CacheItemPoolInterface $cacheItemPool, array $config)
    {
        $this->hostname = $config['hostname'];
        $this->username = $config['username'];
        $this->password = $config['password'];
        $this->wsdl = $config['wsdl'];

        $this->shouldCache = $config['cache'];
        $this->cacheTtl = $config['cache_ttl'];
    }

    /**
     * Get traffic graph from Sightline using the SOAP API.
     *
     * @param string $queryXML Query XML string
     * @param string $graphXML Graph format XML string
     *
     * @return string A PNG image as a string
     */
    public function getTrafficGraph(string $queryXML, string $graphXML): string
    {
        $cachedItem = null;
        if ($this->shouldCache) {
            $cachedItem = $this->cacheItemPool->getItem($this->getCacheKey($queryXML . $graphXML));

            if ($cachedItem->isHit()) {
                return $cachedItem->get();
            }
        }

        $soapClient = $this->connect();

        try {
            $result = $soapClient->getTrafficGraph($queryXML, $graphXML);
        } catch (SoapFault $soapFault) {
            throw new SightlineApiException('Error getting traffic graph.', 0, $soapFault);
        }

        $fileInfo = finfo_open();
        $mimeType = finfo_buffer($fileInfo, $result, FILEINFO_MIME_TYPE);

        if ('image/png' === $mimeType) {
            if ($this->shouldCache) {
                $cachedItem->expiresAfter($this->cacheTtl);
                $cachedItem->set($result);
                $this->cacheItemPool->save($cachedItem);
            }

            return $result;
        }

        // If we get here theres been an error on the graph. Errors usually come
        // out as XML for traffic queries.
        //
        $this->handleResult($result);
    }

    /**
     * Get traffic XML from Sightline using the web services API.
     *
     * @param string $queryXML Query XML string
     *
     * @return SimpleXMLElement XML traffic data
     */
    public function getTrafficXML(string $queryXML): SimpleXMLElement
    {
        $cachedItem = null;
        if ($this->shouldCache) {
            $cachedItem = $this->cacheItemPool->getItem($this->getCacheKey($queryXML));

            if ($cachedItem->isHit()) {
                return new SimpleXMLElement($cachedItem->get());
            }
        }

        $soapClient = $this->connect();

        try {
            $result = $soapClient->runXmlQuery($queryXML, 'xml');
        } catch (SoapFault $soapFault) {
            throw new SightlineApiException('Error getting traffic xml.', 0, $soapFault);
        }

        $outXML = $this->handleResult($result);

        // If there is a valid result, store in cache.
        //
        if ($this->shouldCache) {
            $cachedItem->expiresAfter($this->cacheTtl);
            $cachedItem->set($outXML->asXml());
            $this->cacheItemPool->save($cachedItem);
        }

        return $outXML;
    }

    /**
     * Run a CLI command on Sightline using the SOAP API.
     *
     * @param string $command The command string to run
     * @param int    $timeout timeout in seconds
     *
     * @return string returns the output from the CLI
     */
    public function cliRun(string $command, int $timeout = 20): string
    {
        $soapClient = $this->connect();

        try {
            return $soapClient->cliRun($command, $timeout);
        } catch (SoapFault $soapFault) {
            throw new SightlineApiException('Error connecting to CLI.', 0, $soapFault);
        }
    }

    /**
     * Connect to the Sightline SOAP API.
     */
    public function connect(): SoapClient
    {
        $opts = [
            'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
        ];

        // SOAP 1.2 client
        $params = [
            'encoding' => 'UTF-8',
            'verifypeer' => false,
            'verifyhost' => false,
            'soap_version' => SOAP_1_2,
            'trace' => 1,
            'connection_timeout' => 180,
            'stream_context' => stream_context_create($opts),
            'location' => sprintf('https://%s/soap/sp', $this->hostname),
            'login' => $this->username,
            'password' => $this->password,
            'authentication' => SOAP_AUTHENTICATION_DIGEST,
        ];

        try {
            return new SoapClient($this->wsdl, $params);
        } catch (SoapFault $soapFault) {
            throw new SightlineApiException('Unable to connect to Sightline API.', 0, $soapFault);
        }
    }

    /**
     * Get the cache key.
     *
     * @param string $xml XML Query string
     *
     * @return string cache key
     */
    private function getCacheKey(string $xml): string
    {
        return 'sightline_soap_' . sha1($xml);
    }
}
