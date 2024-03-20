# REST API

The rest API allows for access to Sightline REST API services. Documentation
is avaiable from Sightline and from the Portal UI.

## Error Checking

The REST class uses the HTTP client to handle the error checking based
on HTTP status code or a transport/network error but it also tries to
find errors in the returned response from the Sightline API. If there is an
error an SightlineApiException will be thrown.

```php
try {
    $response = $sightlineApi->getByID('managed_object', '22');
} catch SightlineApiException $e {
    $this->addFlash('error', $e->errorMessage());
}

```

## Getting Elements

getByID gets an endpoint element from the Sightline Leader. See the Sightline
documentation for a list of valid endpoints.

The following example gets Managed object data for a peer.

```php
use Robwdwd\SightlineApiBundle\REST\Rest as SightlineRest;

public function show(Peer $peer, Request $request, SightlineRest $sightlineRest): Response
{
    if ($peer->getSightlineMoId()) {
        $sightlineMO = $sightlineRest->getByID('managed_objects', $peer->getSightlineMoId());
        dump ($sightlineMO);
    }
}
```

## Endpoints

Seperate classes are available for Managed Objects, Mitigation Templates,
Notification groups and Traffic Queries.

```php
use Robwdwd\SightlineApiBundle\Rest\ManagedObject;
use Robwdwd\SightlineApiBundle\Rest\MitigationTemplate;
use Robwdwd\SightlineApiBundle\Rest\NotificationGroup;
use Robwdwd\SightlineApiBundle\Rest\TrafficQuery;
```

## Traffic Queries

Traffic queries were introduced in v9 of the Siteline API. Various helper functions
exist to allow for easy access to various traffic queries. getPeerTraffic(), getInterfaceTraffic(),
getInterfaceAsnTraffic().

Building a traffic query json to send to Sightline REST API can be done with the following function.

```php

use Robwdwd\SightlineApiBundle\Rest\TrafficQuery;

// Get traffic for peer managed object.
//
$peerID = $peer->getPeerManagedObjectID();
$result = $trafficQuery->getPeerTraffic($peerId, '7 days ago', 'now');

// Find traffic for given interfaces and matching AS Path.
//
$interfaceIds = ["122334", "9292929", "2202092"]; // List of Sightline interface IDs
$asPath = '_6768_';  // AS Path regular expression

$trafficQuery->getInterfaceAsPathTraffic($asPath, $interfaceIds, '2 days ago', 'now')

// Build query to get traffic on a given interface broken down by AS Origin.
//
$interfaceID = 1298278;
$startDate = '1 week ago';
$endDate = 'now';

// Build filter, filters take exact same format as described in Sightline API documenation
// facet = Filter Type, values is array of filter matches, and groupby groups data
// by this filter.
//
$filters = [
            ['facet' => 'Interface', 'values' => [$interfaceId], 'groupby' => false],
            ['facet' => 'AS_Origin', 'values' => [], 'groupby' => true],
        ];

$query = $trafficQuery->buildTrafficQueryJson($filters, $startDate, $endDate);

// Do the traffic query post request, this works with caching unlike doPostRequest
// in the base REST class.
//
$result = $trafficQuery->doCachedPostRequest($url, 'POST', $queryJson);

```

## Managed Objects

You can get multiple managed objects with the managed object helper
function. This searches the Attributes of any returned managed object
for an exact match for the search string. By default it gets 50 objects
per page which can be changed with the third parameter.

Filters is an array with type, operator, field and search term.

Type can be, 'a' or 'r', attribute or relationship. Operator can be eq
(equal to) or cn (contains). Field is the field to search. Search can be
a string.

The following gets all peer managed objects retrieving 25 per page.

```php
public function list(Request $request ManagedObject $managedObject): Response
{
    $filter = ['type' => 'a', 'operator' => 'eq', 'field' => 'family', 'search' => 'peer'];

    $sightlineMOs = $managedObject->getManagedObjects($filter, 25);
    dump ($sightlineMOs);

}
```

