<?php

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Workbench\App\Http\Requests\ChangeSpecificTestRequest;
use Workbench\App\Http\Requests\TestRequest;

Route::get('/request-response', function (TestRequest $request) {});
Route::get('/request-specific-response', function (ChangeSpecificTestRequest $request) {});

Route::get('/throws-validation-exception', function (Request $request) {
    $request->validate(['name' => 'required']);
});

Route::get('/throws-model-not-found-exception', function () {
    throw (new ModelNotFoundException)->setModel('Workbench\App\Models\DummyModel', [1]);
});

Route::get('/throws-general-error-exception', function () {
    throw new Exception('boom');
});
