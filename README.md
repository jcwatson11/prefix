# FH QueryBuilder (package: fh/laravel-api-query-builder)

## A simple, unit testable translation device between an API query string and an Eloquent builder

It's simple. QueryBuilder takes an Illuminate\Http\Request object (or FormRequest) and converts the URI string like so:

```http
/api/v1/companies/48/contacts?likeFirstName=Johnny&betweenStatus[]=4&betweenStatus[]=8
```
Becomes
```php
<?php
$model->where('ParentId','=',48)
      ->where('FirstName','LIKE','%Johnny%')
      ->whereBetween('Status',4,8);
```

## Features
- Supports nested relationships, intrinsically limiting results to a parent object's set of owned children according to relationships defined in your Eloquent models.
- Supports all common SQL comparators:
  - IS NULL
  - IS NOT NULL
  - WHERE
  - OR WHERE
  - WHERE LIKE
  - OR WHERE LIKE
  - BETWEEN
  - IN ()
  - NOT IN ()
  - and all of the standard operators: =, >, <, <=, >=.
- Supports order by and group by.
- Supports ordering a parent by a child relation as in: SELECT o.* FROM organizations o JOIN projects p ON o.OrgId = p.OrgId ORDER BY p.Date
- Supports laravel scopes.
- Supports searching of sub-relations with relationname.FieldName=value after any query prefix.
- Supports eager loading of relationships: $model->with(...)
- Supports multiple pagination schemas, including limit/offset, and page=X.
- Pagination parameter names can be customized.
- Fully unit tested with extensive test cases for each scenario.
- Extensible. Extend Fh\QueryBuilder to add your own new operators and clause types, or add custom behavior to existing ones.
- Open Source. MIT License.

## Installation
```bash
$> composer require fh/laravel-prefix-api-querybuilder
```

## Usage

### Configuration

Below is the configuration file that should be stored in your application's config/ folder as file name: fh-laravel-api-query-builder.php. Read the comments for details about each configuration variable and what it does.

```php
<?php
return [
    /**
     * This is the route prefix that your API might use
     * before starting the route to any single resource.
     */
    'baseUri' => '/api/v1/',

    // Default page limit while doing pagination.
    'defaultLimit' => 10,

    /**
     * Model namespace
     *
     * This is the namespace that your models will be prefixed with
     * when they are found in the route to model mapping that you
     * provide to the QueryBuilder when you instantiate it.
     *
     * You can leave this blank, and simply provide the full class path
     * of your models directly in the mapping if you want to.
     */
    'modelNamespace' => 'Fh\QueryBuilder',

    /**
     * Paging Style
     * Two different types of paging styles are supported:
     * 'page=' and 'limit/offset'
     *
     * The 'page=' style is like laravel's default paging
     * style, except that you can control the name of the paging
     * parameter. This is helpful for backward compatability with
     * older API signatures.
     *
     * The 'limit/offset' style is just that. Instead of specifying
     * a page number and a number of results per page, you specify
     * a limit and an offset, much like what MySQL developers are
     * familiar with in a SQL query.
     */
    'pagingStyle' => 'limit/offset',

    /**
     * Name of offset/limit/page parameters used as described
     * above 'pagingStyle'.
     */
    'offsetParameterName' => 'offset',
    'limitParameterName'  => 'limit',
    'pageParameterName'   => 'page'
];
```
### Service Provider Setup

Add the following service provider to your config/app.php file:
```php
Fh\QueryBuilder\FhApiQueryBuilderServiceProvider::class
```

### Implementation

The query builder is intended to be used inside a Laravel controller which defines two things:
- a route to model mapping, so that the route name and model name can be separate from each other
- an instance of the Illuminate\Http\Request object (or FormRequest, since that is a sub class of the former).

Below is an example controller to help you get started. Again, the comments in the code are helpful to read.

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use Illuminate\Http\Request;
use Fh\QueryBuilder\QueryBuilder;