The following gets all managed objects matching SomeNetwork as the name
(this would be just one).

```php
public function list(Request $request ManagedObject $managedObject): Response
{

    $sightlineMOs = $managedObject->getManagedObjects('name', 'SomeNetwork');
    dump ($sightlineMOs);

}
```

### Updating a managed object

Managed objects can be updated. You need to provide a
`` `$attributes ``\` array which changes any attributes on an existing
managed object. The `` `$relationships ``\` array is optional. For
specificys on what fields the attributes and relationships can have
check the SightlineREST documentation.

```php
public function updateMo(Peer $peer, ManagedObject $managedObject): Response
{
    if ($peer->getSightlineMoId()) {
        $sightlinePeer = $peerRepository->getForSightline($peer->getId());

        // Change the name, match type, match and tags
        //
        $attributes = ['name' => $peer->getName(),
            'match' => "702, 701, 703"  // Match is a string.
            'match_type' => 'peer_as',  // Match peer ASN.
            'tags' => ['Downstream', 'ISP'],   // Tags is an array.
        ];


        // Set the shared host detection settings.
        $relationships = [
            'shared_host_detection_settings' => [
                'data' => [
                    'type' => 'shared_host_detection_setting',
                    'id' => '0', // This is the ID for disabled (Turns host detection off).
                ],
            ],
        ];

        $output[] = $managedObject->changeManagedObject($peer->getSightlineMoId(), $attributes, $relationships);

        // Check for errors.
        if ($managedObject->hasError()) {
            foreach ($managedObject->errorMessage() as $error) {
                $this->addFlash('error', $error);
            }

            return $this->redirectToRoute('peer_view', ['id' => $peer->getId()]);
        }

        $this->addFlash('success', 'Peer managed object updated.');
    } else {
        $this->addFlash('error', 'Peer does not have an Sightline managed object ID.');
    }

    return $this->redirectToRoute('peer_view', ['id' => $peer->getId()]);
}
```

## Notification Groups

The following gets a notification group.

```php
public function list(Request $request NotificationGroup $ng): Response
{
    $SightlineNG = $ng->getNotificationGroups('name', 'Group1');
    dump ($SightlineNG);
}
```

## Mitigation Templates

Mitigation templates can be updated, changed and copied in the same way as managed objects.

```php

use Robwdwd\SightlineApiBundle\Rest\MitigationTemplate;

public function updateMo(Peer $peer, MitigationTemplate $mitigationTemplate): Response
{
    $templateID = 1; // Template to copy from
    $newName = 'Copied Template';
    $newDescription = 'This is a template copy';

    $mitigationTemplate->copyMitigationTemplate($templateID,  $newName, $newDescription);
}
```

## findRest()

Most of the helper functions such as `` `getManagedObjects ``\` us the
low level findRest function.

> `` `findRest($endpoint, $field = null, $search = null, $perPage = 50) ``\`

The following retrieves all customer managed objects from the Sightline
Leader.

```php
public function list(Request $request SightlineRest $sightlineRest): Response
{

    $sightlineMOs = $sightlineRest->findRest('managed_objects', 'family', 'customer');
    dump ($sightlineMOs);

}
```

## findRestPaged()

Similar to findRest() except this returns results one page a time.

> `` `findRest($endpoint, $field = null, $search = null, $perPage = 50) ``\`

The following retrieves interface lists for sightline

```php
public function list(Request $request SightlineRest $sightlineRest): Response
{
    // findRestPaged(string $endpoint, array $filters = null, int $perPage = 50, bool $commitFlag = false)
    $sightlineRestPaged->findRestPaged('traffic_query_facet_values/interfaces', null, 200, true);

    do {
        $result = $sightlineRestPaged->getCurrentData();

        foreach ($result as $r) {
            dump($r);
        }
    } while ($sightlineRestPaged->getNextPage());

}
```