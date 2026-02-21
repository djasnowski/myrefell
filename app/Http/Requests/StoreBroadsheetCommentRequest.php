<?php

namespace App\Http\Requests;

use App\Models\BroadsheetComment;
use Illuminate\Foundation\Http\FormRequest;

class StoreBroadsheetCommentRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:'.BroadsheetComment::MAX_BODY],
            'parent_id' => ['nullable', 'integer', 'exists:broadsheet_comments,id'],
        ];
    }
}
