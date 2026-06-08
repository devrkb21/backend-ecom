<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;

class CourierController extends Controller
{
    public function index()
    {
        $steadfastEnabled = filter_var(Setting::getValue('courier', 'steadfast_enabled', '0'), FILTER_VALIDATE_BOOLEAN);

        return view('admin.settings.couriers.index', compact('steadfastEnabled'));
    }
}
