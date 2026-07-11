<?php
namespace App\Http\Controllers\Merchant\Connect;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ConnectContact;
use App\Models\ConnectTag;
use Illuminate\Support\Facades\Auth;

class ConnectContactController extends Controller
{
    public function index(Request $request)
    {
        $merchantId = Auth::id();

        // 1. Calculate KPI Metrics
        $totalContacts = ConnectContact::where('merchant_id', $merchantId)->count();
        $activeContacts = ConnectContact::where('merchant_id', $merchantId)->active()->count();
        $whatsappContacts = ConnectContact::where('merchant_id', $merchantId)->hasWhatsapp()->count();
        $emailContacts = ConnectContact::where('merchant_id', $merchantId)->hasEmail()->count();

        // 2. Fetch Tags for Filter Dropdown
        $merchantTags = ConnectTag::where('merchant_id', $merchantId)->orderBy('name')->get();

        // 3. Query Contacts with filters
        $query = ConnectContact::with('tags')->where('merchant_id', $merchantId);

        // Filter: Search
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Filter: Status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter: Source
        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }

        // Filter: Language
        if ($request->filled('language')) {
            $query->where('language', $request->language);
        }

        // Filter: Tag
        if ($request->filled('tag')) {
            $tagId = $request->tag;
            $query->whereHas('tags', function($q) use ($tagId) {
                $q->where('connect_tags.id', $tagId);
            });
        }

        $contacts = $query->latest('updated_at')->paginate(15)->withQueryString();

        return view('frontend.merchant.connect.contact', compact(
            'totalContacts',
            'activeContacts',
            'whatsappContacts',
            'emailContacts',
            'merchantTags',
            'contacts'
        ));
    }
    
    public function create()
    {
        $merchantId = Auth::id();
        $merchantTags = ConnectTag::where('merchant_id', $merchantId)->orderBy('name')->get();
        $contact = new ConnectContact();
        $isEdit = false;

        return view('frontend.merchant.connect.contact_form', compact('contact', 'merchantTags', 'isEdit'));
    }

    public function store(Request $request)
    {
        return back()->with('success', 'Criado com sucesso');
    }

    public function show($id)
    {
        return view("frontend.merchant.connect.coming_soon");
    }

    public function edit($id)
    {
        $merchantId = Auth::id();
        $contact = ConnectContact::where('merchant_id', $merchantId)->findOrFail($id);
        $merchantTags = ConnectTag::where('merchant_id', $merchantId)->orderBy('name')->get();
        $isEdit = true;

        return view('frontend.merchant.connect.contact_form', compact('contact', 'merchantTags', 'isEdit'));
    }

    public function update(Request $request, $id)
    {
        return back()->with('success', 'Atualizado com sucesso');
    }

    public function destroy($id)
    {
        return back()->with('success', 'Removido com sucesso');
    }
}