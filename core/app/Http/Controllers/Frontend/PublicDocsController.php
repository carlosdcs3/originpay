<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PublicDocsController extends Controller
{
    /**
     * Display the main documentation page.
     */
    public function index()
    {
        return view('frontend.docs.index');
    }

    /**
     * Display a specific documentation page.
     *
     * @param string $page
     */
    public function show($page)
    {
        // Allowed pages as per whitelist
        $allowedPages = [
            'authentication' => 'Autenticação',
            'environments'   => 'Ambientes',
            'charges'        => 'Cobranças',
            'pix'            => 'PIX',
            'card'           => 'Cartão',
            'refunds'        => 'Reembolsos',
            'payouts'        => 'Saques',
            'webhooks'       => 'Webhooks',
            'errors'         => 'Erros',
            'rate-limits'    => 'Rate Limits',
            'idempotency'    => 'Idempotência',
            'changelog'      => 'Changelog',
        ];

        if (!array_key_exists($page, $allowedPages)) {
            abort(404);
        }

        return view('frontend.docs.pages.' . $page, [
            'pageSlug'  => $page,
            'pageTitle' => $allowedPages[$page]
        ]);
    }

    // ==========================================
    // API REFERENCE ENTERPRISE (v1)
    // ==========================================

    public function apiReferenceIndex()
    {
        return view('frontend.docs.api_reference.index');
    }

    public function apiReferenceShow($endpoint)
    {
        $allowedEndpoints = [
            'get-payment'     => 'Retrieve a Payment',
            'create-payment'  => 'Create a Payment',
            'create-refund'   => 'Create a Refund',
            'create-payout'   => 'Create a Payout',
            'get-balance'     => 'Retrieve Balance',
            'create-customer' => 'Create a Customer',
            'test-webhook'    => 'Test a Webhook'
        ];

        if (!array_key_exists($endpoint, $allowedEndpoints)) {
            abort(404);
        }

        return view('frontend.docs.api_reference.endpoints.' . $endpoint, [
            'endpointSlug'  => $endpoint,
            'endpointTitle' => $allowedEndpoints[$endpoint]
        ]);
    }

    public function apiExplorer()
    {
        return view('frontend.docs.explorer');
    }

    public function webhookSimulator()
    {
        return view('frontend.docs.webhook_simulator');
    }

    public function developerResources()
    {
        return view('frontend.docs.resources');
    }

    public function migrationGuide()
    {
        return view('frontend.docs.migration');
    }

    public function releaseNotes()
    {
        return view('frontend.docs.release_notes');
    }
}
