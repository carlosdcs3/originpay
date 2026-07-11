<?php
namespace App\Services\Connect;

use App\Models\Connect\ConnectContact;
use Illuminate\Database\Eloquent\Builder;

class SegmentEngine
{
    protected $allowedFields = [
        'name', 'email', 'whatsapp', 'country', 'language', 'timezone', 'status', 'source', 'created_at', 'updated_at', 'tag'
    ];

    protected $allowedOperators = [
        'equals', 'not_equals', 'contains', 'not_contains', 'starts_with', 'ends_with', 
        'in', 'not_in', 'greater_than', 'less_than', 'between', 'is_null', 'is_not_null'
    ];

    public function buildQuery($merchantId, array $rulesPayload): Builder
    {
        $query = ConnectContact::forMerchant($merchantId);
        
        if (empty($rulesPayload['rules'])) {
            return $query;
        }
        
        $condition = strtolower($rulesPayload['condition'] ?? 'and') === 'or' ? 'or' : 'and';

        $query->where(function (Builder $q) use ($rulesPayload, $condition) {
            $this->applyRulesGroup($q, $rulesPayload['rules'], $condition);
        });

        return $query;
    }

    protected function applyRulesGroup(Builder $query, array $rules, string $condition)
    {
        foreach ($rules as $rule) {
            // Nested groups support
            if (isset($rule['condition']) && isset($rule['rules'])) {
                $subCondition = strtolower($rule['condition']) === 'or' ? 'orWhere' : 'where';
                $query->{$subCondition}(function (Builder $subQ) use ($rule) {
                    $this->applyRulesGroup($subQ, $rule['rules'], strtolower($rule['condition']));
                });
                continue;
            }

            if (!isset($rule['field']) || !isset($rule['operator'])) {
                continue;
            }

            $this->applyRule($query, $rule, $condition);
        }
    }

    protected function applyRule(Builder $query, array $rule, string $condition)
    {
        $field = $rule['field'];
        $operator = $rule['operator'];
        $value = $rule['value'] ?? null;
        
        $method = $condition === 'or' ? 'orWhere' : 'where';

        if ($field === 'tag') {
            $hasMethod = $condition === 'or' ? 'orWhereHas' : 'whereHas';
            $query->{$hasMethod}('tags', function (Builder $q) use ($operator, $value) {
                $this->applyCondition($q, 'name', $operator, $value, 'and');
            });
            return;
        }

        $this->applyCondition($query, $field, $operator, $value, $condition);
    }

    protected function applyCondition(Builder $query, $field, $operator, $value, $condition)
    {
        $method = $condition === 'or' ? 'orWhere' : 'where';
        
        switch ($operator) {
            case 'equals':
                $query->{$method}($field, '=', $value);
                break;
            case 'not_equals':
                $query->{$method}($field, '!=', $value);
                break;
            case 'contains':
                $query->{$method}($field, 'LIKE', "%{$value}%");
                break;
            case 'not_contains':
                $query->{$method}($field, 'NOT LIKE', "%{$value}%");
                break;
            case 'starts_with':
                $query->{$method}($field, 'LIKE', "{$value}%");
                break;
            case 'ends_with':
                $query->{$method}($field, 'LIKE', "%{$value}");
                break;
            case 'in':
                $inMethod = $condition === 'or' ? 'orWhereIn' : 'whereIn';
                $query->{$inMethod}($field, is_array($value) ? $value : explode(',', $value));
                break;
            case 'not_in':
                $notInMethod = $condition === 'or' ? 'orWhereNotIn' : 'whereNotIn';
                $query->{$notInMethod}($field, is_array($value) ? $value : explode(',', $value));
                break;
            case 'greater_than':
                $query->{$method}($field, '>', $value);
                break;
            case 'less_than':
                $query->{$method}($field, '<', $value);
                break;
            case 'is_null':
                $nullMethod = $condition === 'or' ? 'orWhereNull' : 'whereNull';
                $query->{$nullMethod}($field);
                break;
            case 'is_not_null':
                $notNullMethod = $condition === 'or' ? 'orWhereNotNull' : 'whereNotNull';
                $query->{$notNullMethod}($field);
                break;
        }
    }
}
