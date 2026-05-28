<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Instruction;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $categoryStats = Transaction::query()
            ->select('category', DB::raw('count(*) as transactions_count'), DB::raw('coalesce(sum(amount), 0) as total_amount'))
            ->groupBy('category')
            ->orderByDesc('transactions_count')
            ->orderBy('category')
            ->limit(6)
            ->get();

        $topCategory = $categoryStats->first();

        return view('dashboard.index', [
            'stats' => [
                'customers' => Customer::count(),
                'transactions' => Transaction::count(),
                'categories' => Transaction::distinct('category')->count('category'),
                'total_amount' => (float) Transaction::sum('amount'),
                'top_category' => $topCategory?->category,
                'top_category_count' => (int) ($topCategory?->transactions_count ?? 0),
                'pending' => Instruction::whereIn('status', [Instruction::STATUS_PENDING, Instruction::STATUS_PROCESSING])->count(),
                'failed' => Instruction::where('status', Instruction::STATUS_FAILED)->count(),
            ],
            'categoryStats' => $categoryStats,
            'instructions' => Instruction::latest()->limit(10)->get(),
            'transactions' => Transaction::with('customer')->latest()->limit(10)->get(),
            'customers' => Customer::withCount('transactions')->latest()->limit(10)->get(),
        ]);
    }
}
