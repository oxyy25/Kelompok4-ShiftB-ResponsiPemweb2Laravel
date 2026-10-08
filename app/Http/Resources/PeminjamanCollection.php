<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PeminjamanCollection extends ResourceCollection
{
    /** Setiap item dibungkus PeminjamanResource. */
    public $collects = PeminjamanResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection->map(fn ($item) => $item instanceof PeminjamanResource ? $item->toArray($request) : (new PeminjamanResource($item))->toArray($request))->all();
    }
}
