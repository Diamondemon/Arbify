<?php

namespace Arbify\Http\Requests;

class ImportToProject extends AuthorizedFormRequest
{
    public function rules(): array
    {
        return [
            'files' => 'required|array',
            'files.*' => 'required|string',
            'override_message_values' => 'boolean',
        ];
    }
}
