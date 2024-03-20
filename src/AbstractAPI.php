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

use DomDocument;
use DOMElement;
use Robwdwd\SightlineApiBundle\Exception\SightlineApiException;
use SimpleXMLElement;

/**
 * Base API class for the ArborWS and ArborSOAP APIs.
 *
 * @author Rob Woodward <rob@twfmail.uk>
 */
abstract class AbstractAPI
{
    private bool $shouldCache = true;

    /**
     * Get traffic XML from Sightline using the web services API.
     *
     * @param string $queryXML Query XML string
     *
     * @return SimpleXMLElement XML traffic data
     */
    abstract public function getTrafficXML(string $queryXML): SimpleXMLElement;

    /**
     * Get traffic graph from Sightline using the web services API.
     *
     * @param string $queryXML Query XML string
     * @param string $graphXML Graph format XML string
     *
     * @return string returns a PNG image (as a string)
     */
    abstract public function getTrafficGraph(string $queryXML, string $graphXML): string;

    /**
     * Get Peer Managed object traffic graph from Sightline. This is a detail graph with in, out, total.
     *
     * @param int    $sightlineID Arbor Managed Object ID
     * @param string $title       Title of the graph
     * @param string $startDate   Start date for the graph
     * @param string $endDate     End date for the graph
     *
     * @return string returns a PNG image
     */
    public function getPeerTrafficGraph(int $sightlineID, string $title, string $startDate = '7 days ago', string $endDate = 'now'): string
    {
        $filters = [
            ['type' => 'peer', 'value' => $sightlineID, 'binby' => false],
        ];

        $queryXML = $this->buildQueryXML($filters, $startDate, $endDate, 'bps', ['in', 'out', 'total']);
        $graphXML = $this->buildGraphXML($title, 'bps', true);

        return $this->getTrafficGraph($queryXML, $graphXML);
    }

    /**
     * Get ASN traffic graph traffic graph from Sightline.
     *
     * @param string $asPath    AS Patch match string
     * @param string $startDate Start date for the graph
     * @param string $endDate   End date for the graph
     *
     * @return string returns a PNG image
     */
    public function getAsPathTrafficGraph(string $asPath, string $startDate = '7 days ago', string $endDate = 'now'): string
    {
        $filters = [
            ['type' => 'aspath', 'value' => $asPath, 'binby' => true],
        ];

        $queryXML = $this->buildQueryXML($filters, $startDate, $endDate);
        $graphXML = $this->buildGraphXML('Traffic with AS' . $asPath, 'bps [+ to / - from ]', false, 986, 270);

        return $this->getTrafficGraph($queryXML, $graphXML);
    }

    /**
     * Get ASN traffic stats from Sightline.
     *
     * @param string $asPath    AS path string
     * @param string $startDate Start date for the graph
     * @param string $endDate   End date for the graph
     *
     * @return SimpleXMLElement Traffic data XML
     */
    public function getAsPathTrafficXML(string $asPath, string $startDate = '7 days ago', string $endDate = 'now'): SimpleXMLElement
    {
        $filters = [
            ['type' => 'aspath', 'value' => $asPath, 'binby' => true],
        ];

        $queryXML = $this->buildQueryXML($filters, $startDate, $endDate);

        return $this->getTrafficXML($queryXML);
    }

    /**
     * Get interface traffic graph from Sightline.
     *
     * @param int    $sightlineID Arbor Interface Object ID
     * @param string $title       Title of the graph
     * @param string $startDate   Start date for the graph
     * @param string $endDate     End date for the graph
     *
     * @return string returns a PNG image
     */
    public function getIntfTrafficGraph(int $sightlineID, string $title, string $startDate = '7 days ago', string $endDate = 'now'): string
    {
        $filters = [
            ['type' => 'interface', 'value' => $sightlineID, 'binby' => false],
        ];

        $queryXML = $this->buildQueryXML($filters, $startDate, $endDate, 'bps', ['in', 'out', 'total', 'dropped', 'backbone']);
        $graphXML = $this->buildGraphXML($title, 'bps', true);

        return $this->getTrafficGraph($queryXML, $graphXML);
    }

