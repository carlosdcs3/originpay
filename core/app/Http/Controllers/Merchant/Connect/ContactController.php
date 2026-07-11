<?php
namespace App\Http\Controllers\Merchant\Connect;

use App\Http\Controllers\Controller;
use App\Services\Connect\ContactService;
use App\Support\Connect\Capabilities;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContactController extends Controller
{
    protected $contactService;

    public function __construct(ContactService $contactService)
    {
        $this->contactService = $contactService;
    }

    public function index(Request $request)
    {
        $this->authorize(Capabilities::CONTACT_READ);
        
        $filters = $request->only(['search', 'tag', 'source', 'status']);
        $contacts = $this->contactService->searchContacts(Auth::id(), $filters);

        return view('frontend.merchant.connect.contacts.index', compact('contacts'));
    }

    public function create()
    {
        $this->authorize(Capabilities::CONTACT_WRITE);
        return view('frontend.merchant.connect.contacts.create');
    }

    public function store(Request $request)
    {
        $this->authorize(Capabilities::CONTACT_WRITE);
        
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'whatsapp' => 'nullable|string|max:20',
            // Simple validation, actual business constraints in service
        ]);

        $this->contactService->createContact($validated, Auth::id());

        return redirect()->route('merchant.connect.contacts.index')->with('success', 'Contato criado.');
    }

    public function edit($id)
    {
        $this->authorize(Capabilities::CONTACT_WRITE);
        // We'll let the Service/Repository fetch it to avoid tight coupling in controller
        // For the view, we can just fetch it directly through repo but service is fine.
        $contact = app(\App\Repositories\Connect\ContactRepository::class)->findById(Auth::id(), $id);
        
        return view('frontend.merchant.connect.contacts.edit', compact('contact'));
    }

    public function update(Request $request, $id)
    {
        $this->authorize(Capabilities::CONTACT_WRITE);
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'whatsapp' => 'nullable|string|max:20',
        ]);

        $this->contactService->updateContact($id, $validated, Auth::id());
        
        return redirect()->route('merchant.connect.contacts.index')->with('success', 'Contato atualizado.');
    }
}
