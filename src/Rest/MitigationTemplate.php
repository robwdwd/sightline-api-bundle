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
 * Access the Sightline REST API Mitigation Template endpoint.
 *
 * @author Rob Woodward <rob@twfmail.uk>
 */
class MitigationTemplate extends REST
{
    protected $cacheKeyPrefix = 'sightline_rest_mt';

    /**
     * Gets multiple mitigation templates with optional filters (See Sightline API Documents).
     *
     * @param array $filters Filters
     * @param int   $perPage Number of pages to get from the server at a time. Default 50.
     *
     * @return array the output of the API call
     */
    public function getMitigationTemplates(?array $filters = null, int $perPage = 50)
    {
        return $this->findRest('mitigation_templates', $filters, $perPage);
    }

    /**
     * Gets multiple mitigation templates with optional filters (See Sightline API Documents).
     *
     * @param string $templateID  Template ID to copy
     * @param string $name        New name for copied mitigation template
     * @param string $description New description for copied mitigation template
     *
     * @return array the output of the API call
     */
    public function copyMitigationTemplate(string $templateID, string $name, string $description)
    {
        $existingTemplate = $this->getByID('mitigation_templates', $templateID);

        // Fix for IP location policing in 9.7 where it returns an empty array.
        if (empty($existingTemplate['data']['attributes']['subobject']['ip_location_policing'])) {
            unset($existingTemplate['data']['attributes']['subobject']['ip_location_policing']);
        }

        $out = $this->createMitigationTemplate($name, $existingTemplate['data']['attributes']['ip_version'], $description, $existingTemplate['data']['attributes']['subobject'], $existingTemplate['data']['relationships'], $existingTemplate['data']['attributes']['subtype']);

        return $out;
    }

    /**
     * Create a new mitigation template. See Sightline SDK Docs for countermeasure and relationship
     * settings.
     *
     * @param string $name            Name of the mitigation template to create
     * @param string $ipVersion       IP Version of the mitigation template
     * @param string $description     Description of mitigation template
     * @param array  $countermeasures Countermeasure settings for this mitigation template
     * @param array  $relationships   Relationships to this mitigation template. See Sightline SDK Docs
     * @param string $subtype         Subtype for this mitigation template
     *
     * @return array The output of the API call
     */
    public function createMitigationTemplate(string $name, string $ipVersion, string $description, array $countermeasures, array $relationships = [], string $subtype = 'tms')
    {
        $url = $this->url . '/mitigation_templates/';

        // Add in the required attributes for a managed object.
        //
        $attributes = [
            'name' => $name,
            'ip_version' => $ipVersion,
            'description' => $description,
            'subtype' => $subtype,
            'subobject' => $countermeasures,
        ];

        // Create the full mitigation template data to be converted to json.
        //
        $moJson = [
            'data' => [
                'attributes' => $attributes,
                'relationships' => $relationships,
                'type' => 'mitigation_template',
            ],
        ];

        $dataString = json_encode($moJson);

        // Send the API request.
        //
        return $this->doPostRequest($url, 'POST', $dataString);
    }

    /**
     * Change a mitigation template.
     *
     * @param string $sightlineID   Mitigation template ID to change
     * @param array  $attributes    Attributes to change on the managed object.
     *                              See Sightline API documentation for a full list of attributes.
     * @param array  $relationships Relationships to this managed object. See Sightline SDK Docs.
     *
     * @return array the output of the API call
     */
    public function changeMitigationTemplate(string $sightlineID, array $attributes, ?array $relationships = null)
    {
        $url = $this->url . '/mitigation_templates/' . $sightlineID;

        $moJson = [
            'data' => [
                'attributes' => $attributes,
            ],
        ];

        if (null !== $relationships) {
            $moJson['data']['relationships'] = $relationships;
        }

        $dataString = json_encode($moJson);

        // Send the API request.
        //
        return $this->doPostRequest($url, 'PATCH', $dataString);
    }
}
