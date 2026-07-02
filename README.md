# mmae/apiresponse

Standard JSON API response envelope for Laravel apps. Every response — success or failure — comes back in the same shape, so front-end clients parse one format.

## Install

```bash
composer require mmae/apiresponse
```

Service provider auto-registers via package discovery (`MMAE\ApiResponse\MMAEApiResponseServiceProvider`).

## Response envelope

```json
{
  "success": true,
  "data": {},
  "errors": {},
  "message": "",
  "token": ""
}
```

## `HasApiResponse` trait

`MMAE\ApiResponse\Traits\HasApiResponse` — add to any controller (or class) that needs to emit the envelope.

```php
use MMAE\ApiResponse\Traits\HasApiResponse;

class UserController extends Controller
{
    use HasApiResponse;

    public function show(User $user)
    {
        return $this->successResponse(UserResource::make($user));
    }
}
```

Methods (all private, all return `JsonResponse`):

| Method | Purpose | Status default |
|---|---|---|
| `successResponse($data, $statusCode = 200)` | success + data | 200 |
| `successResponseWithToken($data, $token, $message = '', $statusCode = 200)` | success + data + token (e.g. login) | 200 |
| `successMessageResponse($message, $statusCode = 200)` | success + message only, no data | 200 |
| `createdResponse($data, $message = '', $statusCode = 201)` | success + data, resource created | 201 |
| `registeredSuccessfullyResponse($data, $token, $message = '', $statusCode = 201)` | success + data + token, user registered | 201 |
| `updatedResponse($data, $message = '', $statusCode = 200)` | success + data, resource updated | 200 |
| `deletedResponse($message = '', $statusCode = 200)` | success + message only, resource deleted, no data | 200 |
| `failedResponse($errors, $message, $statusCode = null)` | failure + errors | `Response::$FAILED_STATUS` (400) |
| `failedMessageResponse($message, $statusCode = null)` | failure + message only | `Response::$FAILED_STATUS` (400) |

`$data` accepts `array`, `Collection`, `JsonResource`, `LengthAwarePaginator`, or `Model`. All methods funnel through the private `makeResponse()`.

See [`examples/UserController.md`](examples/UserController.md) for a full CRUD + registration controller with real captured responses for every scenario (success, validation failure, not found, unhandled/broken code — `app.debug` on and off).

### Debugging: `dumpAndDie()`

`HasApiResponse::dumpAndDie(JsonResponse $response)` — private helper, pretty-prints a `JsonResponse` envelope to stdout and halts execution (`exit`). API-only: no HTML VarDumper output, just the JSON. Not currently called by any of the public response methods above — invoke it directly on a built response when you need to inspect the exact envelope during development.

### Rendering exceptions: `exceptionResponse()` and per-exception builders

