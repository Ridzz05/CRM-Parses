<?php

namespace App\Http\Controllers;

use App\Services\OpenRouterParser;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OpenRouterDebugController extends Controller
{
    public function create(): View
    {
        return view('debug.openrouter', [
            'sampleText' => 'Transaksi Sebesar 100.000, Pembeliaan SaaS POS selama 1 Bulan + License untuk 10 User',
            'result' => null,
        ]);
    }

    public function store(Request $request, OpenRouterParser $parser): View
    {
        $data = $request->validate([
            'sample_text' => ['required', 'string', 'max:5000'],
        ]);

        return view('debug.openrouter', [
            'sampleText' => $data['sample_text'],
            'result' => $parser->diagnostics($data['sample_text']),
        ]);
    }
}
