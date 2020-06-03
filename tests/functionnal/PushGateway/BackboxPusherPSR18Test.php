<?php

declare(strict_types=1);

namespace Enalean\PrometheusTestE2E\PushGateway;

use Enalean\Prometheus\PushGateway\PSR18Pusher;
use Enalean\Prometheus\PushGateway\Pusher;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;

final class BackboxPusherPSR18Test extends BlackBoxPushGatewayTest
{
    public function getPusher(string $address): Pusher
    {
        return new PSR18Pusher(
            $address,
            Psr18ClientDiscovery::find(),
            Psr17FactoryDiscovery::findRequestFactory(),
            Psr17FactoryDiscovery::findStreamFactory()
        );
    }
}
