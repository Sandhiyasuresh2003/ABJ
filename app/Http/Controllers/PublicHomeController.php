<?php

namespace App\Http\Controllers;

use App\Models\AboutUsPageSetting;
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
        ]);
    }
}
