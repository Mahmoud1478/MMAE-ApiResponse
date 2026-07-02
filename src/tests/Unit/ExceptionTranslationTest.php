<?php

use Illuminate\Http\Request;
use MMAE\ApiResponse\Exceptions\GeneralErrorException;

test('render translates using the locale active at render time', function () {
    $exception = new GeneralErrorException;

    app()->setLocale('ar');
    $response = $exception->render(Request::create('/'));

    expect($response->getData(true)['message'])
        ->toBe(trans('apiresponse::messages.something_went_wrong', locale: 'ar'));
});
