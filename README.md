# ShipWorks - Fulfillment Integration

ShipWorks is a fulfillment house used to trigger to send products from orders via API.

## Getting Started

Authorization credentials provided by ShipWorks is required in order to test the API. One must install ShipWorks and add all necessary credentials.

### Prerequisites

The API can be achieved successfully via basic http request with POST method. $this->username and $this->password can be exchanged with credentials provided by ShipWorks.

```
As an example, here is the POST request for a GetModule call:
POST https://10.1.10.71/stores/shipworks3.php
HTTP/1.1 User-Agent: shipworks
Content-Type: application/x-www-formurlencoded
Host: 10.1.10.71
Content-Length: 50

action=getmodule&username=admin&password=password&...
```

## Running the tests

Confirmed if the ShipWorks software behaved correctly via POST API request.

## Authors

* **Brian Beal** - *Initial work* - [bealdev](https://github.com/bealdev)
