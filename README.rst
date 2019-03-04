Router
######

HTTP request router.

.. image:: https://travis-ci.com/kuria/router.svg?branch=master
   :target: https://travis-ci.com/kuria/router

.. contents::
   :depth: 3


Features
********

- defining routes using OO builders
- matching request attributes (method, scheme, host, port, path)
- regex-driven host and path patterns
- generating URLs


Requirements
************

- PHP 7.1+


Usage
*****

Routing incoming requests
=========================

Simple ``PATH_INFO`` routing
----------------------------

Simple routing using ``$_SERVER['PATH_INFO']`` and hardcoded context information.

Example URL: ``http://localhost/index.php/page/index``

.. code:: php

   <?php

   use Kuria\Router\Context;
   use Kuria\Router\Result\Match;
   use Kuria\Router\Result\MethodNotAllowed;
   use Kuria\Router\Route\RouteCollector;
   use Kuria\Router\Router;

   // create router
   $router = new Router();

   // define default context
   $router->setDefaultContext(new Context(
       'http',         // scheme
       'localhost',    // host
       80,             // port
       '/index.php'    // base path
   ));

   // define routes
   $router->defineRoutes(function (RouteCollector $c) {
       $c->get('index')->path('/');
       $c->get('page')->path('/page/{name}');

       $c->addGroup('user_', '/user', function (RouteCollector $c) {
           $c->add('register')->methods(['GET', 'POST'])->path('/register');
           $c->add('login')->methods(['GET', 'POST'])->path('/login');
           $c->get('logout')->path('/logout');
           $c->get('profile')->path('/profile/{username}');
       });
   });

   // match current request
   $path = rawurldecode($_SERVER['PATH_INFO'] ?? '/');
   $result = $router->matchPath($_SERVER['REQUEST_METHOD'], $path);

   // handle the result
   if ($result instanceof Match) {
       // success
       // do something with the matched route and parameters
       echo 'Matched path: ', $result->getSubject()->path, "\n";
       echo 'Matched route: ', $result->getRoute()->getName(), "\n";
       echo 'Parameters: ', print_r($result->getParameters(), true), "\n";
   } elseif ($result instanceof MethodNotAllowed) {
       // method not allowed
       http_response_code(405);
       header('Allow: ' . implode(', ', $result->getAllowedMethods()));
       echo "Method not allowed :(\n";
   } else {
       // not found
       http_response_code(404);
       echo "Not found :(\n";
   }


Dynamic routing using kuria/request-info
----------------------------------------

Context and path info can be auto-detected using the `kuria/request-info <https://github.com/kuria/request-info>`_ library.

It supports both simple path info and rewritten URLs and can extract information from trusted proxy headers.

.. code:: php

   <?php

   use Kuria\RequestInfo\RequestInfo;
   use Kuria\Router\Context;
   use Kuria\Router\Result\Match;
   use Kuria\Router\Result\MethodNotAllowed;
   use Kuria\Router\Route\RouteCollector;
   use Kuria\Router\Router;

   // create router
   $router = new Router();

   // define default context
   $router->setDefaultContext(new Context(
       RequestInfo::getScheme(),
       RequestInfo::getHost(),
       RequestInfo::getPort(),
       RequestInfo::getBasePath()
   ));

   // define routes
   $router->defineRoutes(function (RouteCollector $c) {
       $c->get('index')->path('/');
       $c->get('page')->path('/page/{name}');

       $c->addGroup('user_', '/user', function (RouteCollector $c) {
           $c->add('register')->methods(['GET', 'POST'])->path('/register');
           $c->add('login')->methods(['GET', 'POST'])->path('/login');
           $c->get('logout')->path('/logout');
           $c->get('profile')->path('/profile/{username}');
       });
   });

   // match current request
   $path = rawurldecode(RequestInfo::getPathInfo());
   $result = $router->matchPath(RequestInfo::getMethod(), $path !== '' ? $path : '/');

   // handle the result
   if ($result instanceof Match) {
       // success
       // do something with the matched route and parameters
       echo 'Matched path: ', $result->getSubject()->path, "\n";
       echo 'Matched route: ', $result->getRoute()->getName(), "\n";
       echo 'Parameters: ', print_r($result->getParameters(), true), "\n";
   } elseif ($result instanceof MethodNotAllowed) {
       // method not allowed
       http_response_code(405);
       header('Allow: ' . implode(', ', $result->getAllowedMethods()));
       echo "Method not allowed :(\n";
   } else {
       // not found
       http_response_code(404);
       echo "Not found :(\n";
   }