/**
 * ApiController for routing all queries to a database.
 * This follows the laravel resource controller interface.
 * Use this as a base class for other resource controllers
 * So you can customize other parts of this resource controller.
 */
class RestApiController extends Controller {

    protected $routeMap = [
        'organizations'          => 'Organization',
        'organizations.contacts' => 'Organization.contacts'
    ];

    public function index(Request $request) {
        // Instantiate the builder
        $qb = new QueryBuilder($this->routeMap,$request);
        // Apply all URI parameters
        $qb->build();

        // If you're using limit/offset paging, you can get the total
        // number of records manually.
        $iTotalRecordCount = $qb->getCount();
        $results = $qb->paginate();

        // Otherwise, you can use Laravel's LengthAwarePaginator
        // that comes with Laravel 5
        $paginator = $qb->paginate();
    }

}
```

## Parameter Prefix Reference
| Parameter Prefix        | Builder Method Called | Default Operator | Notes |
|:------------------------|:----------------------|:-----------------|:------|
| isnull      | whereNull | NA |
| isnotnull   | whereNotNull | NA |
| orwhere     | orWhere | = |
| where       | where | = |
| orderby     | orderBy | NA |
| groupby     | groupBy | NA |
| between     | whereBetween | NA |
| notinarray  | whereNotIn | NA |
| inarray     | whereIn | NA |
| like        | where | LIKE | Values passed in are automatically wrapped with %% for convenience. So you don't have to include the percent sign on the query string.
| orlike      | orWhere | LIKE | Values passed in are automatically wrapped with %% for convenience. So you don't have to include the percent sign on the query string.
| greaterthan | where | > |
| greaterthanorequalto | where | >= |
| lessthan    | where | < |
| lessthanorequalto | where | <= |

## Examples
### Simple query with no nested relationships
```http
/api/v1/organizations?with[]=contacts&inarrayCountry[]=Guatemala&inarrayCountry[]=USA
```
Becomes
```php
$builder = $organization->with('contacts')
               ->whereIn('Country',['Guatemala','USA']);
```
### Simple Example of a Nested Relationship
```http
/api/v1/organizations/2061/contacts
```
Becomes
```php
$org = $organization->find(2061);
$builder = $org->contacts();
```
### Nested relationship with the works
```http
/api/v1/letters/23/photos?with[]=translations&with[]=original&isnullCaption&isnotnullOriginalId&likeFirstName=Jon&filterAppropriateForPrint&lessthanTestId=25
```
Becomes
```php
$l = $letter->find(23);
$builder = $l->photos()
      ->appropriateForPrint() // This is a scope call
      ->with(['translations','original'])
      ->whereNull('Caption')
      ->whereNotNull('OriginalId')
      ->where('FirstName','LIKE','%Jon%')
      ->where('TestId','<',25)
```
### Return a set of records filtered by a value in a child relation
```http
/api/v1/letters/23/organizations?likecontacts.FirstName=Jon
```
Becomes
```php
$builder = $organization->whereHas('contacts', function($q) {
    $q->where('FirstName','LIKE','%Jon%');
});
```
### Return a single record by ID with some eager-loaded relations
```http
/api/v1/organizations/23?with[]=contacts&with[]=notes
```
Becomes
```php
$builder = $letter->with('contacts','notes')
                  ->where($primaryKey,'=',23);
```

### Retrieve a list of results ordered by a certain field
```http
/api/v1/organizations/23?orderbyLastName
```
Becomes
```php
$builder = $letter->orderBy('LastName');
```

### Retrieve a list of results ordered by a relation field.
```http
/api/v1/organizations/23?sortbychildcontact.CreationDate=asc
```
Becomes
```php
$builder = $letter->join("Contact AS relTable","relTable.LetterId",'=',"Letter.LetterId")
                  ->orderBy("Contact.CreationDate",'asc')
                  ->select("Letter.*");
```

# Contributing
I'm happy to consider any pull requests you want to submit. Constructive comments are also always welcome.

# License
MIT License
