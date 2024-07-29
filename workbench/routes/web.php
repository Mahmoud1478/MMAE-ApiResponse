<?php

use Illuminate\Support\Facades\Route;
use Workbench\App\Http\Requests\ChangeSpecificTestRequest;
use Workbench\App\Http\Requests\TestRequest;

Route::get('/request-response', function (TestRequest $request) {});
Route::get('/request-specific-response', function (ChangeSpecificTestRequest $request) {});
