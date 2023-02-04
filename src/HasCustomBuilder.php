<?php

namespace Faliure\LaravelCustomBuilder;

use Closure;

/**
 * Using this trait in your Eloquent model allows you to get custom results
 * from the usual eloquent chains.
 *
 *   Model::customQuery(fn ($result) => $result->toArray())
 *       ->select('id', 'status', 'created_at')
 *       ->where('status', 'pending')
 *       ->latest()
 *       ->get(); // Returns the result as an array instead of a collection
 *
 * @see \Faliure\LaravelCustomBuilder\Builder
 */
trait HasCustomBuilder
{
    public static function customQuery(?Closure $callback = null): Builder
    {
        return new Builder(static::class, $callback);
    }
}
