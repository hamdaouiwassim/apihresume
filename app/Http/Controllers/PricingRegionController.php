<?php

namespace App\Http\Controllers;

use App\Services\PricingRegionService;
use Illuminate\Http\Request;

class PricingRegionController extends Controller
{
    public function __construct(
        private readonly PricingRegionService $pricingRegionService
    ) {}

    public function show(Request $request)
    {
        return response()->json([
            'status' => true,
            'data' => $this->pricingRegionService->resolve($request),
        ]);
    }
}
