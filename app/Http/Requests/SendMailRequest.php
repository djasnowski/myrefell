<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendMailRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'recipient_username' => ['required', 'string', 'exists:users,username'],
            'subject' => ['required', 'string', 'max:100'],
            'body' => ['required', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'recipient_username.exists' => 'No player found with that username.',
            'subject.max' => 'Subject cannot exceed 100 characters.',
            'body.max' => 'Message body cannot exceed 1000 characters.',
        ];
    }
}
