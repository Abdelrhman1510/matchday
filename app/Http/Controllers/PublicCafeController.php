<?php

namespace App\Http\Controllers;

use App\Services\CafeService;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class PublicCafeController extends Controller
{
    protected CafeService $cafeService;

    public function __construct(CafeService $cafeService)
    {
        $this->cafeService = $cafeService;
    }

    /**
     * Show the public cafe page
     */
    public function show(int $id): View|RedirectResponse
    {
        $cafe = $this->cafeService->getCafeById($id);

        if (!$cafe) {
            abort(404, 'Cafe not found');
        }

        return view('public.cafe', compact('cafe'));
    }
}
