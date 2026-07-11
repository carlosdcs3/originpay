<?php

namespace App\Http\Requests\Backend\Finance\Disputes;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Dispute;

class RequestDocumentRequest extends FormRequest
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
            'document_type' => 'required|string|max:50',
            'label' => 'required|string|max:100',
        ];
    }
}
