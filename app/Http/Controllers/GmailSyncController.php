<?php

namespace App\Http\Controllers;

use App\Models\GmailAccount;
use App\Services\GmailService;
use Illuminate\Http\Request;

class GmailSyncController extends Controller
{
    public function sync(Request $request, GmailService $gmailService)
    {
        $user = $request->user();

        // Hanya sinkronkan akun milik user yang sedang login, supaya sync
        // user A tidak ikut menarik data akun user B.
        GmailAccount::ownedBy($user)
            ->chunk(20, function ($accounts) use ($gmailService) {
                foreach ($accounts as $account) {
                    $gmailService->sync($account);
                }
            });

        return response()->json([
            'success' => true,
            'message' => 'Email berhasil disinkronkan'
        ]);
    }
}
