# Changelog

All notable changes to `mmae/apiresponse` are documented here.
Format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [2.0.0] — BREAKING CHANGE

### Added

- `MMAE\ApiResponse\Exceptions\ValidationException`, `ModelNotFoundException`, `RouteNotFoundException`, `GeneralErrorException` — throwable exceptions that render themselves as the standard envelope.
- `Response::handleExceptions(Exceptions $exceptions)` — maps Laravel's own `ValidationException`, `ModelNotFoundException`, unmatched routes, and any other `Throwable` to the matching envelope exception. Wire it in `bootstrap/app.php`'s `withExceptions()`.
- Translated default exception messages (`apiresponse::messages.*`), English + Arabic shipped, publishable via `php artisan vendor:publish --tag=apiresponse-lang`. Messages resolve at `render()` time using the locale active at render, not at throw time.
- Dev tooling: Laravel Pint (`composer format`), PHPStan level `max` via Larastan (`composer analyse`), Rector + rector-laravel (`composer refactor`).
- `src/tests/Pest.php` — Pest test suite bootstrap.
- `HasApiResponse::dumpAndDie()` — private helper, dumps a `JsonResponse` envelope pretty-printed and halts execution (`exit`), for API-only debugging. Not wired into any public response method yet.
- `HasApiResponse::withDebugTrace()` — appends a `debug` block (`exception`, `file`, `line`, `trace`) to a rendered response. All 4 envelope exceptions now accept a trailing `?Throwable $previous` and use it in `render()`: when `app.debug` is `true` and a `$previous` is set, the response shows the original exception's raw message plus the trace (frame `args` stripped); when `app.debug` is `false`, it shows the translated default with no `debug` key. `Response::handleExceptions()` now always passes the original caught exception as `$previous` (unwrapping `ModelNotFoundException` from Laravel's `NotFoundHttpException` wrapper so the trace points at the real throw site).
- `MMAE\ApiResponse\Contracts\ExceptionContract` — interface (`render(Request): JsonResponse`) implemented by all 4 envelope exceptions.
- `HasApiResponse::exceptionResponse(Throwable $exception, ?Request $request = null)` — renders any caught `Throwable` into the envelope: exceptions implementing `ExceptionContract` render themselves, anything else is wrapped in `GeneralErrorException` (original preserved as `$previous`) and reported to the app's exception handler via `report()`. `$request` defaults to the current request when omitted.
- `HasApiResponse::generalErrorExceptionResponse()`, `modelNotFoundExceptionResponse()`, `routeNotFoundExceptionResponse()`, `validationExceptionResponse()` — one builder method per envelope exception; each constructs and renders the matching exception directly (mirrors the exceptions' own constructor signatures).
- `HasApiResponse::createdResponse($data, $message = '', $statusCode = 201)` — success envelope for resource creation, defaults to `201 Created`.
- `HasApiResponse::updatedResponse($data, $message = '', $statusCode = 200)` — success envelope for resource updates, defaults to `200 OK`.
- `HasApiResponse::deletedResponse($message = '', $statusCode = 200)` — success envelope for resource deletion (no data), defaults to `200 OK` (kept over `204 No Content` so the envelope body still reaches the client).
- `HasApiResponse::registeredSuccessfullyResponse($data, $token, $message = '', $statusCode = 201)` — success envelope for user registration, carrying data plus an auth token, defaults to `201 Created`.
- `examples/UserController.md` — real CRUD + registration controller from the host app, with live-captured request/response pairs for every scenario (success, validation failure, not found, unhandled/broken code — `app.debug` on and off).

### Changed

- `ApiRequest::failedValidation()` now throws `ValidationException` directly instead of wrapping a pre-built `JsonResponse` in `HttpResponseException`.
- `Response::$VALIDATION_FAILED_MESSAGE` is now `?string`, defaulting to `null` (uses the translated message) instead of a hardcoded English string. Still overridable.
- `composer.json` script `lint` renamed to `analyse`.
- Test suite converted from PHPUnit class-based tests to Pest (`test()` closures); `composer test` now runs `vendor/bin/pest --test-directory=src/tests`. This also forced a full dependency re-resolution — `orchestra/testbench`, `laravel/framework`, `phpunit/phpunit`, and others were previously locked to unstable `-dev` branches (no `prefer-stable` when first installed) and are now pinned to stable releases.
- `HasApiResponse::successResponseWithToken()` gained an optional `$message = ''` parameter, inserted before `$statusCode`, so token-bearing responses (e.g. registration) can carry a message alongside the token. **Breaking for positional callers**: any call passing `$statusCode` positionally as the 3rd argument now sets `$message` instead — pass `statusCode:` as a named argument, or add `$message` before it.
### Fixed

- `HasApiResponse::failedResponse()` accepted `Model`/`JsonResource`/`LengthAwarePaginator` for `$errors`, but forwarded it to `makeResponse()`, which only accepts `array|Collection` — narrowed the type to match (caught by PHPStan at level `max`).
- `GeneralErrorException`'s message, when rendered from an arbitrary caught `Throwable` via `Response::handleExceptions()`, no longer leaks the raw exception message to the client unless `config('app.debug')` is `true` (information disclosure).
- Workbench test-only request classes (`TestRequest`, `ChangeSpecificTestRequest`) lived under `workbench/app/Requests/` while namespaced `Workbench\App\Http\Requests\...`, breaking autoload — moved to match PSR-4.
- `HasApiResponse::withDebugTrace()` decoded the envelope via `getData(true)` (associative array) then re-encoded it, silently turning an empty `errors` object (`{}`) back into a JSON array (`[]`) — broke the "`errors` is always an object" contract whenever a debug block was attached to a response with no errors (e.g. `ModelNotFoundException`, `GeneralErrorException`). `errors` is now re-cast to an object after the merge.
