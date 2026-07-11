<?php

namespace App\Http\Requests\Backend\Finance\Disputes;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Dispute;

class CloseDisputeRequest extends FormRequest
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
            'close_result' => 'required|string|in:won,lost,canceled',
            'reason' => 'nullable|string|max:500'
        ];
    }
}
