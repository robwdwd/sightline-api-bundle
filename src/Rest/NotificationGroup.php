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

/**
 * Access the Sightline REST API.
 *
 * @author Rob Woodward <rob@twfmail.uk>
 */
class NotificationGroup extends REST
{
    protected $cacheKeyPrefix = 'sightline_rest_ng';

    /**
     * Gets multiple notification Groups with optional search.
     *
     * @param array $filters Search filters
     * @param int   $perPage Number of pages to get from the server at a time. Default 50.
     *
     * @return array the output of the API call
     */
    public function getNotificationGroups(?array $filters = null, int $perPage = 50)
    {
        return $this->findRest('notification_groups', $filters, $perPage);
    }

    /**
     * Create a new managed object.
     *
     * @param string $name            Name of the managed object to create
     * @param array  $emailAddresses  Email addresses to add to the notification group
     * @param array  $extraAttributes Extra attributes to add to this notification group. See Sightline SDK Docs.
     *
     * @return array the output of the API call
     */
    public function createNotificationGroup(string $name, ?array $emailAddresses = null, ?array $extraAttributes = null)
    {
        $url = $this->url . '/notification_groups/';

        // Add in the required attributes for a notification group.
        //
        $requiredAttributes = ['name' => $name];

        if (isset($emailAddresses)) {
            $requiredAttributes['smtp_email_addresses'] = implode(' ', $emailAddresses);
        }

        // Merge in extra attributes for this managed object
        //
        if (null === $extraAttributes) {
            $attributes = $requiredAttributes;
        } else {
            $attributes = array_merge($requiredAttributes, $extraAttributes);
        }

        // Create the full managed object data to be converted to json.
        //
        $ngJson = [
            'data' => [
                'attributes' => $attributes,
            ],
        ];

        $dataString = json_encode($ngJson);

        // Send the API request.
        //
        return $this->doPostRequest($url, 'POST', $dataString);
    }

    /**
     * Change a notification group.
     *
     * @param string $sightlineID Notification group ID to change
     * @param array  $attributes  Attributes to change on the notifciation group
     *                            See Sightline API documentation for a full list of attributes
     *
     * @return array the output of the API call
     */
    public function changeNotificationGroup(string $sightlineID, array $attributes)
    {
        $url = $this->url . '/notification_groups/' . $sightlineID;

        $ngJson = [
            'data' => [
                'attributes' => $attributes,
            ],
        ];

        $dataString = json_encode($ngJson);

        // Send the API request.
        //
        return $this->doPostRequest($url, 'PATCH', $dataString);
    }
}
