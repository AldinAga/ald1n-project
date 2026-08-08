<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\ProductLine;
use App\Models\ProductType;
use App\Models\SpecificationField;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

final class CatalogDictionary
{
    /** @return array<string,array<string,mixed>> */
    public function definitions(): array
    {
        return [
            'categories' => ['label' => 'Kategorije', 'model' => Category::class],
            'brands' => ['label' => 'Brendovi', 'model' => Brand::class],
            'product-lines' => ['label' => 'Linije proizvoda', 'model' => ProductLine::class],
            'product-types' => ['label' => 'Tipovi artikala', 'model' => ProductType::class],
            'specification-fields' => ['label' => 'Specifikaciona polja', 'model' => SpecificationField::class],
        ];
    }

    /** @return array<string,mixed> */
    public function definition(string $resource): array
    {
        return $this->definitions()[$resource] ?? throw new InvalidArgumentException('Nepoznat kataloški resurs.');
    }

    public function model(string $resource, ?int $id = null): Model
    {
        $class = $this->definition($resource)['model'];
        return $id === null ? new $class() : $class::query()->findOrFail($id);
    }
}
