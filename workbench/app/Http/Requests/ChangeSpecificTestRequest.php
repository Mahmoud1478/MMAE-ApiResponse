<?php

namespace Workbench\App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use MMAE\ApiResponse\Request\ApiRequest;

class ChangeSpecificTestRequest extends ApiRequest
{
    public string $message = 'testing';

    public int $statusCode = 200;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required',
        ];
    }
}
