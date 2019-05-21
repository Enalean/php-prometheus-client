<?php

declare(strict_types=1);

namespace Prometheus\Storage;

final class NullStore implements Storage
{
    /**
     * @inheritdoc
     */
    public function collect() : array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function updateHistogram(array $data) : void
    {
        return;
    }

    /**
     * @inheritdoc
     */
    public function updateGauge(array $data) : void
    {
        return;
    }

    /**
     * @inheritdoc
     */
    public function updateCounter(array $data) : void
    {
        return;
    }
}
