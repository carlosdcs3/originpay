<?php
namespace App\Http\Controllers\Merchant\Connect;

use App\Http\Controllers\Controller;
use App\Services\Connect\TemplateService;
use App\Services\Connect\Template\TemplatePreviewService;
use App\Models\Connect\Template;
use App\Support\Connect\Capabilities;
use App\Support\Connect\TemplateVariableRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TemplateController extends Controller
{
    protected $service;

    public function __construct(TemplateService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $this->authorize(Capabilities::TEMPLATE_READ, Template::class);
        $templates = Template::forMerchant(Auth::id())->where('is_current', true)->paginate(15);
        return view('frontend.merchant.connect.templates.index', compact('templates'));
    }

    public function create()
    {
        $this->authorize(Capabilities::TEMPLATE_WRITE, Template::class);
        $variables = TemplateVariableRegistry::all();
        // Base skeleton JSON for UI
        $defaultJson = json_encode([
            "version" => 1,
            "blocks" => [
                [
                    "id" => Str::uuid()->toString(),
                    "type" => "paragraph",
                    "content" => "Olá {{contact.name}}!"
                ]
            ]
        ], JSON_PRETTY_PRINT);

        return view('frontend.merchant.connect.templates.create', compact('variables', 'defaultJson'));
    }

    public function store(Request $request)
    {
        $this->authorize(Capabilities::TEMPLATE_WRITE, Template::class);
        $validated = $request->validate([
            'channel' => 'required|in:email,whatsapp,sms',
            'name' => 'required|string|max:255',
            'subject' => 'nullable|string',
            'content' => 'required|string',
        ]);

        $this->service->createTemplate($validated, Auth::id(), Auth::id());
        return redirect()->route('merchant.connect.templates.index')->with('success', 'Template criado como Draft.');
    }

    public function edit($id)
    {
        $this->authorize(Capabilities::TEMPLATE_WRITE, Template::class);
        $template = Template::forMerchant(Auth::id())->findOrFail($id);
        $variables = TemplateVariableRegistry::all();
        return view('frontend.merchant.connect.templates.edit', compact('template', 'variables'));
    }

    public function update(Request $request, $id)
    {
        $this->authorize(Capabilities::TEMPLATE_WRITE, Template::class);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'nullable|string',
            'content' => 'required|string',
        ]);

        $this->service->updateTemplate($id, $validated, Auth::id(), Auth::id());
        return redirect()->route('merchant.connect.templates.index')->with('success', 'Template atualizado.');
    }

    public function publish($id)
    {
        $this->authorize(Capabilities::TEMPLATE_PUBLISH, Template::class);
        $this->service->publishTemplate($id, Auth::id(), Auth::id());
        return back()->with('success', 'Template Publicado com sucesso!');
    }

    public function preview(Request $request, TemplatePreviewService $previewService)
    {
        $this->authorize(Capabilities::TEMPLATE_READ, Template::class);
        $ast = json_decode($request->content, true);
        if(!$ast) return response()->json(['error' => 'Invalid JSON'], 400);

        try {
            $html = $previewService->generatePreview($ast, $request->channel);
            return response()->json(['preview' => nl2br(e($html))]); // Simple presentation for now
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
