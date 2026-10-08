<?php

namespace App\Http\Controllers;

use App\Models\VirtualQueue;
use Illuminate\View\View;

class QueueController extends Controller
{
    public function show(VirtualQueue $virtualQueue): View
    {
        return view('pages.queue-show', [
            'virtualQueue' => $virtualQueue,
        ]);
    }
}
