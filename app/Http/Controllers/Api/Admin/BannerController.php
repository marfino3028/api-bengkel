<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\BannerResource;
use App\Models\Banner;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    public function index()
    {
        return BannerResource::collection(Banner::orderBy('sort_order')->get());
    }

    public function store(Request $request)
    {
        $banner = Banner::create($this->validateData($request));

        return (new BannerResource($banner))->response()->setStatusCode(201);
    }

    public function show(Banner $banner)
    {
        return new BannerResource($banner);
    }

    public function update(Request $request, Banner $banner)
    {
        $banner->update($this->validateData($request));

        return new BannerResource($banner);
    }

    public function destroy(Banner $banner)
    {
        $banner->delete();

        return response()->json(['message' => 'Banner dihapus.']);
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'image' => ['required', 'string', 'max:1024'],
            'link' => ['nullable', 'string', 'max:1024'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer'],
        ]);
    }
}
