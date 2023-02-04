<?php

namespace Faliure\LaravelCustomBuilder;

use Closure;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use InvalidArgumentException;

class Builder
{
    public const GET   = 'get';
    public const FIRST = 'first';
    public const COUNT = 'count';
    public const PLUCK = 'pluck';

    /**
     * Methods that finish query building and generate a result.
     */
    private const FINAL_METHODS = [
        self::GET,
        self::FIRST,
        self::COUNT,
        self::PLUCK,
    ];

    /**
     * Underlying Eloquent Builder.
     */
    private EloquentBuilder $builder;

    /**
     * Custom callbacks to apply on final methods of the Builder.
     */
    private array $callbacks;

    /**
     * Construct a new CustomBuilder, from a Model, an EloquentBuilder or a Model's
     * class (FQN).
     */
    public function __construct(Model|EloquentBuilder|string $model, ?Closure $callback = null)
    {
        if ($model instanceof EloquentBuilder) {
            $this->builder = $model;
        } elseif ($model instanceof Model) {
            $this->builder = $model->newQuery();
        } elseif (is_subclass_of($model, Model::class)) {
            $this->builder = $model::query();
        } else {
            throw new InvalidArgumentException("$model is not an Eloquent Model");
        }

        $this->setCallbacks($callback ?? fn($x) => $x);
    }

    public function setCallback(Closure $callback, string $finalMethod)
    {
        return $this->setCallbacks($callback, [ $finalMethod ]);
    }

    public function setCallbacks(Closure $callback, array $finalMethods = []): self
    {
        if (! $finalMethods) {
            return $this->setCommonCallback($callback);
        }

        $this->validateFinalMethods($finalMethods);

        foreach ($finalMethods as $method) {
            $this->callbacks[$method] = $callback;
        }

        return $this;
    }

    /**
     * Forward calls to the underlying Eloquent Builder; for final methods (as
     * defined in @@FINAL_METHODS), process the custom callback response.
     */
    public function __call($method, $parameters)
    {
        $response = $this->builder->$method(...$parameters);

        return in_array($method, static::FINAL_METHODS)
            ? ($this->callbacks[$method])($response)
            : $this;
    }

    /**
     * Set the same callback for all final methods.
     */
    private function setCommonCallback(Closure $callback): self
    {
        $this->callbacks = [];

        foreach (static::FINAL_METHODS as $method) {
            $this->callbacks[$method] = $callback;
        }

        return $this;
    }

    /**
     * For an array of FinalMethods, make sure they're all valid.
     *
     * @throws InvalidArgumentException  If at least one method is invalid
     */
    private function validateFinalMethods(array $finalMethods): void
    {
        $invalidMethods = array_diff($finalMethods, static::FINAL_METHODS);

        if ($invalidMethods) {
            throw new InvalidArgumentException(
                "[CustomBuilder] Unrecognized or non-final methods " .
                Arr::join($invalidMethods, ', ', ' and ')
            );
        }
    }
}
