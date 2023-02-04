# faliure/laravel-custom-builder

Laravel Custom Builder allows you to perform Eloquent queries as you normally would, but changing the shape or type of its response.

---

## API

The Builder class can be initialized with a number of different types as the first argument:

- From an Eloquent Builder
  - The custom Builder will just use the Elqouent Builder in its current state (which may include some operations already performed on it).

- From an Eloquent Model
  - The underlying Eloquent Builder will be a fresh one for this Model instance (you could, for example, fetch relations and continue the chain from there)

- From an Eloquent Model's class (FQN)
  - The underying Eloquen Builder will be a fresh one for this Model class

The second argument, optional, is a generic `$callback` that will apply to any final methods of the Eloquent chain (recognized final methods are `first()`, `get()`, `count()` and `pluck()`). It's rarely the case that you would use a single callback for all final methods, but in some cases it could make sense (see the first trivial example below).

Normally, you would not use the second argument, but instead would define different callbacks for different final methods. This is done by calling `Builder@setCallback()` or `Builder@setCallbacks()`, as in the following examples:

```php
use Faliure\LaravelCustomBuilder\Builder;

# Generic callback for all final methods
$builder = new Builder(User::class, fn ($x) => $x->toArray());

# Custom callback, only for `get()`
# NOTE: the other final methods don't transform the result
$builder = new Builder(User::class);
$builder->setCallback($someCallback, Builder::GET);

# Custom callback for `get()` and `first()`
# NOTE: the other final methods don't transform the result
$builder = new Builder(User::class);
$builder->setCallbacks($someCallback, [ Builder::GET, Builder::FIRST ]);
```

---

## Usage

Basic example (just for illustration, not really useful):

```php
use App\Models\User;
use Faliure\LaravelCustomBuilder\Builder;

$builder = new Builder(User::class, fn ($result) => $result->toArray());

$userData = $builder->first();
$usersData = $builder->where('status', 'active')->get();
```

In both cases, the result is not the Model or an Eloquent Collection of Models, but instead the array representations of both.

Let's see a more realistic example. Here, we have a transform option that comes from elsewhere, and we just plug it here using the Custom Builder:

```php
use App\Models\User;
use Faliure\LaravelCustomBuilder\Builder;

$transform = TransformsFactory::getSomeUserTransformFn(); // Returns some hypotetical transformation closure

$builder = new Builder(User::class, $transform);
$usersTransformed = $builder->get();
```

In this case, without the Custom Builder we would have done something like:

```php
use App\Models\User;
use Faliure\LaravelCustomBuilder\Builder;

$transform = TransformsFactory::getSomeUserTransformFn(); // Returns some hypotetical transformation closure

$usersTransformed = $transform(User::all());
```

Where it really gets interesting is when you can build your own custom functions that leverage the Builder to seamlessly transform regular Eloquent results into something else (see, for example, [faliure/laravel-resourceable](https://github.com/faliure/laravel-resourceable)).

```php
trait HasResources // Eloquent Model trait
{
    public static function resourcesQuery(?string $resourceClass = null): Builder
    {
        $resourceClass ??= static::resourceClass();

        return (new Builder(static::class))
            ->setCallback(
                fn ($models) => $resourceClass::collection($models),
                Builder::GET
            )->setCallback(
                fn ($model) => $model->resource(),
                Builder::FIRST
            );
    }
}

# Elsewhere in the code
$resources = User::resourcesQuery()->where('some', 'constraint')->get();
```

With this example, we can package the complexity of creating the builder and providing it the callback(s), and just create some handy functions with it.

Check [faliure/laravel-resourceable](https://github.com/faliure/laravel-resourceable) for a real-life usage example.
