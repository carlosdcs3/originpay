<?php
namespace App\Repositories\Connect;

use App\Models\Connect\ConnectContact;
use Illuminate\Database\Eloquent\Builder;

class ContactRepository
{
    public function searchPaginated($merchantId, array $filters, $perPage = 15)
    {
        $query = ConnectContact::forMerchant($merchantId)
            ->with(['tags', 'customFields'])
            ->orderBy('created_at', 'desc');

        if (!empty($filters['search'])) {
            $term = '%' . $filters['search'] . '%';
            $query->where(function(Builder $q) use ($term) {
                $q->where('name', 'like', $term)
                  ->orWhere('email', 'like', $term)
                  ->orWhere('phone', 'like', $term)
                  ->orWhere('whatsapp', 'like', $term);
            });
        }

        if (!empty($filters['source'])) {
            $query->where('source', $filters['source']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['tag'])) {
            $query->whereHas('tags', function (Builder $q) use ($filters) {
                $q->where('name', $filters['tag']);
            });
        }

        return $query->paginate($perPage);
    }

    public function findById($merchantId, $id)
    {
        return ConnectContact::forMerchant($merchantId)
            ->with(['tags', 'customFields'])
            ->findOrFail($id);
    }
}
