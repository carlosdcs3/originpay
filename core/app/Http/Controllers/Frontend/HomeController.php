<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\CommercialPolicy;
use Illuminate\Support\Facades\Schema;

class HomeController extends Controller
{
    public function __invoke()
    {
        $policy = null;

        if (Schema::hasTable('commercial_policies')) {
            $policy = CommercialPolicy::active()->first();
        }

        $rates  = $policy?->payload ?? [];

        return view('frontend.pages.digisynk-home', compact('rates'));
    }
}
