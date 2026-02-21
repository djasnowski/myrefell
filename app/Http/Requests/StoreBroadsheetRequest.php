<?php

namespace App\Http\Requests;

use App\Models\Broadsheet;
use Illuminate\Foundation\Http\FormRequest;

class StoreBroadsheetRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:'.Broadsheet::MAX_TITLE],
            'content' => ['required', 'array'],
            'plain_text' => ['required', 'string', 'max:'.Broadsheet::MAX_CONTENT_LENGTH],
        ];
    }
}
