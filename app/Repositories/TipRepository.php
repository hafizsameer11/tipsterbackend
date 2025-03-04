<?php

namespace App\Repositories;

use App\Models\Tip;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;

class TipRepository
{
    public function all() {}

    public function find($id)
    {
        // Add logic to find data by ID
    }

    public function create(array $data)
    {
        $user = Auth::user();
        $data['user_id'] = $user->id;
        return Tip::create($data);
    }
    public function getFreeTipofUser($userId)
    {
        $user = User::findOrFail($userId);
        if (!$user) {
            throw new Exception('User not found.');
        }

        $tips = Tip::where('user_id', $userId)->with('bettingCompany')
            ->orderBy('created_at', 'desc')
            ->get();

        $totalTips = $tips->count();
        $lostTips = $tips->where('result', 'loss')->count();
        $winRate = $totalTips > 0 ? round((($totalTips - $lostTips) / $totalTips) * 100, 2) : 0;
        $lastFiveResults = $tips->take(5)->pluck('result')->map(function ($result) {
            return strtoupper(substr($result, 0, 1)); // Extract first letter and convert to uppercase
        })->toArray();
        $tipsWithUser = $tips->map(function ($tip) use ($user, $winRate, $lastFiveResults) {
            return array_merge($tip->toArray(), [
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'profile_picture' => $user->profile_picture ?? null,
                    'win_rate' => $winRate . '%',
                    'last_five' => $lastFiveResults,
                ],
            ]);
        });

        return $tipsWithUser;
    }
    public function getAllTips()
    {
        $tips = Tip::with('bettingCompany', 'user')->orderBy('created_at', 'desc')->get();
        $formattedTips = $tips->map(function ($tip) {
            // Fetch all tips of the user
            $userTotalTips = Tip::where('user_id', $tip->user_id)->count();
            $userWonTips = Tip::where('user_id', $tip->user_id)->where('status', 'won')->count();

            // Calculate win rate percentage
            $winRate = $userTotalTips > 0 ? round(($userWonTips / $userTotalTips) * 100) : 0;

            return [
                "img" => asset('/storage/' . $tip->user->profile_picture), // Full URL of profile image
                "name" => $tip->user->username,
                "Walletimg" => asset('/storage/' .  $tip->bettingCompany->logo), // Full URL of betting company logo
                "WalletName" => "Bitcoin Wallet", // Modify if needed
                "odds" => $tip->ods,
                "code" => $tip->codes,
                "winRate" => (string) $winRate, // Convert to string as required
                "date" => $tip->match_date, // Ensure correct date format
                "status" => ucfirst($tip->status), // Capitalizing first letter
                "approval" => $tip->status === "approved",
                "id" => $tip->id, // Formatting ID
                "category" => $tip->betting_category,
                "bettingCompany" => $tip->bettingCompany->title,
                "bettingCompanyImage" => asset('/storage/' . $tip->bettingCompany->logo),
            ];
        });
        return $tips;
    }

    public function getAllRunningTips()
    {
        $tips = Tip::where('result', 'running')
            ->with('user') // Eager load user data
            ->where('status', 'approved')
            ->orderBy('created_at', 'desc')
            ->get();

        $groupedTips = $tips->groupBy('user_id')->map(function ($userTips) {
            $user = $userTips->first()->user;

            // Fetch ALL tips of this user (not just running ones)
            $allTips = Tip::where('user_id', $user->id)->orderBy('created_at', 'desc')->get();
            $totalTips = $allTips->count();
            $lostTips = $allTips->where('result', 'loss')->count();
            $winRate = $totalTips > 0 ? round((($totalTips - $lostTips) / $totalTips) * 100, 2) : 0;

            // Get last 5 tip results
            $lastFiveResults = $allTips->take(5)->pluck('result')->map(function ($result) {
                return strtoupper(substr($result, 0, 1)); // Extract first letter and convert to uppercase
            })->toArray();

            // Attach user details to each tip
            $tipsWithUser = $userTips->map(function ($tip) use ($user, $winRate, $lastFiveResults) {
                return array_merge($tip->toArray(), [
                    'user' => [
                        'id' => $user->id,
                        'username' => $user->username,
                        'profile_picture' => $user->profile_picture ?? null,
                        'win_rate' => $winRate . '%',
                        'last_five' => $lastFiveResults,
                    ],
                ]);
            });

            return $tipsWithUser;
        });

        return $groupedTips->values()->flatten(1); // Flatten to avoid nested arrays
    }



    public function approveTip($tipId)
    {
        $tip = Tip::findOrFail($tipId);
        if (!$tip) {
            throw new Exception('Tip not found.');
        }
        $tip->status = 'approved';
        $tip->save();

        return $tip;
    }
    public function setTipResult($tipId, $result)
    {
        $tip = Tip::findOrFail($tipId);
        if (!$tip) {
            throw new Exception('Tip not found.');
        }
        $tip->result = $result;
        $tip->save();

        return $tip;
    }
    public function update($id, array $data)
    {
        $tip = Tip::findOrFail($id);
        if (!$tip) {
            throw new Exception('Tip not found.');
        }
        $tip->update($data);

        return $tip;
    }

    public function delete($id)
    {
        // Add logic to delete data
    }
}