`HasApiResponse::exceptionResponse(Throwable $exception, ?Request $request = null): JsonResponse` — renders any caught `Throwable` into the envelope. Exceptions implementing `MMAE\ApiResponse\Contracts\ExceptionContract` (the 4 exceptions below) render themselves; anything else is wrapped in `GeneralErrorException` (original preserved as `$previous`, subject to the [debug mode](#debug-mode-raw-message--trace) rules) and reported to the app's exception handler via `report()`. `$request` defaults to the current request when omitted.

```php
public function show(string $id)
{
    try {
        return $this->successResponse(UserResource::make(User::findOrFail($id)));
    } catch (Throwable $e) {
        return $this->exceptionResponse($e);
    }
}
```

Only the fallback `GeneralErrorException` path is reported — `ModelNotFoundException`, `RouteNotFoundException`, `ValidationException`, and any custom `ExceptionContract` exception are expected/known conditions and aren't logged.

One dedicated builder method per envelope exception is also available — construct and render directly without `throw`/`catch`:

| Method | Builds |
|---|---|
| `generalErrorExceptionResponse($request, ?$message, $statusCode = 500, ?$previous)` | `GeneralErrorException` |
| `modelNotFoundExceptionResponse($request, ?$message, $statusCode = 404)` | `ModelNotFoundException` |
| `routeNotFoundExceptionResponse($request, ?$message, $statusCode = 404)` | `RouteNotFoundException` |
| `validationExceptionResponse($request, $errors, ?$message, $statusCode = 422)` | `ValidationException` |

```php
public function show(Request $request, string $id)
{
    $user = User::find($id);

    if (! $user) {
        return $this->modelNotFoundExceptionResponse($request);
    }

    return $this->successResponse(UserResource::make($user));
}
```

### Debug traces: `withDebugTrace()`

`HasApiResponse::withDebugTrace(JsonResponse $response, Throwable $source): JsonResponse` — protected helper, appends a `debug` block (`exception`, `file`, `line`, `trace`) to an already-built envelope response. Frame `args` are stripped from the trace (may carry sensitive values like passwords/tokens). Used internally by the 4 envelope exceptions (`ValidationException`, `ModelNotFoundException`, `RouteNotFoundException`, `GeneralErrorException`) when rendering with a `$previous` exception and `app.debug` is `true` — see [Debug mode: raw message + trace](#debug-mode-raw-message--trace) below. Can also be called directly if you build custom exception rendering.

## `ApiRequest`

`MMAE\ApiResponse\Request\ApiRequest` — `FormRequest` base class. Overrides `failedValidation()` to return `failedResponse()` (422) instead of Laravel's default redirect.

```php
use MMAE\ApiResponse\Request\ApiRequest;

class StoreUserRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
        ];
    }
}
```

Override defaults per-request by declaring properties:

```php
class StoreUserRequest extends ApiRequest
{
    protected ?string $message = 'Please fix the errors below';
    protected ?int $statusCode = 422;
    // ...
}
```

## Config defaults

`MMAE\ApiResponse\Configurations\Response` — static properties, override directly if needed:

- `$VALIDATION_FAILED_STATUS = 422`
- `$FAILED_STATUS = 400`
- `$VALIDATION_FAILED_MESSAGE = null` — `null` uses the translated `apiresponse::messages.validation_failed` line; set a string to override.

Static props, not config-file-backed. Set them once app boots (e.g. `AppServiceProvider::boot()`) to change defaults app-wide:

```php
use MMAE\ApiResponse\Configurations\Response;

public function boot(): void
{
    Response::$FAILED_STATUS = 422;
    Response::$VALIDATION_FAILED_MESSAGE = 'The given data was invalid.';
}
```

Affects `failedResponse()` / `failedMessageResponse()` (via `$FAILED_STATUS`) and `ApiRequest::failedValidation()` (via `$VALIDATION_FAILED_STATUS` / `$VALIDATION_FAILED_MESSAGE`) whenever caller doesn't pass explicit `$statusCode`/`$message`.

## Exceptions

`MMAE\ApiResponse\Exceptions\*` — throwable exceptions that render themselves as the standard envelope. Each implements `MMAE\ApiResponse\Contracts\ExceptionContract` (`render(Request): JsonResponse`), so throwing one anywhere in the app produces the envelope automatically — no extra wiring required. See [Rendering exceptions](#rendering-exceptions-exceptionresponse-and-per-exception-builders) above for the `HasApiResponse` helpers that build/render these directly.

| Exception | Signature | Default status | Default message key |
|---|---|---|---|
| `ValidationException` | `(array\|Collection $errors, ?string $message = null, int $statusCode = 422, ?Throwable $previous = null)` | 422 | `apiresponse::messages.validation_failed` |
| `ModelNotFoundException` | `(?string $message = null, int $statusCode = 404, ?Throwable $previous = null)` | 404 | `apiresponse::messages.resource_not_found` |
| `RouteNotFoundException` | `(?string $message = null, int $statusCode = 404, ?Throwable $previous = null)` | 404 | `apiresponse::messages.route_not_found` |
| `GeneralErrorException` | `(?string $message = null, int $statusCode = 500, ?Throwable $previous = null)` | 500 | `apiresponse::messages.something_went_wrong` |

Pass `null` (or omit) `$message` to use the translated default; pass a string to override it for that instance — an explicit message always wins, regardless of `app.debug`. Messages are resolved at `render()` time, using whichever locale is active when the response is built — not the locale at throw time.

### Mapping Laravel's own exceptions

`Response::handleExceptions(Exceptions $exceptions)` maps Laravel's built-in exceptions to the matching envelope exception, for JSON requests only (non-JSON requests fall through to Laravel's default handling). Wire it in `bootstrap/app.php`:

```php
use MMAE\ApiResponse\Configurations\Response;

->withExceptions(function (Exceptions $exceptions) {
    Response::handleExceptions($exceptions);
})
```

Handles: `Illuminate\Validation\ValidationException`, `Illuminate\Database\Eloquent\ModelNotFoundException`, unmatched routes (`NotFoundHttpException`), and any other `Throwable` as a `GeneralErrorException`.

### Debug mode: raw message + trace

When an envelope exception is auto-constructed from a real caught exception (i.e. via `Response::handleExceptions`, which always passes the original as `$previous`), `render()` behaves differently based on `config('app.debug')`:

- **`app.debug = true`** — `message` is the original exception's raw `getMessage()`, and a `debug` block is added to the envelope: `{ "exception": "...", "file": "...", "line": ..., "trace": [...] }`. Frame `args` are stripped from every trace entry (they can carry sensitive values like passwords or tokens).
- **`app.debug = false`** — `message` is the translated default (or your override), and no `debug` key is present at all.

This only applies to exceptions carrying a `$previous` (i.e. mapped by `handleExceptions`). If you throw one of these exceptions directly in app code with an explicit `$message`, that message is shown as-is regardless of `app.debug` — it's an intentional, developer-authored message, not something to hide.

Unrecognized throwables passed to `exceptionResponse()` are also reported to the app's exception handler via `report()` before being wrapped — see [Rendering exceptions](#rendering-exceptions-exceptionresponse-and-per-exception-builders) above.

## Translations

Default messages are translated via `apiresponse::messages.*` (English + Arabic shipped). Laravel resolves the active locale automatically; override a locale's strings by publishing:

```bash
php artisan vendor:publish --tag=apiresponse-lang
```

This copies the lang files to `lang/vendor/apiresponse/{locale}/messages.php` in the host app, where they take priority over the package's own.

## Artisan command

```bash
php artisan make:request-api {name}
```

Generates a request class extending `ApiRequest` into `App\Http\Requests\Api`, using `src/stubs/request.stub`.

## Testing & tooling

Package tests live in `src/tests` (Testbench, not top-level `tests/`), written in Pest. `src/tests/Pest.php` binds `TestCase` to `Unit`/`Feature`.

```bash
composer test        # vendor/bin/pest --test-directory=src/tests
composer analyse     # vendor/bin/phpstan analyse (level: max, via larastan)
composer format      # vendor/bin/pint
composer refactor    # vendor/bin/rector process (src/, PHP 8.4 + Laravel 13 rule sets)
```

`phpstan.neon.dist` and `rector.php` both scope to `src/` (skip `src/stubs` and `src/tests`).
