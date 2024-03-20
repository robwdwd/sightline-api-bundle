<?php
/*
 * This file is part of the Sightline API Bundle.
 *
 * Copyright 2022-2024 Robert Woodward
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Robwdwd\SightlineApiBundle\Rest;

use Robwdwd\SightlineApiBundle\Exception\SightlineApiException;

/**
 * Access the Sightline REST API.
 *
 * @author Rob Woodward <rob@twfmail.uk>
 */
class Paged extends REST
{
    protected $cacheKeyPrefix = 'sightline_rest_paged';

    private int $currentPage = 1;

    private int $totalPages = 0;

    private array $currentData = [];

    private string $oururl = '';

    private array $ourArgs = [];

    /**
     * Find or search Sigthline REST API for a particular record or set of
     * records, uses paging instead of returning full result set.
     *
     * @param string $endpoint   Endpoint type, Managed Object, Mitigations etc.
     *                           See Sightline API documenation for endpoint list.
     * @param array  $filters    Search filters
     * @param int    $perPage    Limit the number of returned objects per page, default 50
     * @param bool   $commitFlag Add config=commited to endpoints which require it, default false
     */
    public function findRestPaged(string $endpoint, ?array $filters = null, int $perPage = 50, bool $commitFlag = false): void
    {
        // Clear any current arguments for request and any current data.
        $this->currentData = [];
        $this->ourArgs = [];

        // Generate new URL.
        $this->oururl = $this->url . $endpoint . '/';

        if (null !== $filters) {
            $this->oururl .= '?' . $this->filterToUrl($filters);
        }

        $this->ourArgs['perPage'] = $perPage;
        $this->ourArgs['page'] = 1;

        if ($commitFlag) {
            $this->ourArgs['config'] = 'committed';
        }

        $result = $this->doGetRequest($this->oururl, $this->ourArgs);

        $this->currentData = $result['data'];

        //
        // Work out the number of pages.
        //
        if (isset($result['links']['last'])) {
            parse_str(parse_url((string) $result['links']['last'])['query'], $parsed);
            $this->totalPages = $parsed['page'];
        } else {
            throw new SightlineApiException('Unable to determine the number of pages.');
        }
    }

    /**
     * Get next page of date from API. Returns true if there is more data or
     * false if there are no more pages of data.
     */
    public function getNextPage(): bool
    {
        // Increment page by one
        ++$this->ourArgs['page'];

        // Have we gone over the total pages?
        if ($this->ourArgs['page'] > $this->totalPages) {
            return false;
        }

        // Get result data.
        $result = $this->doGetRequest($this->oururl, $this->ourArgs);

        $this->currentData = $result['data'];

        ++$this->currentPage;

        return true;
    }

    /**
     * Get current data from the API request.
     */
    public function getCurrentData(): array
    {
        return $this->currentData;
    }

    /**
     *  Get the current page number.
     */
    public function getCurrentPageNumber(): int
    {
        return $this->currentPage;
    }
}
