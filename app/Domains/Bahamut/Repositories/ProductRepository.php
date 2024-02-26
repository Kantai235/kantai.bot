<?php

namespace App\Domains\Bahamut\Repositories;

use App\Domains\Bahamut\Models\Product;
use App\Repositories\BaseRepository;

class ProductRepository extends BaseRepository
{
    public function __construct(Product $product)
    {
        $this->setModel($product::class);
    }

    public function findProductBySn(string $sn): Product|bool
    {
        $product = $this->model
            ->where('sn', $sn)
            ->first();

        if ($product instanceof $this->model) {
            return $product;
        }

        return false;
    }
}
