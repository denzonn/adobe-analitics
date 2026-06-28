<?php

namespace App\Http\Controllers;

use App\Models\GmailAccount;
use App\Services\GmailService;

class GmailSyncController extends Controller
{
    public function sync(GmailService $gmailService)
    {
        GmailAccount::chunk(20, function ($accounts) use ($gmailService) {

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
