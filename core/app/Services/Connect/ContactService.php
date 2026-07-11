<?php
namespace App\Services\Connect;

use App\Models\Connect\ConnectContact;
use App\Models\Connect\ConnectTag;
use App\Models\Connect\ConnectContactCustomField;
use App\Repositories\Connect\ContactRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

class ContactService
{
    protected $repository;

    public function __construct(ContactRepository $repository)
    {
        $this->repository = $repository;
    }

    public function searchContacts($merchantId, array $filters)
    {
        return $this->repository->searchPaginated($merchantId, $filters);
    }

    public function createContact(array $data, $merchantId)
    {
        $this->validateUniqueness($merchantId, $data);

        return DB::transaction(function () use ($data, $merchantId) {
            $contact = ConnectContact::create(array_merge(
                collect($data)->only(['name', 'email', 'phone', 'whatsapp', 'country', 'language', 'timezone', 'source', 'status', 'notes'])->toArray(),
                ['merchant_id' => $merchantId]
            ));

            $this->syncTags($contact, $data['tags'] ?? []);
            $this->syncCustomFields($contact, $data['custom_fields'] ?? []);

            event('ConnectContactCreated', $contact);

            return $contact;
        });
    }

    public function updateContact($id, array $data, $merchantId)
    {
        $contact = $this->repository->findById($merchantId, $id);
        $this->validateUniqueness($merchantId, $data, $id);

        return DB::transaction(function () use ($contact, $data, $merchantId) {
            $contact->update(
                collect($data)->only(['name', 'email', 'phone', 'whatsapp', 'country', 'language', 'timezone', 'source', 'status', 'notes'])->toArray()
            );

            if (isset($data['tags'])) {
                $this->syncTags($contact, $data['tags']);
            }
            
            if (isset($data['custom_fields'])) {
                $this->syncCustomFields($contact, $data['custom_fields']);
            }

            event('ConnectContactUpdated', $contact);

            return $contact;
        });
    }

    public function deleteContact($id, $merchantId)
    {
        $contact = $this->repository->findById($merchantId, $id);
        $contact->delete();
        event('ConnectContactDeleted', $contact);
    }

    public function restoreContact($id, $merchantId)
    {
        $contact = ConnectContact::forMerchant($merchantId)->withTrashed()->findOrFail($id);
        $contact->restore();
        event('ConnectContactRestored', $contact);
    }

    public function importContacts($merchantId, array $contactsData)
    {
        // Batch structure for future async importing.
        // For now, iterate and upsert securely.
        DB::transaction(function () use ($merchantId, $contactsData) {
            foreach ($contactsData as $data) {
                // Determine if exists
                $existing = null;
                if (!empty($data['email'])) {
                    $existing = ConnectContact::forMerchant($merchantId)->where('email', $data['email'])->first();
                }
                if (!$existing && !empty($data['whatsapp'])) {
                    $existing = ConnectContact::forMerchant($merchantId)->where('whatsapp', $data['whatsapp'])->first();
                }

                if ($existing) {
                    $this->updateContact($existing->id, $data, $merchantId);
                } else {
                    $this->createContact($data, $merchantId);
                }
            }
        });
    }

    public function mergeDuplicateContacts($merchantId, $targetId, $sourceId)
    {
        return DB::transaction(function () use ($merchantId, $targetId, $sourceId) {
            $target = $this->repository->findById($merchantId, $targetId);
            $source = $this->repository->findById($merchantId, $sourceId);

            // 1. Merge tags
            $sourceTags = $source->tags->pluck('id')->toArray();
            if (!empty($sourceTags)) {
                $target->tags()->syncWithoutDetaching($sourceTags);
            }

            // 2. Merge custom fields (target wins conflicts)
            $targetFields = $target->customFields->pluck('field_name')->toArray();
            foreach ($source->customFields as $field) {
                if (!in_array($field->field_name, $targetFields)) {
                    $field->update(['contact_id' => $target->id]);
                }
            }

            // 3. Delete source securely (Hard delete or soft delete depending on policy. Using soft delete for safety)
            $source->delete();

            event('ConnectContactMerged', ['target' => $target, 'source' => $source]);

            return $target;
        });
    }

    protected function validateUniqueness($merchantId, $data, $excludeId = null)
    {
        if (!empty($data['email'])) {
            $query = ConnectContact::forMerchant($merchantId)->where('email', $data['email']);
            if ($excludeId) $query->where('id', '!=', $excludeId);
            if ($query->exists()) throw ValidationException::withMessages(['email' => 'Email is already in use by another contact.']);
        }

        if (!empty($data['whatsapp'])) {
            $query = ConnectContact::forMerchant($merchantId)->where('whatsapp', $data['whatsapp']);
            if ($excludeId) $query->where('id', '!=', $excludeId);
            if ($query->exists()) throw ValidationException::withMessages(['whatsapp' => 'WhatsApp is already in use by another contact.']);
        }
    }

    protected function syncTags($contact, array $tagNames)
    {
        $tagIds = [];
        foreach ($tagNames as $name) {
            $name = trim($name);
            if (empty($name)) continue;
            
            $tag = ConnectTag::firstOrCreate(
                ['merchant_id' => $contact->merchant_id, 'name' => $name],
                ['color' => '#CCCCCC']
            );
            $tagIds[] = $tag->id;
        }
        $contact->tags()->sync($tagIds);
    }

    protected function syncCustomFields($contact, array $fields)
    {
        foreach ($fields as $name => $value) {
            ConnectContactCustomField::updateOrCreate(
                ['merchant_id' => $contact->merchant_id, 'contact_id' => $contact->id, 'field_name' => $name],
                ['field_value' => $value]
            );
        }
    }
}