Defining routes
===============

``RouteCollector`` provides a convenient interface to define routes.

The easier way to use it is to call ``Router->defineRoutes()`` with a callback
accepting an instance of ``RouteCollector``. The router then takes care of adding
the defined routes.

.. code:: php

   <?php

   use Kuria\Router\Route\RouteCollector;
   use Kuria\Router\Router;

   $router = new Router();

   $router->defineRoutes(function (RouteCollector $c) {
       $c->get('index')->path('/');
       $c->post('login')->path('/login');
       // ...
   });


Route collector API
-------------------

``RouteCollector`` provides methods to create and organize route builders.

The returned ``RouteBuilder`` instances can be used to configure the routes.
See `Route builder API`_.

- ``add($routeName): RouteBuilder`` - add a route
- ``get($routeName): RouteBuilder`` - add a route that matches GET requests
- ``head($routeName): RouteBuilder`` - add a route that matches HEAD requests
- ``post($routeName): RouteBuilder`` - add a route that matches POST requests
- ``put($routeName): RouteBuilder`` - add a route that matches PUT requests
- ``delete($routeName): RouteBuilder`` - add a route that matches DELETE requests
- ``options($routeName): RouteBuilder`` - add a route that matches OPTIONS requests
- ``patch($routeName): RouteBuilder`` - add a route that matches PATCH requests
- ``addVariant($existingRouteName, $newRouteName): RouteBuilder`` - add a variant of an existing route, see `Route variants`_
- ``addGroup($namePrefix, $pathPrefix, $callback): void`` - add a group of routes with common prefixes, see `Route groups`_
- ``hasBuilder($routeName): bool`` - see if a route is defined
- ``getBuilder($routeName): RouteBuilder`` - get builder for the given route
- ``removeBuilder($routeName): void`` - remove route definition
- ``getBuilders(): RouteBuilder[]`` - get all configured builders
- ``getRoutes(): Route[]`` - build routes
- ``clear(): void`` - remove all defined routes


Route variants
^^^^^^^^^^^^^^

To add multiple similar routes, you can define a single route and then use that
definition as a base of new routes by calling ``addVariant()``:

.. code:: php

   <?php

   use Kuria\Router\Route\RouteCollector;
   use Kuria\Router\Router;

   $router = new Router();

   $router->defineRoutes(function (RouteCollector $c) {
       // define a base route
       $c->get('get_row')
           ->path('/{database}/{row}')
           ->defaults(['format' => 'json']);

       // define a variant of the base route
       $c->addVariant('get_row', 'get_row_with_format')
           ->appendPath('.{format}')
           ->requirements(['format' => 'json|xml']);
   });

   // print defined routes
   foreach ($router->getRoutes() as $route) {
       echo $route->getName(), ' :: ', $route->dump(), "\n";
   }

Output:

::

  get_row :: GET /{database}/{row}
  get_row_with_format :: GET /{database}/{row}.{format}


Route groups
^^^^^^^^^^^^

To define several routes that share a common path and name prefix, use ``addGroup()``:

.. code:: php

   <?php

   use Kuria\Router\Route\RouteCollector;
   use Kuria\Router\Router;

   $router = new Router();

   $router->defineRoutes(function (RouteCollector $c) {
       $c->addGroup('user_', '/user', function (RouteCollector $c) {
           $c->add('register')->methods(['GET', 'POST'])->path('/register');
           $c->add('login')->methods(['GET', 'POST'])->path('/login');
           $c->get('logout')->path('/logout');
           $c->get('profile')->path('/profile/{username}');
       });
   });

   // print defined routes
   foreach ($router->getRoutes() as $route) {
       echo $route->getName(), ' :: ', $route->dump(), "\n";
   }

Output:

::

  user_register :: GET|POST /user/register
  user_login :: GET|POST /user/login
  user_logout :: GET /user/logout
  user_profile :: GET /user/profile/{username}


Route builder API
-----------------

