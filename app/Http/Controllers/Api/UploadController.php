<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UploadController extends Controller
{
    public function resume(Request $request)
    {
        try {
            $request->validate([
                'resume' => 'required|file|mimes:pdf,doc,docx|max:5120',
            ]);

            $path = $request->file('resume')->store('resumes', 'public');

            return response()->json([
                'success' => true,
                'message' => 'Resume uploaded successfully.',
                'data'    => [
                    'url'  => asset('storage/' . $path),
                    'path' => $path,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Upload failed.',
                'data'    => null,
            ], 500);
        }
    }
}
