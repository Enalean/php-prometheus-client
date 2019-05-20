<?php

declare(strict_types=1);

namespace Test\Prometheus\PushGateway;

use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Prometheus\PushGateway\PSR18Pusher;
use Prometheus\PushGateway\Pusher;

final class BackboxPusherPSR18Test extends BlackBoxPushGatewayTest
{
    public function getPusher(string $address) : Pusher
    {
        return new PSR18Pusher(
            $address,
            Psr18ClientDiscovery::find(),
            Psr17FactoryDiscovery::findRequestFactory(),
            Psr17FactoryDiscovery::findStreamFactory()
        );
    }
}
