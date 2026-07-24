<?php

namespace App\Models\Concerns;

use App\Models\Image;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasImages
{
    /**
     * @return MorphMany<Image, $this>
     */
    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable')->orderBy('sort_order');
    }

    /**
     * @return MorphOne<Image, $this>
     */
    public function image(string $collection = 'default'): MorphOne
    {
        return $this->morphOne(Image::class, 'imageable')->where('collection', $collection);
    }
}
