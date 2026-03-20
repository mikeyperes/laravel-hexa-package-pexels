<?php

namespace hexa_package_pexels\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use hexa_package_pexels\Services\PexelsService;

/**
 * PexelsController — handles the raw dev page and AJAX search endpoint.
 */
class PexelsController extends Controller
{
    /**
     * Show the raw dev page for Pexels.
     *
     * @return \Illuminate\View\View
     */
    public function raw()
    {
        return view('pexels::raw.index');
    }

    /**
     * AJAX: Search photos via Pexels API.
     *
     * @param Request $request
     * @param PexelsService $service
     * @return JsonResponse
     */
    public function search(Request $request, PexelsService $service): JsonResponse
    {
        $request->validate([
            'query'    => 'required|string|max:255',
            'per_page' => 'nullable|integer|min:1|max:80',
            'page'     => 'nullable|integer|min:1',
        ]);

        $result = $service->searchPhotos(
            $request->input('query'),
            (int) $request->input('per_page', 15),
            (int) $request->input('page', 1)
        );

        return response()->json($result);
    }
}
