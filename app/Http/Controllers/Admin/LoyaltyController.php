<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyReward;
use App\Models\LoyaltyTier;
use App\Models\LoyaltyTransaction;
use App\Models\LoyaltyRedemption;
use App\Models\User;
use App\Models\Product;
use App\Services\LoyaltyService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LoyaltyController extends Controller
{
    public function __construct(
        protected LoyaltyService $loyaltyService
    ) {}

    /**
     * Loyalty dashboard
     */
    public function index()
    {
        $stats = [
            'total_members' => User::where('lifetime_points', '>', 0)->count(),
            'total_points_issued' => LoyaltyTransaction::where('points', '>', 0)->sum('points'),
            'total_points_redeemed' => abs(LoyaltyTransaction::where('type', 'redeemed')->sum('points')),
            'active_rewards' => LoyaltyReward::active()->count(),
            'pending_redemptions' => LoyaltyRedemption::pending()->count(),
        ];

        // Tier stats for the sidebar
        $tierStats = User::where('lifetime_points', '>', 0)
            ->selectRaw('loyalty_tier as tier, COUNT(*) as count')
            ->groupBy('loyalty_tier')
            ->get();

        $recentTransactions = LoyaltyTransaction::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $topEarners = User::where('lifetime_points', '>', 0)
            ->orderBy('lifetime_points', 'desc')
            ->limit(10)
            ->get();

        // Chart data for points activity over last 30 days
        $chartData = $this->getPointsChartData();

        return view('admin.loyalty.index', compact('stats', 'tierStats', 'recentTransactions', 'topEarners', 'chartData'));
    }

    /**
     * Get points chart data for last 30 days
     */
    protected function getPointsChartData(): array
    {
        $labels = [];
        $earned = [];
        $redeemed = [];

        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $date->format('M d');

            $earned[] = LoyaltyTransaction::whereDate('created_at', $date)
                ->where('points', '>', 0)
                ->sum('points');

            $redeemed[] = abs(LoyaltyTransaction::whereDate('created_at', $date)
                ->where('type', 'redeemed')
                ->sum('points'));
        }

        return [
            'labels' => $labels,
            'earned' => $earned,
            'redeemed' => $redeemed,
        ];
    }

    /**
     * Rewards management
     */
    public function rewards()
    {
        $rewards = LoyaltyReward::orderBy('points_required')->paginate(15);

        return view('admin.loyalty.rewards.index', compact('rewards'));
    }

    public function createReward()
    {
        $products = Product::where('is_active', true)->orderBy('name')->get();
        $tiers = LoyaltyTier::orderBy('min_points')->get();

        return view('admin.loyalty.rewards.form', compact('products', 'tiers'));
    }

    public function storeReward(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'points_required' => 'required|integer|min:1',
            'reward_type' => 'required|in:discount_percentage,discount_fixed,free_shipping,free_product,coupon',
            'reward_value' => 'required|numeric|min:0',
            'product_id' => 'nullable|exists:products,id',
            'quantity_available' => 'nullable|integer|min:1',
            'per_user_limit' => 'integer|min:1',
            'is_active' => 'boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
        ]);

        LoyaltyReward::create($validated);

        return redirect()
            ->route('admin.loyalty.rewards.index')
            ->with('success', 'Reward created successfully!');
    }

    public function editReward(LoyaltyReward $reward)
    {
        $products = Product::where('is_active', true)->orderBy('name')->get();
        $tiers = LoyaltyTier::orderBy('min_points')->get();

        return view('admin.loyalty.rewards.form', compact('reward', 'products', 'tiers'));
    }

    public function updateReward(Request $request, LoyaltyReward $reward)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'points_required' => 'required|integer|min:1',
            'reward_type' => 'required|in:discount_percentage,discount_fixed,free_shipping,free_product,coupon',
            'reward_value' => 'required|numeric|min:0',
            'product_id' => 'nullable|exists:products,id',
            'quantity_available' => 'nullable|integer|min:1',
            'per_user_limit' => 'integer|min:1',
            'is_active' => 'boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
        ]);

        $reward->update($validated);

        return redirect()
            ->route('admin.loyalty.rewards.index')
            ->with('success', 'Reward updated successfully!');
    }

    public function destroyReward(LoyaltyReward $reward)
    {
        $reward->delete();

        return redirect()
            ->route('admin.loyalty.rewards.index')
            ->with('success', 'Reward deleted successfully!');
    }

    /**
     * Tiers management
     */
    public function tiers()
    {
        $tiers = LoyaltyTier::orderBy('min_points')->get();

        return view('admin.loyalty.tiers.index', compact('tiers'));
    }

    public function createTier()
    {
        return view('admin.loyalty.tiers.form');
    }

    public function storeTier(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'min_points' => 'required|integer|min:0',
            'points_multiplier' => 'required|numeric|min:1|max:5',
            'birthday_bonus' => 'integer|min:0',
            'free_shipping' => 'boolean',
            'exclusive_discount' => 'integer|min:0|max:100',
            'benefits' => 'nullable|string',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        LoyaltyTier::create($validated);

        return redirect()
            ->route('admin.loyalty.tiers.index')
            ->with('success', 'Tier created successfully!');
    }

    public function editTier(LoyaltyTier $tier)
    {
        return view('admin.loyalty.tiers.form', compact('tier'));
    }

    public function updateTier(Request $request, LoyaltyTier $tier)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'min_points' => 'required|integer|min:0',
            'points_multiplier' => 'required|numeric|min:1|max:5',
            'birthday_bonus' => 'integer|min:0',
            'free_shipping' => 'boolean',
            'exclusive_discount' => 'integer|min:0|max:100',
            'benefits' => 'nullable|string',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $tier->update($validated);

        return redirect()
            ->route('admin.loyalty.tiers.index')
            ->with('success', 'Tier updated successfully!');
    }

    public function destroyTier(LoyaltyTier $tier)
    {
        $tier->delete();

        return redirect()
            ->route('admin.loyalty.tiers.index')
            ->with('success', 'Tier deleted successfully!');
    }

    /**
     * Members management
     */
    public function members(Request $request)
    {
        $query = User::where('lifetime_points', '>', 0);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('tier')) {
            $query->where('loyalty_tier', $request->tier);
        }

        $members = $query->orderBy('lifetime_points', 'desc')->paginate(20);

        $tiers = LoyaltyTier::orderBy('min_points')->get();

        return view('admin.loyalty.members.index', compact('members', 'tiers'));
    }

    public function showMember(User $user)
    {
        $user->load(['loyaltyTransactions', 'loyaltyRedemptions.reward']);

        $transactions = LoyaltyTransaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.loyalty.members.show', [
            'member' => $user,
            'transactions' => $transactions,
        ]);
    }

    public function adjustPoints(Request $request, User $user)
    {
        $validated = $request->validate([
            'points' => 'required|integer',
            'reason' => 'required|string|max:255',
        ]);

        if ($validated['points'] > 0) {
            $this->loyaltyService->addPoints(
                $user,
                $validated['points'],
                LoyaltyTransaction::TYPE_ADJUSTED,
                "Admin adjustment: {$validated['reason']}"
            );
        } else {
            $this->loyaltyService->deductPoints(
                $user,
                abs($validated['points']),
                LoyaltyTransaction::TYPE_ADJUSTED,
                "Admin adjustment: {$validated['reason']}"
            );
        }

        return back()->with('success', 'Points adjusted successfully!');
    }

    /**
     * Redemptions management
     */
    public function redemptions(Request $request)
    {
        $query = LoyaltyRedemption::with(['user', 'reward']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $redemptions = $query->orderBy('created_at', 'desc')->paginate(20);
        
        $rewards = LoyaltyReward::orderBy('name')->get();

        return view('admin.loyalty.redemptions', compact('redemptions', 'rewards'));
    }

    /**
     * Transactions report
     */
    public function transactions(Request $request)
    {
        $query = LoyaltyTransaction::with('user');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $transactions = $query->orderBy('created_at', 'desc')->paginate(50);

        return view('admin.loyalty.transactions', compact('transactions'));
    }

    /**
     * Export members
     */
    public function exportMembers()
    {
        $members = User::where('lifetime_points', '>', 0)
            ->orderBy('lifetime_points', 'desc')
            ->get();

        $filename = 'loyalty_members_' . now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($members) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Name', 'Email', 'Current Points', 'Lifetime Points', 'Tier', 'Joined']);

            foreach ($members as $member) {
                fputcsv($file, [
                    $member->name,
                    $member->email,
                    $member->loyalty_points,
                    $member->lifetime_points,
                    $member->loyalty_tier,
                    $member->created_at->toDateString(),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