``RouteBuilder`` provides a fluent interface to configure a single route.

- ``methods($allowedMethods): self`` - match request methods (must be uppercase, e.g. ``GET``, ``POST``, etc.)
- ``scheme($scheme): self`` - match a scheme (e.g. ``http`` or ``https``)
- ``host($hostPattern): self`` - match host name pattern, see `Route patterns`_
- ``prependHost($hostPatternPrefix): self`` - add a prefix to the host name pattern
- ``appendHost($hostPatternPrefix): self`` - add a suffix to the host name pattern
- ``port($port): self`` - match port
- ``path($pathPattern): self`` - match path pattern, see `Route patterns`_
- ``prependPath($pathPatternPrefix): self`` - add a prefix to the path pattern
- ``appendPath($pathPatternPrefix): self`` - add a suffix to the path pattern
- ``defaults($defaults): self`` - specify default parameters, see `Route defaults`_
- ``attributes($attributes): self`` - specify arbitrary route attributes, see `Route attributes`_
- ``requirements($requirements): self`` - specify parameter requirements, see `Route requirements`_

Example call:

.. code:: php

   <?php

   $router->defineRoutes(function (RouteCollector $c) {
       // $c->add() returns a RouteBuilder
       $c->add('user_profile_page')
           ->methods(['GET', 'POST'])
           ->scheme('https')
           ->host('{username}.example.com')
           ->port(8080)
           ->path('/{page}')
           ->defaults(['page' => 'home'])
           ->requirements(['username' => '\w+', 'page' => '[\w.\-]+']);
   });


Route patterns
--------------

The host and path of a route can contain any number of parameter placeholders.

Placeholder syntax is the following:

::

  {parameterName}

Parameter name can consist of any characters with the exception of ``}``.

These parameters will be available in the matching result. See `Matching routes`_.

.. NOTE::

   Optional pattern parameters are not supported. If you need differently structured
   URLs to match the same resource, define multiple routes accordingly.

   See `Route variants`_.


Route defaults
--------------

A route can contain default parameter values.

