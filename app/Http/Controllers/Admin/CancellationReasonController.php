<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class CancellationReasonController extends Controller
{
    private function getReasons(): array
    {
        $str = Setting::getValue('general', 'cancellation_reasons', 'Out of Stock,Customer Request,Fraudulent,Payment Failed,Other');
        return array_values(array_filter(array_map('trim', explode(',', $str))));
    }

    private function saveReasons(array $reasons): void
    {
        Setting::setValue('general', 'cancellation_reasons', implode(',', $reasons));
    }

    public function index()
    {
        $reasons = $this->getReasons();
        return view('admin.settings.cancellation-reasons', compact('reasons'));
    }

    public function store(Request $request)
    {
        $request->validate(['reason' => 'required|string|max:255']);
        $reasons = $this->getReasons();
        
        $newReason = trim($request->input('reason'));
        if (in_array(strtolower($newReason), array_map('strtolower', $reasons))) {
            return back()->with('error', 'Reason already exists.');
        }

        $reasons[] = $newReason;
        $this->saveReasons($reasons);

        return back()->with('success', 'Cancellation reason added successfully.');
    }

    public function update(Request $request, $id)
    {
        $request->validate(['reason' => 'required|string|max:255']);
        $reasons = $this->getReasons();

        if (!isset($reasons[$id])) {
            return back()->with('error', 'Reason not found.');
        }

        $newReason = trim($request->input('reason'));
        
        if (strtolower($reasons[$id]) === 'other' && strtolower($newReason) !== 'other') {
            return back()->with('error', 'The "Other" option cannot be renamed to something else.');
        }

        $lowerReasons = array_map('strtolower', $reasons);
        unset($lowerReasons[$id]);

        if (in_array(strtolower($newReason), $lowerReasons)) {
            return back()->with('error', 'Reason already exists.');
        }

        $reasons[$id] = $newReason;
        $this->saveReasons($reasons);

        return back()->with('success', 'Cancellation reason updated successfully.');
    }

    public function destroy($id)
    {
        $reasons = $this->getReasons();

        if (!isset($reasons[$id])) {
            return back()->with('error', 'Reason not found.');
        }

        if (strtolower($reasons[$id]) === 'other') {
            return back()->with('error', 'The "Other" option cannot be deleted.');
        }

        unset($reasons[$id]);
        $this->saveReasons(array_values($reasons));

        return back()->with('success', 'Cancellation reason deleted successfully.');
    }
}
