<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaController extends Controller
{
    /**
     * Display the specified resource.
     */
    public function index(Media $media, Request $request)
    {
        return $media->toInlineResponse($request);
    }
}