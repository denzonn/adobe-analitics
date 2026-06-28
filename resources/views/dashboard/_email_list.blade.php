@forelse($emails as $email)
    @php
        $badgeClass = match ($email->email_type) {
            'submission_update' => 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30',
            'earnings_report' => 'bg-blue-500/20 text-blue-300 border border-blue-500/30',
            default => 'bg-white/10 text-slate-300 border border-white/10',
        };
        $badgeLabel = match ($email->email_type) {
            'submission_update' => 'Submission',
            'earnings_report' => 'Earnings',
            default => 'Adobe',
        };
    @endphp
    <a href="{{ $mobile ? route('emails.show', $email->id) : '?account_id=' . request('account_id') . '&email_id=' . $email->id }}"
        class="block p-4 border-b border-white/5 hover:bg-white/5 transition {{ !$mobile && request('email_id') == $email->id ? 'bg-blue-500/10 border-l-4 border-blue-500' : '' }}">

        <div class="flex gap-3">
            <div
                class="w-10 h-10 rounded-full bg-blue-500/20 flex items-center justify-center font-bold text-blue-300 shrink-0">
                A
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                    <div class="font-medium {{ $mobile ? 'truncate w-full' : 'text-sm truncate' }}"
                        title="{{ $email->subject }}">
                        {{ $email->subject }}
                    </div>
                </div>

                @if ($email->email_type === 'submission_update')
                    <div class="text-[11px] text-slate-400 mt-1">
                        <span class="text-emerald-300">{{ $email->accepted_count }}</span> /
                        <span class="text-amber-300">{{ $email->pending_count }}</span> /
                        <span class="text-rose-300">{{ $email->rejected_count }}</span>
                        (Accepted / Pending / Rejected)
                    </div>
                @elseif ($email->email_type === 'earnings_report' && $email->earnings_amount !== null)
                    <div class="text-[11px] text-slate-400 mt-1">
                        Penjualan:
                        <span class="text-emerald-300 font-semibold">
                            ${{ number_format((float) $email->earnings_amount, 2) }}
                        </span>
                    </div>
                @endif

                <div class="flex items-center justify-between mt-1">
                    <div class="text-slate-500 text-xs truncate" title="{{ $email->sender }}">{{ $email->sender }}
                    </div>
                    <span class="text-[10px] px-2 py-0.5 rounded-full {{ $badgeClass }}">{{ $badgeLabel }}</span>
                </div>

                <div class="text-slate-500 text-xs mt-2">
                    {{ optional($email->received_at)->diffForHumans() ?? $email->created_at->diffForHumans() }}
                </div>
            </div>
        </div>
    </a>
@empty
    <div class="p-8 text-center text-slate-500">Tidak ada email.</div>
@endforelse