These defaults are used when generating URLs (in case one or more parameters haven't been specified).
See `Generating URLs`_.

Default parameters can also be useful when defining multiple routes that point to the
same resource (so the routes are interchangeable).


Route attributes
----------------

A route can contain arbitrary attributes.

The use depends entirely on the application, but it is a good place to store
various metadata, e.g. controller names or handler callables.


Route requirements
------------------

Route requirements are a set of plain regular expressions for each host or path pattern
parameter. See `Route patterns`_.

The regular expressions should not be delimited. They are also anchored automatically, so
they should not contain ``^`` or ``$``.


Default requirements
^^^^^^^^^^^^^^^^^^^^

If no requirement is specified, a default one will be assumed instead, depending on the
type of the pattern:

- host pattern: ``.+``

  - one or more characters of any type

- path pattern: ``[^/]+``

  - one or more characters that are not a forward slash


Caching routes
==============

Building and compiling routes will introduce some overhead into your application.
Luckily, the defined routes can be serialized and stored for later use.

Below is an example of route caching using the `kuria/cache <https://github.com/kuria/cache>`_
library, but you can any other library or code.

.. code:: php

   <?php

   use Kuria\Cache\Cache;
   use Kuria\Cache\Driver\Filesystem\FilesystemDriver;
   use Kuria\Router\Route\RouteCollector;
   use Kuria\Router\Router;

   // example cache
   $cache = new Cache(new FilesystemDriver(__DIR__ . '/cache'));

   // create router
   $router = new Router();

   // attempt to load routes from the cache
   $routes = $cache->get('routes');

   if ($routes === null) {
       // no routes found in cache, define them
       $router->defineRoutes(function (RouteCollector $c) {
           $c->get('index')->path('/');
           $c->get('page')->path('/page/{name}');
       });

       // store defined routes in the cache
       $cache->set('routes', $router->getRoutes());
   } else {
       // use routes from cache
       $router->setRoutes($routes);
   }

.. NOTE::

   Routes that contain unserializable values (such as closures in the attributes)
   cannot be cached.


Matching routes
===============

After routes have been defined, the router can be used to route a request.

See example code in `Routing incoming requests`_.


Using ``Router->match()/matchPath()``
-------------------------------------

Both ``Router->match()`` and ``Router->matchPath()`` return an instance of ``Kuria\Router\Result\Result``,
which may be one of the following:


``Kuria\Router\Result\Match``
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

A route has been matched successfully. The ``Match`` object provides access to the
matched route and parameters.

It us up to the application to do something with this information.

.. code:: php

   <?php

   use Kuria\Router\Result\Match;
   use Kuria\Router\Router;

   $result = $router->matchPath('GET', '/user/profile/bob');

   if ($result instanceof Match) {
       echo 'Matched route is ', $result->getRoute()->getName(), "\n";
       echo 'Matched parameters are: ', json_encode($result->getParameters()), "\n";
   }

Output:

::

  Matched route is user_profile
  Matched parameters are: {"username":"bob"}

.. TIP::

   You can access route attributes at ``$result->getRoute()->getAttributes()``.

   See `Route attributes`_.


``Kuria\Router\Result\MethodNotAllowed``
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

No routes have been matched, but there are routes that would match if the method
was different.

A proper response in this case is HTTP 405 Method Not Allowed, with an ``Allow``
header specifying the allowed methods.


.. code:: php

   <?php

   use Kuria\Router\Result\MethodNotAllowed;

   $result = $router->matchPath('POST', '/user/logout');

   if ($result instanceof MethodNotAllowed) {
       http_response_code(405);
       header('Allow: ' . implode(', ', $result->getAllowedMethods()));
   }


``Kuria\Router\Result\NotFound``
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

No routes have matched.

A proper response in this case is HTTP 404 Not Found.

.. code:: php

   <?php

   use Kuria\Router\Result\NotFound;

   $result = $router->matchPath('GET', '/nonexistent');

   if ($result instanceof NotFound) {
       http_response_code(404);
   }


HEAD to GET fallback
--------------------

To ease compliance with the HTTP specification, if a ``HEAD`` request does not match
any route, a second matching attempt will be made assuming a ``GET`` method instead.

PHP itself supports ``HEAD`` requests and will only respond with headers, so you don't
have to craft additional routes to handle these requests in most cases.


Generating URLs
===============

After routes have been defined, the router can be used to generate URLs.

See `Routing incoming requests`_ for an example of a configured router.


Using ``Router->generate()``
----------------------------

The ``Router->generate()`` method will generate an URL for the given route
and parameters and return an instance of ``Kuria\Url\Url``.

- if no such route exists or the parameters are invalid, an exception will
  be thrown (see `Route requirements`_)
- if some parameters are missing, the configured default values will be used
  instead (see `Route defaults`_)
- any extra parameters (that are not present in the host or path pattern)
  will be added as query parameters instead
- if the scheme, host or port is different from the context, the URL's preferred
  format will be ``Url::ABSOLUTE``; if they are all the same or undefined, it will
  be ``Url::RELATIVE`` (See `Router context`_)

.. code:: php

   <?php

   var_dump(
       $router->generate('user_register')->build(),
       $router->generate('user_profile', ['username' => 'bob', 'extra' => 'example'])->build()
   );

Output:

::

  string(14) "/user/register"
  string(31) "/user/profile/bob?extra=example"

If you wish to get absolute URLs regardless of the context, use ``buildAbsolute()``:

.. code:: php

   <?php

   var_dump(
       $router->generate('index')->buildAbsolute(),
       $router->generate('page', ['name' => 'contact'])->buildAbsolute()
   );

Output:

::

  string(17) "http://localhost/"
  string(29) "http://localhost/page/contact"


Router context
==============

Router context is used to fill in missing information (scheme, host, port, etc.) when generating
URLs or matching paths.

It can be specified in two ways:


Using ``Router->setDefaultContext()``
-------------------------------------

This method defines a default context to be used the none is given.

.. code:: php

   <?php

   use Kuria\Router\Context;

   $router->setDefaultContext(new Context(
       'https',       // scheme
       'example.com', // host
       443,           // port
       ''             // basePath
   ));


Using the ``$context`` parameter
--------------------------------

``Router->matchPath()`` and ``Router->generate()`` accept an optional ``$context`` argument.

If no context is given, the default context will be used instead. If no default context
is specified, an exception will be thrown.
