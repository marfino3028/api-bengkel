<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Support\Media;
use Illuminate\Http\Request;

class UploadController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'image' => ['required', 'image', 'max:4096'], // max 4MB
            'folder' => ['nullable', 'string', 'in:products,services,banners,avatars'],
        ]);

        $folder = $request->input('folder', 'products');
        $path = $request->file('image')->store($folder, 'public');

        return response()->json([
            'path' => $path,
            'url' => Media::url($path),
        ], 201);
    }
}
