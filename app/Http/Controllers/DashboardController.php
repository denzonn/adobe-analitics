<?php

namespace App\Http\Controllers;

use App\Models\Email;
use App\Models\GmailAccount;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $selectedAccountId = $request->account_id;
        $selectedEmailId = $request->email_id;

        $accounts = GmailAccount::withCount([
            'emails',
            'emails as unread_count' => function ($query) {
                $query->where('is_read', false);
            }
        ])->get();

        // Hitung statistik per akun dari email yang sudah di-parse.
        $accountStats = $this->buildAccountStats($accounts->pluck('id')->all());

        // Tambahkan data statistik ke setiap akun agar view tinggal render.
        $accounts->each(function (GmailAccount $account) use ($accountStats) {
            $stats = $accountStats->get($account->id, $this->emptyStats());
            $account->stats = $stats;
        });

        // Grand total (untuk kartu ringkasan atas).
        $grandTotal = $this->aggregateGrandTotal($accountStats);

        $emails = Email::query()
            ->when($selectedAccountId, function ($query) use ($selectedAccountId) {
                $query->where('gmail_account_id', $selectedAccountId);
            })
            ->latest('received_at')
            ->get();

        $selectedEmail = $selectedEmailId
            ? Email::find($selectedEmailId)
            : $emails->first();

        return view('dashboard', compact(
            'accounts',
            'emails',
            'selectedEmail',
            'grandTotal'
        ));
    }

    public function show(Email $email)
    {
        if (!$email->is_read) {
            $email->update([
                'is_read' => true
            ]);
        }

        return response()->json([
            'id'          => $email->id,
            'subject'     => $email->subject,
            'sender'      => $email->sender,
            'received_at' => optional($email->received_at)->format('d M Y H:i'),
            'body'        => $email->body,
            'email_type'  => $email->email_type,
        ]);
    }

    /**
     * Bangun map [account_id => stats] dari tabel emails.
     */
    private function buildAccountStats(array $accountIds): Collection
    {
        if (empty($accountIds)) {
            return collect();
        }

        $now = Carbon::now();

        // Tarik hanya kolom yang relevan untuk efisiensi.
        $rows = Email::query()
            ->whereIn('gmail_account_id', $accountIds)
            ->whereIn('email_type', [
                Email::TYPE_SUBMISSION_UPDATE,
                Email::TYPE_EARNINGS_REPORT,
            ])
            ->orderByDesc('received_at')
            ->get([
                'id',
                'gmail_account_id',
                'email_type',
                'accepted_count',
                'pending_count',
                'rejected_count',
                'earnings_amount',
                'earnings_currency',
                'received_at',
            ]);


        $grouped = $rows->groupBy('gmail_account_id');

        return collect($accountIds)->mapWithKeys(function ($id) use ($grouped, $now) {
            $emails = $grouped->get($id, collect());

            $stats = $this->emptyStats();

            // Submission update: jumlahkan seluruh snapshot dari email Adobe
            // Stock (tiap email = delta/jumlah saat email itu dikirim), tanpa
            // memfilter berdasarkan tanggal, sehingga total per akun =
            // Σ accepted / pending / rejected dari semua email submission.
            $submissions = $emails
                ->where('email_type', Email::TYPE_SUBMISSION_UPDATE);

            foreach ($submissions as $row) {
                $stats['accepted_count'] += (int) ($row->accepted_count ?? 0);
                $stats['pending_count']  += (int) ($row->pending_count ?? 0);
                $stats['rejected_count'] += (int) ($row->rejected_count ?? 0);

                $received = $row->received_at instanceof Carbon
                    ? $row->received_at
                    : ($row->received_at ? Carbon::parse($row->received_at) : null);

                if ($received && (
                    !$stats['latest_submission_at']
                    || $received->gt($stats['latest_submission_at'])
                )) {
                    $stats['latest_submission_at'] = $received;
                }
            }

            $stats['total_assets'] = $stats['accepted_count']
                + $stats['pending_count']
                + $stats['rejected_count'];

            // Earnings report: agregasi hari ini, bulan ini, dan total.
            $earnings = $emails->where('email_type', Email::TYPE_EARNINGS_REPORT);

            foreach ($earnings as $row) {
                $amount = (float) ($row->earnings_amount ?? 0);
                if ($amount <= 0) {
                    continue;
                }

                $received = $row->received_at instanceof Carbon
                    ? $row->received_at
                    : Carbon::parse($row->received_at);

                $stats['earnings_total'] += $amount;

                if ($received->isSameDay($now)) {
                    $stats['earnings_today'] += $amount;
                }
                if ($received->isSameMonth($now) && $received->isSameYear($now)) {
                    $stats['earnings_this_month'] += $amount;
                }
                if ($received->isSameWeek($now)) {
                    $stats['earnings_this_week'] += $amount;
                }

                if (!$stats['latest_earning_at'] || $received->gt($stats['latest_earning_at'])) {
                    $stats['latest_earning_at'] = $received;
                }

                // Pakai currency dari entry terbaru yang punya nilai.
                if ($row->earnings_currency) {
                    $stats['earnings_currency'] = $row->earnings_currency;
                }
            }

            $stats['earnings_count'] = $earnings->count();

            return [$id => $stats];
        });
    }

    /**
     * Struktur stats kosong untuk satu akun.
     */
    private function emptyStats(): array
    {
        return [
            'accepted_count'         => 0,
            'pending_count'          => 0,
            'rejected_count'         => 0,
            'total_assets'           => 0,
            'earnings_today'         => 0.0,
            'earnings_this_week'     => 0.0,
            'earnings_this_month'    => 0.0,
            'earnings_total'         => 0.0,
            'earnings_count'         => 0,
            'earnings_currency'      => 'USD',
            'latest_submission_at'   => null,
            'latest_earning_at'      => null,
        ];
    }

    /**
     * Akumulasi seluruh akun untuk kartu ringkasan atas dashboard.
     */
    private function aggregateGrandTotal(Collection $accountStats): array
    {
        $totals = [
            'accounts'            => $accountStats->count(),
            'total_assets'        => 0,
            'accepted_count'      => 0,
            'pending_count'       => 0,
            'rejected_count'      => 0,
            'earnings_today'      => 0.0,
            'earnings_this_week'  => 0.0,
            'earnings_this_month' => 0.0,
            'earnings_total'      => 0.0,
        ];

        foreach ($accountStats as $stats) {
            $totals['total_assets']   += $stats['total_assets'];
            $totals['accepted_count'] += $stats['accepted_count'];
            $totals['pending_count']  += $stats['pending_count'];
            $totals['rejected_count'] += $stats['rejected_count'];
            $totals['earnings_today']      += $stats['earnings_today'];
            $totals['earnings_this_week']  += $stats['earnings_this_week'];
            $totals['earnings_this_month'] += $stats['earnings_this_month'];
            $totals['earnings_total']      += $stats['earnings_total'];
        }

        return $totals;
    }
}
