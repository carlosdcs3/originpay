<?php

namespace App\Http\Requests\Backend\Finance\Disputes;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Dispute;

class SendMessageRequest extends FormRequest
{
    public function authorize()
    {
        $dispute = Dispute::where('uuid', $this->route('uuid'))->first();
        if (!$dispute) return false;
        
        return $this->user()->can('view', $dispute);
    }

    public function rules()
    {
        return [
            'message' => 'required|string|max:2000',
            'is_internal_note' => 'nullable|boolean'
        ];
    }
}
