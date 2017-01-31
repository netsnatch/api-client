# Base API Client

[![Latest Stable Version](https://poser.pugx.org/torann/api-client/v/stable.png)](https://packagist.org/packages/torann/api-client)
[![Total Downloads](https://poser.pugx.org/torann/api-client/downloads.png)](https://packagist.org/packages/torann/api-client)
[![Patreon donate button](https://img.shields.io/badge/patreon-donate-yellow.svg)](https://www.patreon.com/torann)
[![Donate weekly to this project using Gratipay](https://img.shields.io/badge/gratipay-donate-yellow.svg)](https://gratipay.com/~torann)
[![Donate to this project using Flattr](https://img.shields.io/badge/flattr-donate-yellow.svg)](https://flattr.com/profile/torann)
[![Donate to this project using Paypal](https://img.shields.io/badge/Donate-PayPal-green.svg)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=4CJA2A97NPYVU)

A reusable base API client for use with remote services.

- [Base API Client on Packagist](https://packagist.org/packages/torann/api-client)
- [Base API Client on GitHub](https://github.com/Torann/api-client)

## Installation

Install using composer:

```
$ composer require torann/api-client
```

## Creating Clients

Once installed we need to create some clients. First we need to extend the `\BaseApiClient\Client` class and set endpoint namespace.

### Client

```php
<?php

namespace App\BlogApi;

class Client extends \BaseApiClient\Client
{
    /**
     * Namespace for the endpoints
     *
     * @var string
     */
    protected $endpointNamespace = 'App\BlogApi\Endpoints';
}
```

The `$endpointNamespace` variable is the prefix for the namespace of our endpoints.

### Endpoints

From the endpoint we make our API calls and return the models.

```php
<?php

namespace App\BlogApi\Endpoints;

use App\BlogApi\Models\Post;

use BaseApiClient\Endpoint;
use BaseApiClient\Models\Collection;

class Posts extends Endpoint
{
    /**
     * Get pages for the provided website.
     *
     * @param  array $params
     *
     * @return Collection
     * @throws \BaseApiClient\Exceptions\ApiException
     */
    public function index(array $params = [])
    {
        $response = $this->request->get('posts', $params);

        return new Collection($response, 'Post');
    }

    /**
     * Create a new post.
     *
     * @param  array $params
     *
     * @return Post
     * @throws \BaseApiClient\Exceptions\ApiException
     */
    public function create(array $params)
    {
        $response = $this->request->post('posts', $params);

        return new Post($response);
    }

    /**
     * Delete the provided post.
     *
     * @param  string $id
     *
     * @return bool
     * @throws \BaseApiClient\Exceptions\ApiException
     */
    public function delete($id)
    {
        $response = $this->request->delete(sprintf('posts/%s', $id));

        return $response->getResponseCode() === 200;
    }
}
```

### Models

```php
<?php

namespace App\BlogApi\Models;

use BaseApiClient\Models\Model;

class Post extends Model
{
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'publish_at',
    ];
}
```

## Registering Our Clients

```php
<?php

namespace App\Providers;

use App\BlogApi\Client;
use App\AnotherApi\Client;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerBlogService();
        $this->registerAnotherService();
    }

    /**
     * Register blog manager services.
     *
     * @return void
     */
    public function registerBlogService()
    {
        $this->app->bind(Client::class, function () {
            return new Client([
                'domain' => 'http://some.fancy.ip/',
                'secret' => env('BLOG_MANAGER_API_SECRET'),
            ]);
        });
    }

    /**
     * Register blog manager services.
     *
     * @return void
     */
    public function registerAnotherService()
    {
        $this->app->bind(Client::class, function () {
            return new Client([
                'domain' => 'http://some.fancy.ip/',
                'secret' => env('ANOTHER_API_SECRET'),
            ]);
        });
    }
}
```

## Calling an Endpoint

Below is an example of using our `\App\BlogApi\Client` inside of a controller.

```php
<?php

namespace App\Http\Controllers;

use App\BlogApi\Client;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    /**
     * Blog manager client instance.
     *
     * @var \App\BlogApi\Client
     */
    protected $client;

    /**
     * Initializer constructor.
     *
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        parent::__construct();

        $this->client = $client;
    }

    /**
     * Display the specified resource.
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $posts = $this->client->posts->index($request->only('page'));

        return view('posts.index')->with([
            'posts' => $posts->paginate()
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  Request $request
     * @param  string  $slug
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $slug)
    {
        $post = $this->client->posts->find($slug);

        return view('posts.show')->with([
            'post' => $post
        ]);
    }
}
```

## Change Log

**0.2.0**

- Add support for Laravel 5.4

**0.1.4**

- Return null for empty values

**0.1.3**

- Add support for Laravel 5.3

**0.1.2**

- Remove trailing slash

**0.1.1**

- Bug fixes

**0.1.0**

- First release