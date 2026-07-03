<?php

namespace Modules\Setting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Setting\Services\FeatureToggleService;

class FeatureToggleController extends Controller
{
    public function __construct(
        private readonly FeatureToggleService $featureToggleService
    ) {}

    public function index()
    {
        $toggles = $this->featureToggleService->getHierarchicalToggles();

        return response()->json([
            'success' => true,
            'data' => $toggles,
        ]);
    }

    public function update(Request $request, string $key)
    {
        $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $success = $this->featureToggleService->updateStatus(
            $key,
            $request->is_active,
            $request->user() ? $request->user()->id : null
        );

        if (! $success) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah status fitur. Fitur tidak ditemukan atau terkunci.',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Status fitur berhasil diperbarui.',
        ]);
    }
}
