# Example: `UserController` (host app)

Real controller from the host app (`testing_env`), showing `HasApiResponse` used for
a full CRUD + registration flow. All responses below were captured live against this
exact code — not hand-written samples.

## The code

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\Api\V1\User\LoginUserRequest;
use App\Http\Requests\Api\V1\User\StoreUserRequest;
use App\Http\Requests\Api\V1\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

class UserController extends Controller
{
    use HasApiResponse;

    public function index(): JsonResponse
    {
        return $this->successResponse(UserResource::collection(User::paginate()));
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        try {
            return DB::transaction(function () use ($request) {
                $user = User::create($request->validated());

                return $this->createdResponse(UserResource::make($user), 'User created successfully');
            });
        } catch (Throwable $exception) {
            return $this->exceptionResponse($exception);
        }
    }

    public function register(StoreUserRequest $request): JsonResponse
    {
        try {
            return DB::transaction(function () use ($request) {
                $user = User::create($request->validated());
                $token = $user->createToken('api')->plainTextToken;

                return $this->registeredSuccessfullyResponse(
                    UserResource::make($user),
                    $token,
                    'Registered successfully',
                );
            });
        } catch (Throwable $exception) {
            return $this->exceptionResponse($exception);
        }
    }

    public function login(LoginUserRequest $request): JsonResponse
    {
        try {
            $user = User::where('email', $request->validated('email'))->first();

            if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
                return $this->failedMessageResponse('Invalid credentials', SymfonyResponse::HTTP_UNAUTHORIZED);
            }

            $token = $user->createToken('api')->plainTextToken;

            return $this->successResponseWithToken(
                UserResource::make($user),
                $token,
                'Login successful',
            );
        } catch (Throwable $exception) {
            return $this->exceptionResponse($exception);
        }
    }

    public function show(User $user): JsonResponse
    {
        return $this->successResponse(UserResource::make($user));
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        try {
            return DB::transaction(function () use ($request, $user) {
                $user->update($request->validated());

                return $this->updatedResponse(UserResource::make($user), 'User updated successfully');
            });
        } catch (Throwable $exception) {
            return $this->exceptionResponse($exception);
        }
    }

    public function destroy(User $user): JsonResponse
    {
        try {
            return DB::transaction(function () use ($user) {
                $user->delete();

                return $this->deletedResponse('User deleted successfully');
            });
        } catch (Throwable $exception) {
            return $this->exceptionResponse($exception);
        }
    }
}
```

Routes (`routes/api.php`):

```php
Route::post('register', [UserController::class, 'register']);
Route::post('login', [UserController::class, 'login']);
Route::apiResource('users', UserController::class);
```

`DB::transaction()` wraps every write, with the envelope response built *inside* the
closure and returned straight out of `DB::transaction()` — so the transaction only
commits once the response is fully constructed. On any thrown error inside the
closure, the transaction auto-rolls-back before the exception reaches the `catch`
block, which hands it to `exceptionResponse()`. `login()` has no write beyond
`createToken()`, so it skips the transaction wrapper.

---

## 1. Working code, request OK — 2xx

**`GET /api/users/12`** → `show()`, `successResponse()`

```
HTTP/1.1 200 OK
```
```json
{
  "success": true,
  "data": {
    "id": 12,
    "name": "Caveman Tester",
    "email": "caveman@example.com",
    "email_verified_at": null,
    "created_at": "2026-07-02T20:39:03.000000Z",
    "updated_at": "2026-07-02T20:39:03.000000Z"
  },
  "errors": {},
  "message": "",
  "token": ""
}
```

**`POST /api/users`** → `store()`, `createdResponse()`

```
HTTP/1.1 201 Created
```
```json
{
  "success": true,
  "data": { "id": 16, "name": "Store Only", "email": "storeonly@example.com", "email_verified_at": null, "created_at": "2026-07-02T20:47:46.000000Z", "updated_at": "2026-07-02T20:47:46.000000Z" },
  "errors": {},
  "message": "User created successfully",
  "token": ""
}
```

**`POST /api/register`** → `register()`, `registeredSuccessfullyResponse()`

```
HTTP/1.1 201 Created
```
```json
{
  "success": true,
  "data": { "id": 20, "name": "Doc Example", "email": "docexample@example.com", "email_verified_at": null, "created_at": "2026-07-02T21:00:04.000000Z", "updated_at": "2026-07-02T21:00:04.000000Z" },
  "errors": {},
  "message": "Registered successfully",
  "token": "4|zBbdIA1ezpuzhJnJ139SCiAH2h1FZzaACXYxNj3Kf3c235b8"
}
```

**`POST /api/login`** → `login()`, `successResponseWithToken()`

```
HTTP/1.1 200 OK
```
```json
{
  "success": true,
  "data": { "id": 22, "name": "Login Tester", "email": "logintest@example.com", "email_verified_at": null, "created_at": "2026-07-02T21:09:06.000000Z", "updated_at": "2026-07-02T21:09:06.000000Z" },
  "errors": {},
  "message": "Login successful",
  "token": "6|J7vj3nfo4PLTYH0P01FpuCtR61duSrrrpXUZ1y5Hae8b4f34"
}
```

**`PATCH /api/users/12`** → `update()`, `updatedResponse()`

```
HTTP/1.1 200 OK
```
```json
{
  "success": true,
  "data": { "id": 12, "name": "Caveman Updated", "email": "caveman@example.com", "email_verified_at": null, "created_at": "2026-07-02T20:39:03.000000Z", "updated_at": "2026-07-02T20:40:05.000000Z" },
  "errors": {},
  "message": "User updated successfully",
  "token": ""
}
```

**`DELETE /api/users/12`** → `destroy()`, `deletedResponse()`

```
HTTP/1.1 200 OK
```
```json
{ "success": true, "data": [], "errors": {}, "message": "User deleted successfully", "token": "" }
```

---

## 2. Login failure — bad credentials

`login()` checks the user manually (no `ApiRequest` exception path involved here) and
returns `failedMessageResponse()` directly with **401**.

**`POST /api/login` with wrong password**

```
HTTP/1.1 401 Unauthorized
```
```json
{ "success": false, "data": [], "errors": {}, "token": "", "message": "Invalid credentials" }
```

---

## 3. Validation error — `StoreUserRequest`/`UpdateUserRequest`/`LoginUserRequest` fail

`ApiRequest::failedValidation()` throws `ValidationException` directly — never reaches
the controller's `try`/`catch`. Status comes from `Response::$VALIDATION_FAILED_STATUS`
(package default: **422**).

**This host app overrides the default** in `AppServiceProvider::boot()`:

```php
Response::$VALIDATION_FAILED_STATUS = Response::$FAILED_STATUS = 200;
Response::$VALIDATION_FAILED_MESSAGE = trans('Validation Error');
```

So on this app, a validation failure comes back as:

**`POST /api/users` with `{}`**

```
HTTP/1.1 200 OK
```
```json
{
  "success": false,
  "data": [],
  "errors": {
    "name": ["The name field is required."],
    "email": ["The email field is required."],
    "password": ["The password field is required."]
  },
  "message": "Validation Error",
  "token": ""
}
```

Without that override, a fresh install returns `422` and message `"Validation Failed"`
(`apiresponse::messages.validation_failed`) instead.

`ValidationException` never carries `$previous`, so `app.debug` has **no effect** on
this response shape — no `debug` block ever appears here, on or off.

---

## 4. Not found — route model binding fails

`GET /api/users/{user}` fails to resolve → Laravel's `ModelNotFoundException` →
mapped by `Response::handleExceptions()` (wired in `bootstrap/app.php`) to the
package's own `ModelNotFoundException`, with the original exception preserved as
`$previous`. Status: **404**.

### `app.debug = true`

```
HTTP/1.1 404 Not Found
```
```json
{
  "success": false,
  "data": [],
  "errors": {},
  "message": "No query results for model [User] 999999",
  "token": "",
  "debug": {
    "exception": "Illuminate\\Database\\Eloquent\\ModelNotFoundException",
    "file": "...\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\ImplicitRouteBinding.php",
    "line": 61,
    "trace": [
      { "file": "...\\Router.php", "line": 980, "function": "resolveForRoute", "class": "Illuminate\\Routing\\ImplicitRouteBinding", "type": "::" },
      { "function": "...", "class": "Illuminate\\Routing\\Router", "type": "->" }
      /* ...remaining frames, args always stripped... */
    ]
  }
}
```

- `message` is the **raw** exception message (`getMessage()` of the real `ModelNotFoundException`).
- `debug.trace` frame `args` are always stripped, even in debug mode (may hold sensitive values).

### `app.debug = false`

```
HTTP/1.1 404 Not Found
```
```json
{
  "success": false,
  "data": [],
  "errors": {},
  "message": "Resource not found",
  "token": ""
}
```

- `message` falls back to the translated default (`apiresponse::messages.resource_not_found`).
- No `debug` key at all.

---

## 5. Broken code — unhandled `Throwable`

Simulated by forcing `json_decode()` to throw a `JsonException` inside a `try`/`catch`
that calls `$this->exceptionResponse($exception)` (see `ApiResponseController@index`).
Since `JsonException` doesn't implement `ExceptionContract` and isn't a
`ModelNotFoundException`/`RouteNotFoundException`, it falls to the final branch:
`report($exception)` (logged) + wrapped in `GeneralErrorException`. Status: **500**.

### `app.debug = true`

```
HTTP/1.1 500 Internal Server Error
```
```json
{
  "success": false,
  "data": [],
  "errors": {},
  "message": "Control character error, possibly incorrectly encoded",
  "token": "",
  "debug": {
    "exception": "JsonException",
    "file": "...\\app\\Http\\Controllers\\ApiResponseController.php",
    "line": 18,
    "trace": [
      { "file": "...\\ApiResponseController.php", "line": 18, "function": "json_decode" },
      { "file": "...\\ControllerDispatcher.php", "line": 46, "function": "index", "class": "App\\Http\\Controllers\\ApiResponseController", "type": "->" }
      /* ...remaining framework frames, args always stripped... */
    ]
  }
}
```

### `app.debug = false`

```
HTTP/1.1 500 Internal Server Error
```
```json
{ "success": false, "data": [], "errors": {}, "message": "Something went wrong", "token": "" }
```

- `message` is the translated default (`apiresponse::messages.something_went_wrong`) — the raw
  `JsonException` message is never leaked.
- Unlike the `ModelNotFoundException` case, this path also always calls `report($exception)`
  regardless of `app.debug` — it's logged either way, only the client-facing payload changes.

---

## Summary table

| Scenario | Status | `debug` key present? | `message` source |
|---|---|---|---|
| Success (index/show/store/register/login/update/destroy) | 200 / 201 | never | explicit string passed by controller |
| Login failure (bad credentials) | 401 | never | explicit string passed by controller (`failedMessageResponse()`) |
| Validation failure | 422 (this app: 200, see override above) | never (`ValidationException` has no `$previous` here) | `Response::$VALIDATION_FAILED_MESSAGE` or translated default |
| Model not found | 404 | only if `app.debug=true` | raw exception message (debug) / translated default (no debug) |
| Broken/unhandled code | 500 | only if `app.debug=true` | raw exception message (debug) / translated default (no debug) — always reported |
