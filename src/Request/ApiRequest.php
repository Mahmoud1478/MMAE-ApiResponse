<?php

namespace MMAE\ApiResponse\Request;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use MMAE\ApiResponse\Configurations\Response;
use MMAE\ApiResponse\Exceptions\ValidationException;

class ApiRequest extends FormRequest
{
    /**
     * @throws ValidationException
     */
    #[\Override]
    public function failedValidation(Validator $validator): void
    {
        $message = property_exists($this, 'message') && is_string($this->message) ? $this->message : null;
        $statusCode = property_exists($this, 'statusCode') && is_int($this->statusCode) ? $this->statusCode : Response::$VALIDATION_FAILED_STATUS;

        throw new ValidationException($validator->errors()->toArray(), $message, $statusCode);
    }
}
