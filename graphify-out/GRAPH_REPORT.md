# Graph Report - crm-api.lan  (2026-09-26)

## Corpus Check
- 39 files · ~12,971 words
- Verdict: corpus is large enough that graph structure adds value.
- Unclassified: 19 file(s) not represented in the graph (top: (none) 15, .css 2, .xml 1)

## Summary
- 204 nodes · 212 edges · 34 communities (15 shown, 19 thin omitted)
- Extraction: 99% EXTRACTED · 1% INFERRED · 0% AMBIGUOUS · INFERRED: 3 edges (avg confidence: 0.85)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `cde4fd18`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- Illuminate\Database\Schema\Blueprint
- composer.json
- Illuminate\Support\Str
- DatabaseSeeder.php
- tryitout-5.11.0.js
- bootstrap/app.php
- config
- require-dev
- AGENTS.md — crm-api.lan (crm-api)
- TestCase
- logging.php
- sanctum.php
- theme-default-5.11.0.js
- console.php
- Illuminate\Support\Facades\Route
- ExampleTest
- artisan
- App\Providers\AppServiceProvider
- CRM API — Backend API
- Backend API Endpoints
- psr-4
- permission.php

## God Nodes (most connected - your core abstractions)
1. `AGENTS.md — crm-api.lan (crm-api)` - 15 edges
2. `CRM API — Backend API` - 9 edges
3. `require-dev` - 8 edges
4. `Backend API Endpoints` - 7 edges
5. `API Endpoints` - 7 edges
6. `require` - 6 edges
7. `config` - 5 edges
8. `executeTryOut()` - 5 edges
9. `psr-4` - 4 edges
10. `scripts` - 4 edges

## Surprising Connections (you probably didn't know these)
- `ExampleTest` --inherits--> `TestCase`  [EXTRACTED]
  tests/Feature/ExampleTest.php → tests/TestCase.php

## Import Cycles
- None detected.

## Communities (34 total, 19 thin omitted)

### Community 0 - "Illuminate\Database\Schema\Blueprint"
Cohesion: 0.11
Nodes (17): {closure#1}(), {closure#2}(), {closure#3}(), {closure#1}(), {closure#2}(), {closure#1}(), {closure#2}(), {closure#3}() (+9 more)

### Community 1 - "composer.json"
Cohesion: 0.08
Nodes (25): autoload-dev, psr-4, description, extra, laravel, keywords, dont-discover, license (+17 more)

### Community 3 - "DatabaseSeeder.php"
Cohesion: 0.50
Nodes (3): DatabaseSeeder, Illuminate\Database\Seeder, Spine\Database\Seeders\AdminUserSeeder

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

### Community 8 - "AGENTS.md — crm-api.lan (crm-api)"
Cohesion: 0.11
Nodes (17): AGENTS.md — crm-api.lan (crm-api), Aplikasi (`/www/wwwroot/crm-api.lan/`), Environment, Frontend (crm-web), Frontend (`/www/wwwroot/crm-web/`), Git, Infrastruktur, Komando Umum (+9 more)

### Community 9 - "TestCase"
Cohesion: 0.47
Nodes (3): Illuminate\Foundation\Testing\TestCase, ExampleTest, TestCase

### Community 10 - "logging.php"
Cohesion: 0.40
Nodes (4): Monolog\Handler\NullHandler, Monolog\Handler\StreamHandler, Monolog\Handler\SyslogUdpHandler, Monolog\Processor\PsrLogMessageProcessor

### Community 11 - "sanctum.php"
Cohesion: 0.40
Nodes (4): Illuminate\Cookie\Middleware\EncryptCookies, Illuminate\Foundation\Http\Middleware\ValidateCsrfToken, Laravel\Sanctum\Http\Middleware\AuthenticateSession, Laravel\Sanctum\Sanctum

### Community 30 - "CRM API — Backend API"
Cohesion: 0.12
Nodes (15): API Endpoints, Auth (Authenticated), Auth (Public), CRM API — Backend API, Database, GitHub, Installation, Menus (+7 more)

### Community 31 - "Backend API Endpoints"
Cohesion: 0.29
Nodes (7): Auth (Authenticated), Auth (Public), Backend API Endpoints, Cron, Menu Registry, Package APIs (106 routes), Public Content

### Community 32 - "psr-4"
Cohesion: 0.40
Nodes (5): autoload, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\

### Community 33 - "permission.php"
Cohesion: 0.50
Nodes (3): Spatie\Permission\DefaultTeamResolver, Spatie\Permission\Models\Permission, Spatie\Permission\Models\Role

## Knowledge Gaps
- **69 isolated node(s):** `$schema`, `name`, `type`, `description`, `keywords` (+64 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 126 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **19 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `require-dev` connect `require-dev` to `composer.json`?**
  _High betweenness centrality (0.014) - this node is a cross-community bridge._
- **Why does `config` connect `config` to `composer.json`?**
  _High betweenness centrality (0.014) - this node is a cross-community bridge._
- **Why does `AGENTS.md — crm-api.lan (crm-api)` connect `AGENTS.md — crm-api.lan (crm-api)` to `Backend API Endpoints`?**
  _High betweenness centrality (0.012) - this node is a cross-community bridge._
- **What connects `$schema`, `name`, `type` to the rest of the system?**
  _69 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Illuminate\Database\Schema\Blueprint` be split into smaller, more focused modules?**
  _Cohesion score 0.10685483870967742 - nodes in this community are weakly interconnected._
- **Should `composer.json` be split into smaller, more focused modules?**
  _Cohesion score 0.07692307692307693 - nodes in this community are weakly interconnected._
- **Should `AGENTS.md — crm-api.lan (crm-api)` be split into smaller, more focused modules?**
  _Cohesion score 0.1111111111111111 - nodes in this community are weakly interconnected._