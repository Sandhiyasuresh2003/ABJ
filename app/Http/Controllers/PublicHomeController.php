<?php

namespace App\Http\Controllers;

use App\Models\AboutUsPageSetting;
use App\Models\BannerImage;
use App\Models\ClientTestimonial;
use App\Models\LatestNews;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicHomeController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        if ($request->user()) {
            return redirect()->route($request->user()->role === 'employee' ? 'employee.workspace' : 'dashboard');
        }

        return view('agropro.index', [
            'aboutUsSettings' => AboutUsPageSetting::query()->find(1),
            'bannerImages' => BannerImage::query()->orderBy('position')->orderBy('id')->get(),
            'services' => Service::query()->orderBy('position')->orderBy('id')->get(),
            'testimonialSlides' => ClientTestimonial::query()->orderBy('position')->orderBy('id')->get()->chunk(2),
            'latestNews' => LatestNews::query()->orderByDesc('published_at')->orderBy('position')->orderBy('id')->get(),
        ]);
    }
}
