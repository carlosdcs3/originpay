<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\TrxType;
use App\Exceptions\NotifyErrorException;
use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use DB;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Log;
use Transaction;
use Wallet;

class TransactionController extends Controller
{
    public function index()
    {
        $status = request('status');
        if (! $status && request('tab') && request('tab') !== 'all') {
            $status = request('tab');
        }

        $dateRange = request('daterange');
        if (! $dateRange && (request('date_from') || request('date_to'))) {
            $startDate = request('date_from') ?: request('date_to');
            $endDate = request('date_to') ?: request('date_from');
            $dateRange = "{$startDate},{$endDate}";
        }

        $transactions = Transaction::getTransactions(
            user_id: auth()->user()->id,
            trx_type: request('type'),
            status: $status,
            search: request('search'),
            dateRange: $dateRange
        );

        $summaryRows = \App\Models\Transaction::query()
            ->applyFilters([
                'user_id' => auth()->user()->id,
                'trx_type' => request('type'),
                'status' => $status,
                'search' => request('search'),
                'dateRange' => $dateRange,
            ])
            ->get(['status', 'amount']);

        $tabCountRows = \App\Models\Transaction::query()
            ->applyFilters([
                'user_id' => auth()->user()->id,
                'trx_type' => request('type'),
                'status' => null,
                'search' => request('search'),
                'dateRange' => $dateRange,
            ])
            ->get(['status']);

        $approved = $summaryRows->where('status', \App\Enums\TrxStatus::COMPLETED)->count();
        $pending = $summaryRows->where('status', \App\Enums\TrxStatus::PENDING)->count();
        $failed = $summaryRows->where('status', \App\Enums\TrxStatus::FAILED)->count();
        $canceled = $summaryRows->where('status', \App\Enums\TrxStatus::CANCELED)->count();
        $total = $summaryRows->count();

        $transactionSummary = [
            'total' => $total,
            'approved' => $approved,
            'pending' => $pending,
            'failed' => $failed,
            'canceled' => $canceled,
            'total_volume' => $summaryRows->where('status', \App\Enums\TrxStatus::COMPLETED)->sum('amount'),
            'approval_rate' => $total > 0 ? round(($approved / $total) * 100, 2) : 0,
            'avg_ticket' => $approved > 0 ? $summaryRows->where('status', \App\Enums\TrxStatus::COMPLETED)->sum('amount') / $approved : 0,
            'chargebacks' => $summaryRows->where('status', \App\Enums\TrxStatus::CANCELED)->sum('amount'),
        ];

        $transactionCounts = [
            'all' => $tabCountRows->count(),
            'approved' => $tabCountRows->where('status', \App\Enums\TrxStatus::COMPLETED)->count(),
            'pending' => $tabCountRows->where('status', \App\Enums\TrxStatus::PENDING)->count(),
            'failed' => $tabCountRows->where('status', \App\Enums\TrxStatus::FAILED)->count(),
            'canceled' => $tabCountRows->where('status', \App\Enums\TrxStatus::CANCELED)->count(),
        ];

        return view('frontend.user.transaction.index', compact('transactions', 'transactionSummary', 'transactionCounts'));
    }

    /**
     * Handle transaction actions: save remarks, approve, or reject.
     *
     * @throws NotifyErrorException
     */
    public function handleAction(Request $request)
    {
        // Validate request inputs
        $validated = $request->validate([
            'trx_id'  => 'required|exists:transactions,trx_id',
            'remarks' => 'nullable|string|max:255',
            'action'  => 'required|in:approve,reject',
        ]);

        try {
            // Fetch the transaction
            $transaction = Transaction::findTransaction($validated['trx_id']);

            if (! $transaction) {
                throw new NotifyErrorException('Transaction not found.');
            }

            // Handle the approve action
            if ($validated['action'] === 'approve') {
                return $this->approveTransaction($transaction, $validated['remarks']);
            }

            // Handle the reject action
            if ($validated['action'] === 'reject') {
                return $this->rejectTransaction($transaction, $validated['remarks']);
            }
            throw new NotifyErrorException(__('Invalid action.'));
        } catch (Exception $e) {

            // Log the error and notify the user
            Log::error('Transaction handling error: '.$e->getMessage());

            throw new NotifyErrorException(__('An error occurred while processing the transaction.'));
        }
    }

    public function downloadPdf($trx_id)
    {
        // Retrieve the logo from the storage folder
        $logoPath = setting('logo'); // Assuming this returns a relative path like "logos/site-logo.png"

        $fileContent = Storage::get('public/'.$logoPath);
        $fileType    = pathinfo(Storage::path('public/'.$logoPath), PATHINFO_EXTENSION);
        $siteLogo    = 'data:image/'.$fileType.';base64,'.base64_encode($fileContent);

        // Retrieve transaction data
        $transaction = Transaction::findTransaction($trx_id);

        // Generate the PDF
        $pdf = Pdf::loadView('general.pdf.transaction', compact('transaction', 'siteLogo'));

        // Return the PDF for download
        return $pdf->download('transaction_receipt_'.$transaction->trx_id.'.pdf');
    }

    private function approveTransaction($transaction, $remarks)
    {
        if ($transaction->trx_type !== TrxType::REQUEST_MONEY && $transaction->status !== 'pending') {
            notifyEvs('error', 'A transação não pode ser aprovada.');

            return redirect()->back();
        }

        $payableAmount = $transaction->payable_amount;
        $myWalletUuid  = $transaction->wallet_reference;

        if (! Wallet::isWalletBalanceSufficient($myWalletUuid, $payableAmount)) {
            notifyEvs('error', 'Saldo insuficiente na sua carteira.');

            return redirect()->back();
        }

        // Complete transactions within a database transaction
        DB::transaction((function () use ($transaction, $remarks) {
            Transaction::completeTransaction($transaction->trx_id);
            Transaction::completeTransaction($transaction->trx_reference, $remarks);
        }));

        notifyEvs('success', 'Transação aprovada com sucesso.');

        return redirect()->back();
    }

    private function rejectTransaction($transaction, $remarks)
    {

        // Cancel transactions within a database transaction
        DB::transaction((function () use ($transaction, $remarks) {
            Transaction::cancelTransaction($transaction->trx_id);
            Transaction::cancelTransaction($transaction->trx_reference, $remarks);
        }));

        notifyEvs('success', 'Transação rejeitada com sucesso.');

        return redirect()->back();
    }
}
