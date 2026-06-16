<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Modules\Finance\Repositories\Contracts\InvoiceRepositoryInterface;
use Modules\Finance\Transformers\InvoiceResource;
use App\Contracts\ConfigProviderInterface;

class InvoiceController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly InvoiceRepositoryInterface $invoiceRepository,
        private readonly ConfigProviderInterface $settingService,
    ) {}

    public function index(Request $request)
    {
        $request->validate([
            'per_page' => 'nullable|integer|min:1|max:200',
            'status' => 'nullable|string',
        ]);

        $perPage = (int) $request->query('per_page', 200);
        $filters = $request->only(['status']);

        $invoices = $this->invoiceRepository->getPaginated($perPage, $filters);

        return InvoiceResource::collection($invoices)->additional([
            'success' => true,
            'message' => 'Daftar tagihan berhasil diambil',
        ]);
    }

    public function show(int $id)
    {
        $invoice = $this->invoiceRepository->findById($id);

        if (! $invoice) {
            return response()->json([
                'success' => false,
                'message' => 'Tagihan tidak ditemukan',
            ], 404);
        }

        $invoice->load(['payments']);

        return $this->apiSuccess(new InvoiceResource($invoice), 'Detail tagihan berhasil diambil');
    }

    public function getPrintLink(int $id)
    {
        $invoice = $this->invoiceRepository->findById($id);

        if (! $invoice) {
            return response()->json(['success' => false, 'message' => 'Tagihan tidak ditemukan'], 404);
        }

        $url = URL::temporarySignedRoute(
            'finance.invoice.print',
            now()->addHours(2),
            ['id' => $id]
        );

        return $this->apiSuccess(['url' => $url], 'Link cetak berhasil dibuat');
    }

    public function printPdf(int $id)
    {
        $invoice = $this->invoiceRepository->findById($id);
        if (! $invoice) {
            abort(404);
        }

        $invoice->load(['payments']);

        $wismaName = $this->settingService->getSettingValue('wisma_name', 'Wisma Amal Gorontalo');

        $statusValue = is_object($invoice->status) ? $invoice->status->value : $invoice->status;
        $isPaid = strtolower($statusValue) === 'paid';

        $payment = null;
        if ($isPaid) {
            $payment = $invoice->payments
                ->whereIn('status', ['verified', 'paid'])
                ->sortByDesc('updated_at')
                ->first();
        }

        return view('finance::invoice-print', compact('invoice', 'wismaName', 'isPaid', 'payment'));
    }
}
