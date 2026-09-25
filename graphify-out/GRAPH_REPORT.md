# Graph Report - crm-api.lan  (2026-09-25)

## Corpus Check
- cluster-only mode — file stats not available

## Summary
- 179 nodes · 201 edges · 30 communities (12 shown, 18 thin omitted)
- Extraction: 99% EXTRACTED · 1% INFERRED · 0% AMBIGUOUS · INFERRED: 3 edges (avg confidence: 0.85)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `0dd7050c`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- Illuminate\Database\Schema\Blueprint
- composer.json
- UserFactory.php
- DatabaseSeeder.php
- tryitout-5.11.0.js
- bootstrap/app.php
- config
- require-dev
- require
- TestCase
- logging.php
- sanctum.php
- theme-default-5.11.0.js
- console.php
- Illuminate\Support\Facades\Route
- ExampleTest
- artisan
- App\Providers\AppServiceProvider

## God Nodes (most connected - your core abstractions)
1. `require-dev` - 8 edges
2. `require` - 6 edges
3. `executeTryOut()` - 5 edges
4. `config` - 5 edges
5. `UserFactory` - 4 edges
6. `DatabaseSeeder` - 4 edges
7. `TestCase` - 4 edges
8. `psr-4` - 4 edges
9. `scripts` - 4 edges
10. `allow-plugins` - 4 edges

## Surprising Connections (you probably didn't know these)
- `ExampleTest` --inherits--> `TestCase`  [EXTRACTED]
  tests/Feature/ExampleTest.php → tests/TestCase.php

## Import Cycles
- None detected.

## Communities (30 total, 18 thin omitted)

### Community 0 - "Illuminate\Database\Schema\Blueprint"
Cohesion: 0.10
Nodes (18): {closure#1}(), {closure#2}(), {closure#3}(), {closure#1}(), {closure#2}(), {closure#1}(), {closure#2}(), {closure#3}() (+10 more)

### Community 1 - "composer.json"
Cohesion: 0.08
Nodes (24): autoload, autoload-dev, psr-4, psr-4, description, extra, laravel, keywords (+16 more)

### Community 2 - "UserFactory.php"
Cohesion: 0.16
Nodes (7): App\Models\User, UserFactory, Illuminate\Database\Eloquent\Factories\Factory, Illuminate\Support\Facades\Hash, Illuminate\Support\Str, Pdo\Mysql, static

### Community 3 - "DatabaseSeeder.php"
Cohesion: 0.24
Nodes (7): DatabaseSeeder, RbacSeeder, Illuminate\Database\Console\Seeds\WithoutModelEvents, Illuminate\Database\Seeder, Spatie\Permission\DefaultTeamResolver, Spatie\Permission\Models\Permission, Spatie\Permission\Models\Role

### Community 4 - "tryitout-5.11.0.js"
Cohesion: 0.29
Nodes (7): executeTryOut(), getCookie(), handleError(), handleResponse(), hideCodeSamples(), makeAPICall(), addItemToSearchParamsObject()

### Community 5 - "bootstrap/app.php"
Cohesion: 0.33
Nodes (6): {closure#2}(), {closure#3}(), Illuminate\Auth\AuthenticationException, Illuminate\Foundation\Application, Illuminate\Foundation\Configuration\Exceptions, Illuminate\Http\Request

### Community 6 - "config"
Cohesion: 0.25
Nodes (8): pestphp/pest-plugin, php-http/discovery, wikimedia/composer-merge-plugin, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 7 - "require-dev"
Cohesion: 0.25
Nodes (8): require-dev, fakerphp/faker, laravel/pail, laravel/pint, laravel/sail, mockery/mockery, nunomaduro/collision, phpunit/phpunit

### Community 8 - "require"
Cohesion: 0.33
Nodes (6): require, laravel/framework, laravel/reverb, laravel/tinker, php, spine/laravel-spine

### Community 9 - "TestCase"
Cohesion: 0.47
Nodes (3): Illuminate\Foundation\Testing\TestCase, ExampleTest, TestCase

### Community 10 - "logging.php"
Cohesion: 0.40
Nodes (4): Monolog\Handler\NullHandler, Monolog\Handler\StreamHandler, Monolog\Handler\SyslogUdpHandler, Monolog\Processor\PsrLogMessageProcessor

### Community 11 - "sanctum.php"
Cohesion: 0.40
Nodes (4): Illuminate\Cookie\Middleware\EncryptCookies, Illuminate\Foundation\Http\Middleware\ValidateCsrfToken, Laravel\Sanctum\Http\Middleware\AuthenticateSession, Laravel\Sanctum\Sanctum

## Knowledge Gaps
- **35 isolated node(s):** `description`, `keywords`, `dont-discover`, `license`, `minimum-stability` (+30 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 91 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **18 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `require-dev` connect `require-dev` to `composer.json`?**
  _High betweenness centrality (0.019) - this node is a cross-community bridge._
- **Why does `config` connect `config` to `composer.json`?**
  _High betweenness centrality (0.018) - this node is a cross-community bridge._
- **Why does `require` connect `require` to `composer.json`?**
  _High betweenness centrality (0.014) - this node is a cross-community bridge._
- **What connects `description`, `keywords`, `dont-discover` to the rest of the system?**
  _35 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Illuminate\Database\Schema\Blueprint` be split into smaller, more focused modules?**
  _Cohesion score 0.09523809523809523 - nodes in this community are weakly interconnected._
- **Should `composer.json` be split into smaller, more focused modules?**
  _Cohesion score 0.08 - nodes in this community are weakly interconnected._