<?php

namespace OramaCloud\Client\QueryParams;

class SortBy
{
    private $property;
    private $order;
    private $availableOrders = [
        SortByOrder::ASC,
        SortByOrder::DESC
    ];

    public function __construct(string $property, $order = SortByOrder::ASC)
    {
        $this->property = $property;
        $this->order = strtoupper($order);

        $this->validate();
    }

    public function toArray()
    {
        return [
            'property' => $this->property,
            'order' => $this->order
        ];
    }

    private function validate()
    {
        if (!in_array($this->order, $this->availableOrders)) {
            throw new \InvalidArgumentException('Invalid $order parameter in SortBy');
        }
    }
}