    /**
     * Get ASN traffic graph broken down by interface from Sightline.
     *
     * @param string $asPath       AS Path string
     * @param array  $interfaceIds Array of interface IDs to filter on
     * @param string $title        Title of the graph
     * @param string $startDate    Start date for the graph
     * @param string $endDate      End date for the graph
     *
     * @return string returns a PNG image
     */
    public function getInterfaceAsPathTrafficGraph(
        string $asPath,
        array $interfaceIds,
        string $title,
        string $startDate = '7 days ago',
        string $endDate = 'now'
    ): string {
        sort($interfaceIds, SORT_NUMERIC);

        $filters = [
            ['type' => 'interface', 'value' => $interfaceIds, 'binby' => true],
            ['type' => 'aspath', 'value' => $asPath, 'binby' => true],
        ];

        $queryXML = $this->buildQueryXML($filters, $startDate, $endDate);

        $graphXML = $this->buildGraphXML($title, 'bps (-In / +Out)', false, 986, 270);

        return $this->getTrafficGraph($queryXML, $graphXML);
    }

    /**
     * Get ASN traffic stats broken down by interface from Sightline.
     *
     * @param string $asPath       AS Path match string
     * @param array  $interfaceIds Array of interface IDs to filter on
     * @param string $startDate    Start date for the graph
     * @param string $endDate      End date for the graph
     *
     * @return SimpleXMLElement Traffic data XML
     */
    public function getInterfaceAsPathTrafficXML(
        string $asPath,
        array $interfaceIds,
        string $startDate = '7 days ago',
        string $endDate = 'now'
    ): SimpleXMLElement {
        sort($interfaceIds, SORT_NUMERIC);
        $filters = [
            ['type' => 'interface', 'value' => $interfaceIds, 'binby' => true],
            ['type' => 'aspath', 'value' => $asPath, 'binby' => false],
        ];

        $queryXML = $this->buildQueryXML($filters, $startDate, $endDate);

        return $this->getTrafficXML($queryXML);
    }

    /**
     * Get Interface traffic graph broken down by ASN from Sightline.
     *
     * @param int    $interfaceId Interface IDs to filter on
     * @param string $title       Title of the graph
     * @param string $startDate   Start date for the graph
     * @param string $endDate     End date for the graph
     *
     * @return string A PNG image as a string
     */
    public function getIntfAsnTrafficGraph(int $interfaceId, string $title, string $startDate = '7 days ago', string $endDate = 'now'): string
    {
        $filters = [
            ['type' => 'interface', 'value' => $interfaceId, 'binby' => false],
            ['type' => 'as_origin', 'value' => null, 'binby' => true],
        ];

        $queryXML = $this->buildQueryXML($filters, $startDate, $endDate);

        $graphXML = $this->buildGraphXML($title, 'bps (-In / +Out)', false, 986, 270);

        return $this->getTrafficGraph($queryXML, $graphXML);
    }

    /**
     * Get interface traffic stats broken down by ASN from Sightline.
     *
     * @param int    $interfaceId Interface IDs to filter on
     * @param string $startDate   Start date for the graph
     * @param string $endDate     End date for the graph
     *
     * @return SimpleXMLElement Traffic data XML
     */
    public function getIntfAsnTrafficXML(int $interfaceId, string $startDate = '7 days ago', string $endDate = 'now'): SimpleXMLElement
    {
        $filters = [
            ['type' => 'interface', 'value' => $interfaceId, 'binby' => false],
            ['type' => 'as_origin', 'value' => null, 'binby' => true],
        ];

        $queryXML = $this->buildQueryXML($filters, $startDate, $endDate);

        return $this->getTrafficXML($queryXML);
    }

    /**
     * Build XML for querying the Web Services API.
     *
     * @param array  $filters   filters array
     * @param string $startDate start date/time for data
     * @param string $endDate   end date/time for data
     * @param string $unitType  Units of data to gather. bps or pps.
     * @param array  $classes   Classes of data to gather. in, out, total, backbone, dropped.
     *
     * @return string An XML string used to Query the WS API
     */
    public function buildQueryXML(
        array $filters,
        string $startDate = '7 days ago',
        string $endDate = 'now',
        string $unitType = 'bps',
        array $classes = []
    ): string {
        $domDocument = $this->getBaseXML();
        $baseNode = $domDocument->firstChild;

        // Create Query Node.
        $queryNode = $domDocument->createElement('query');
        $queryNode->setAttribute('type', 'traffic');

        $baseNode->appendChild($queryNode);

        // Create time Node.
        $timeNode = $domDocument->createElement('time');
        $timeNode->setAttribute('end_ascii', $endDate);
        $timeNode->setAttribute('start_ascii', $startDate);

        $queryNode->appendChild($timeNode);

        // Create unit node.
        $unitNode = $domDocument->createElement('unit');
        $unitNode->setAttribute('type', $unitType);

        $queryNode->appendChild($unitNode);

        // Create search node.
        $searchNode = $domDocument->createElement('search');
        $searchNode->setAttribute('timeout', 30);
        $searchNode->setAttribute('limit', 200);

        $queryNode->appendChild($searchNode);

        // Add the class nodes
        foreach ($classes as $class) {
            $classNode = $domDocument->createElement('class', $class);
            $queryNode->appendChild($classNode);
        }

        // Add the filters.
        foreach ($filters as $filter) {
            if (isset($filter['type'])) {
                $filterNode = $this->addQueryFilter($filter, $domDocument);
                if ($filterNode) {
                    $queryNode->appendChild($filterNode);
                }
            }
        }

        $xml = $domDocument->saveXML();

        if (false === $xml) {
            throw new SightlineApiException('Error creating query XML');
        }

        return $xml;
    }

