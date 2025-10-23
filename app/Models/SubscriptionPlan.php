<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class SubscriptionPlan extends Model
{
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Indicates if the model exists.
     *
     * @var bool
     */
    public $exists = true;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'subscription_plans';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'plan',
        'description',
        'price_id',
        'price',
        'features',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'features' => 'array',
        'price' => 'float',
    ];

    /**
     * Get a new query builder for the model's table.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function query()
    {
        $plans = collect(Config::get('subscriptions.subscriptions', []));

        $builder = new Builder(new QueryBuilder(app('db')->connection()));
        $builder->setModel(new static());

        // Override the count method to return the number of plans
        $builder->getQuery()->shouldSelect = ['*'];
        $builder->getQuery()->from = 'subscription_plans';

        // Override the get method to return the plans
        $builder->getQuery()->shouldSelect = ['*'];
        $builder->getQuery()->from = 'subscription_plans';

        // Add a custom where clause to make the query valid
        $builder->whereRaw('1 = 1');

        return $builder;
    }

    /**
     * Get the number of subscription plans.
     *
     * @return int
     */
    public static function count()
    {
        return collect(Config::get('subscriptions.subscriptions', []))->count();
    }

    /**
     * Execute the query as a "select" statement.
     *
     * @param  array  $columns
     * @return \Illuminate\Support\Collection
     */
    public static function get($columns = ['*'])
    {
        $plans = collect(Config::get('subscriptions.subscriptions', []));

        return $plans->map(function ($plan, $key) {
            $model = new static();
            $model->exists = true;
            $model->id = $key;
            $model->fill($plan);
            return $model;
        });
    }

    /**
     * Paginate the given query.
     *
     * @param  int  $perPage
     * @param  array  $columns
     * @param  string  $pageName
     * @param  int|null  $page
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public static function paginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null)
    {
        $plans = collect(Config::get('subscriptions.subscriptions', []));

        $page = $page ?: LengthAwarePaginator::resolveCurrentPage($pageName);
        $perPage = $perPage ?: 10;

        $items = $plans->forPage($page, $perPage)->map(function ($plan, $key) {
            $model = new static();
            $model->exists = true;
            $model->id = $key;
            $model->fill($plan);
            return $model;
        });

        return new LengthAwarePaginator(
            $items,
            $plans->count(),
            $perPage,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        );
    }

    /**
     * Get all subscription plans from the configuration.
     *
     * @return \Illuminate\Support\Collection
     */
    public static function all($columns = ['*'])
    {
        $plans = collect(Config::get('subscriptions.subscriptions', []));

        return $plans->map(function ($plan, $key) {
            $model = new static();
            $model->exists = true;
            $model->id = $key;
            $model->fill($plan);
            return $model;
        });
    }

    /**
     * Find a subscription plan by ID.
     *
     * @param  int  $id
     * @return static|null
     */
    public static function find($id)
    {
        $plans = collect(Config::get('subscriptions.subscriptions', []));

        if (!$plans->has($id)) {
            return null;
        }

        $plan = $plans->get($id);
        $model = new static();
        $model->exists = true;
        $model->id = $id;
        $model->fill($plan);

        return $model;
    }

    /**
     * Create a new subscription plan.
     *
     * @param  array  $attributes
     * @return static
     */
    public static function create(array $attributes = [])
    {
        $plans = collect(Config::get('subscriptions.subscriptions', []));
        $plans->push($attributes);

        Config::set('subscriptions.subscriptions', $plans->toArray());

        $model = new static();
        $model->exists = true;
        $model->id = $plans->keys()->last();
        $model->fill($attributes);

        return $model;
    }

    /**
     * Update the subscription plan.
     *
     * @param  array  $attributes
     * @param  array  $options
     * @return bool
     */
    public function update(array $attributes = [], array $options = [])
    {
        $plans = collect(Config::get('subscriptions.subscriptions', []));

        if (!$plans->has($this->id)) {
            return false;
        }

        $plans->put($this->id, $attributes);
        Config::set('subscriptions.subscriptions', $plans->toArray());

        $this->fill($attributes);

        return true;
    }

    /**
     * Delete the subscription plan.
     *
     * @return bool|null
     */
    public function delete()
    {
        $plans = collect(Config::get('subscriptions.subscriptions', []));

        if (!$plans->has($this->id)) {
            return false;
        }

        $plans->forget($this->id);
        Config::set('subscriptions.subscriptions', $plans->values()->toArray());

        return true;
    }
}
