<?php

namespace App\Http\Controllers;

use App\Models\VirtualQueue;
use Illuminate\View\View;

class PublicQueueController extends Controller
{
    public function show(VirtualQueue $virtualQueue): View
    {
        return view('pages.queue-public', [
            'virtualQueue' => $virtualQueue,
        ]);
    }
}