    /**
     * Build XML for graph output.
     *
     * @param string $title  title of the graph
     * @param string $yLabel label for the Y-Axis on the graph
     * @param bool   $detail sets the graph to be a detail graph type when true
     * @param int    $width  graph width
     * @param int    $height graph height
     *
     * @return string A XML string used to configure the graph returned by the WS API
     */
    public function buildGraphXML(string $title, string $yLabel, bool $detail = false, int $width = 986, int $height = 180): string
    {
        $domDocument = $this->getBaseXML();
        $baseNode = $domDocument->firstChild;

        $graphNode = $domDocument->createElement('graph');
        $graphNode->setAttribute('id', 'graph1');

        $baseNode->appendChild($graphNode);

        $graphNode->appendChild($domDocument->createElement('title', $title));
        $graphNode->appendChild($domDocument->createElement('ylabel', $yLabel));
        $graphNode->appendChild($domDocument->createElement('width', $width));
        $graphNode->appendChild($domDocument->createElement('height', $height));
        $graphNode->appendChild($domDocument->createElement('legend', 1));

        if ($detail) {
            $graphNode->appendChild($domDocument->createElement('type', 'detail'));
        }

        $xml = $domDocument->saveXML();

        if (false === $xml) {
            throw new SightlineApiException('Error creating graph XML');
        }

        return $xml;
    }

    /**
     * Turn the cache on or off.
     *
     * @param bool $cacheOn Cache or not
     */
    public function setShouldCache(bool $cacheOn): void
    {
        $this->shouldCache = $cacheOn;
    }

    /**
     * Handle XML result output.
     */
    public function handleResult(string $output): SimpleXMLElement
    {
        $outXML = new SimpleXMLElement($output);

        // If we get here theres been an error on the graph. Errors usually come
        // out as XML for traffic queries.
        //
        if ($outXML->{'error-line'}) {
            $errorMessage = '';
            foreach ($outXML->{'error-line'} as $error) {
                $errorMessage .= $error . "\n";
            }

            throw new SightlineApiException($errorMessage);
        }

        return $outXML;
    }

    /**
     * Gets a base XML DOM document.
     *
     * @return DomDocument The DOM document to use as the base XML
     */
    private function getBaseXML(): DomDocument
    {
        $domDocument = new DomDocument('1.0', 'UTF-8');
        $domDocument->formatOutput = true;
        $peakflowNode = $domDocument->createElement('peakflow');
        $peakflowNode->setAttribute('version', '2.0');

        $domDocument->appendChild($peakflowNode);

        return $domDocument;
    }

    /**
     * Get a Dom Element for use in the Query XML.
     *
     * @param array       $filter      the filter array to build the filter node for the XML
     * @param DomDocument $domDocument the DOMDocument object
     *
     * @return DomDocument The DOM element to include in the query XML
     */
    private function addQueryFilter(array $filter, DomDocument $domDocument): DomDocument
    {
        $domElement = $domDocument->createElement('filter');

        if (!$domElement instanceof DOMElement) {
            throw new SightlineApiException('Error creating XML');
        }

        $domElement->setAttribute('type', $filter['type']);

        if (true === $filter['binby']) {
            $domElement->setAttribute('binby', 1);
        }

        if (null !== $filter['value']) {
            if (is_array($filter['value'])) {
                foreach ($filter['value'] as $fvalue) {
                    $instanceNode = $domDocument->createElement('instance');
                    $instanceNode->setAttribute('value', $fvalue);
                    $domElement->appendChild($instanceNode);
                }
            } else {
                $instanceNode = $domDocument->createElement('instance');
                $instanceNode->setAttribute('value', $filter['value']);
                $domElement->appendChild($instanceNode);
            }
        }

        return $domElement;
    }
}
