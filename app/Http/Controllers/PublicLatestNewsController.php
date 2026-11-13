<?php

namespace App\Http\Controllers;

use App\Models\LatestNews;
use Illuminate\View\View;

class PublicLatestNewsController extends Controller
{
    public function show(LatestNews $latestNews): View
    {
        return view('agropro.news-show', [
            'newsItem' => $latestNews->load('galleryImages'),
        ]);
    }
}
