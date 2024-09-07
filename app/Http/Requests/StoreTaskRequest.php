<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
{
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // dd($this->validated());
        return [
            'description' => ['string','max:191'],
            'data' => ['date_equals:today','date_format:Y-m-d'],
            'value' => ['numeric','min:0.01'],
            'user_id' => ['exists:users,id'],
        ];
    }
}
