<?php
namespace App\Services\Connect\Journey;
use App\Models\Connect\Journey\ConnectJourneyInstance;

class JourneyConditionEvaluator
{
    public function evaluate(ConnectJourneyInstance $instance, array $node): bool
    {
        // $node['data'] = ['field' => 'contact.tags', 'operator' => 'contains', 'value' => 'vip']
        $field = $node['data']['field'] ?? '';
        $operator = $node['data']['operator'] ?? '==';
        $expected = $node['data']['value'] ?? '';

        $contact = $instance->contact;
        $actual = null;

        if ($field === 'contact.tags') {
            $actual = $contact->tags ?? []; // Simplified
            if ($operator === 'contains') return in_array($expected, $actual);
        }

        // Add more logic here...
        return false;
    }
}
