<?php

namespace App\Http\Controllers\Admin\Billing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CommercialFeature;
use App\Models\CommercialPlan;
use App\Models\Product;

class PlanController extends Controller
{
    public function index()
    {
        $pageTitle = 'Billing Plans';
        $plans = CommercialPlan::with('product')->orderBy('sort_order')->get();
        return view('admin.billing.plans.index', compact('pageTitle', 'plans'));
    }

    public function create()
    {
        $pageTitle = 'Create Plan';
        $products = Product::orderBy('sort_order')->orderBy('name')->get();
        $features = CommercialFeature::orderBy('category')->orderBy('name')->get();

        return view('admin.billing.plans.form', compact('pageTitle', 'products', 'features'));
    }

    public function edit($id)
    {
        $pageTitle = 'Edit Plan';
        $plan = CommercialPlan::findOrFail($id);
        $products = Product::orderBy('sort_order')->orderBy('name')->get();
        $features = CommercialFeature::orderBy('category')->orderBy('name')->get();

        return view('admin.billing.plans.form', compact('pageTitle', 'plan', 'products', 'features'));
    }

    public function store(Request $request)
    {
        CommercialPlan::create($this->validatedData($request));

        return redirect()->route('admin.billing.plans.index')->with('notify', [['success', 'Plan created successfully']]);
    }

    public function update(Request $request, $id)
    {
        $plan = CommercialPlan::findOrFail($id);
        $plan->update($this->validatedData($request, $plan->id));

        return redirect()->route('admin.billing.plans.index')->with('notify', [['success', 'Plan updated successfully']]);
    }

    public function destroy($id)
    {
        CommercialPlan::findOrFail($id)->delete();

        return redirect()->route('admin.billing.plans.index')->with('notify', [['success', 'Plan deleted successfully']]);
    }

    private function validatedData(Request $request, ?int $planId = null): array
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:commercial_plans,slug'.($planId ? ','.$planId : '')],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer'],
            'color' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:255'],
            'badge' => ['nullable', 'string', 'max:255'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
