<?php

namespace MMAE\ApiResponse\Request;

use MMAE\ApiResponse\Configurations\Response;
use MMAE\Apiresponse\Traits\HasApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ApiRequest extends FormRequest
{
    use HasApiResponse;
    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException($this->failedResponse(
            $validator->errors()->toArray(),
            Response::$VALIDATION_FAILED_MESSAGE ,
            Response::$VALIDATION_FAILED_STATUS
        ));
    }


}
