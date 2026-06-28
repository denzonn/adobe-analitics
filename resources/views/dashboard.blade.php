<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Adobe Mail Center</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, .15);
            border-radius: 999px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, .30);
        }
    </style>
</head>

<body class="bg-[#070B1A] text-white min-h-screen">

    {{-- Background Glow --}}
    <div class="fixed inset-0 -z-10 overflow-hidden">
        <div class="absolute top-[-300px] left-[-300px] w-[800px] h-[800px] bg-blue-600/20 rounded-full blur-[220px]">
        </div>
        <div
            class="absolute bottom-[-300px] right-[-300px] w-[800px] h-[800px] bg-purple-600/20 rounded-full blur-[220px]">
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 md:px-6 py-6">

        {{-- HEADER --}}
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl md:text-4xl font-bold">Adobe Mail Center</h1>
                <p class="text-slate-400 mt-2">Ringkasan submission & penjualan Adobe Stock dari semua akun terhubung.
                </p>
            </div>

            <div class="flex flex-col sm:flex-row gap-3 w-full lg:w-auto">
                <input type="text" placeholder="Cari email..."
                    class="w-full sm:w-72 bg-white/5 border border-white/10 rounded-2xl px-4 py-3 outline-none focus:border-blue-500">

                <div class="flex gap-3">
                    <button id="syncBtn" class="px-5 py-3 rounded-2xl bg-green-600 hover:bg-green-700 text-center">
                        <span id="syncText">Sync</span>
                    </button>

                    <a href="{{ route('google.redirect') }}"
                        class="px-5 py-3 rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-600 text-center">
                        + Gmail
                    </a>
                </div>
            </div>
        </div>

        {{-- GRAND TOTAL --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
            <div class="bg-white/5 border border-white/10 rounded-3xl p-5">
                <div class="text-slate-400 text-sm">Total Aset</div>
                <div class="text-3xl font-bold mt-2">{{ number_format($grandTotal['total_assets']) }}</div>
                <div class="text-xs text-slate-500 mt-1">Akumulasi submission</div>
            </div>
            <div class="bg-white/5 border border-white/10 rounded-3xl p-5">
                <div class="text-slate-400 text-sm">Diterima</div>
                <div class="text-3xl font-bold mt-2 text-emerald-400">{{ number_format($grandTotal['accepted_count']) }}
                </div>
                <div class="text-xs text-slate-500 mt-1">Accepted</div>
            </div>
            <div class="bg-white/5 border border-white/10 rounded-3xl p-5">
                <div class="text-slate-400 text-sm">Ditolak</div>
                <div class="text-3xl font-bold mt-2 text-rose-400">{{ number_format($grandTotal['rejected_count']) }}
                </div>
                <div class="text-xs text-slate-500 mt-1">Weren't accepted</div>
            </div>
            <div class="bg-white/5 border border-white/10 rounded-3xl p-5">
                <div class="text-slate-400 text-sm">Pending</div>
                <div class="text-3xl font-bold mt-2 text-amber-400">{{ number_format($grandTotal['pending_count']) }}
                </div>
                <div class="text-xs text-slate-500 mt-1">Pending Reminders</div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <div
                class="bg-gradient-to-br from-emerald-500/15 to-emerald-700/5 border border-emerald-500/20 rounded-3xl p-5">
                <div class="text-emerald-300 text-sm">Penjualan Hari Ini</div>
                <div class="text-3xl font-bold mt-2">${{ number_format($grandTotal['earnings_today'], 2) }}</div>
                <div class="text-xs text-slate-500 mt-1">Update harian</div>
            </div>
            <div class="bg-gradient-to-br from-blue-500/15 to-blue-700/5 border border-blue-500/20 rounded-3xl p-5">
                <div class="text-blue-300 text-sm">Minggu Ini</div>
                <div class="text-3xl font-bold mt-2">${{ number_format($grandTotal['earnings_this_week'], 2) }}</div>
                <div class="text-xs text-slate-500 mt-1">7 hari terakhir</div>
            </div>
            <div
                class="bg-gradient-to-br from-indigo-500/15 to-indigo-700/5 border border-indigo-500/20 rounded-3xl p-5">
                <div class="text-indigo-300 text-sm">Bulan Ini</div>
                <div class="text-3xl font-bold mt-2">${{ number_format($grandTotal['earnings_this_month'], 2) }}</div>
                <div class="text-xs text-slate-500 mt-1">Sejak tanggal 1</div>
            </div>
            <div
                class="bg-gradient-to-br from-purple-500/15 to-purple-700/5 border border-purple-500/20 rounded-3xl p-5">
                <div class="text-purple-300 text-sm">Total Penjualan</div>
                <div class="text-3xl font-bold mt-2">${{ number_format($grandTotal['earnings_total'], 2) }}</div>
                <div class="text-xs text-slate-500 mt-1">Akumulasi seluruh akun</div>
            </div>
        </div>

        {{-- ACCOUNT CARDS --}}
        <div class="mb-8">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-semibold text-lg">Statistik per Akun</h2>
                <span class="text-sm text-slate-400">{{ $grandTotal['accounts'] }} Akun terhubung</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                @forelse($accounts as $account)
                    @php $stats = $account->stats; @endphp
                    <a href="?account_id={{ $account->id }}"
                        class="relative bg-white/5 border {{ request('account_id') == $account->id ? 'border-blue-500 bg-blue-500/10' : 'border-white/10' }} rounded-3xl p-5 transition hover:bg-white/10">

                        @if ($account->unread_count > 0)
                            <span class="absolute top-4 right-4 bg-red-500 text-white text-xs px-2 py-1 rounded-full">
                                {{ $account->unread_count }} unread
                            </span>
                        @endif

                        <div class="flex items-center gap-4">
                            <div
                                class="w-12 h-12 rounded-2xl bg-gradient-to-r from-blue-500 to-indigo-500 flex items-center justify-center font-bold">
                                {{ strtoupper(substr($account->email, 0, 1)) }}
                            </div>
                            <div class="flex-1 min-w-0 pr-20">
                                <div class="font-semibold truncate" title="{{ $account->email }}">
                                    {{ $account->email }}
                                </div>

                                <div class="text-slate-400 text-xs">
                                    {{ $account->emails_count }} email tersinkron
                                </div>
                            </div>
                        </div>

                        <div class="mt-5">
                            <div class="text-xs uppercase tracking-wider text-slate-500 mb-2">Submission Terbaru</div>
                            <div class="grid grid-cols-3 gap-2 text-center">
                                <div class="bg-emerald-500/10 border border-emerald-500/20 rounded-xl py-2">
                                    <div class="text-lg font-bold text-emerald-300">{{ $stats['accepted_count'] }}
                                    </div>
                                    <div class="text-[10px] text-slate-400 uppercase">Accepted</div>
                                </div>
                                <div class="bg-amber-500/10 border border-amber-500/20 rounded-xl py-2">
                                    <div class="text-lg font-bold text-amber-300">{{ $stats['pending_count'] }}</div>
                                    <div class="text-[10px] text-slate-400 uppercase">Pending</div>
                                </div>
                                <div class="bg-rose-500/10 border border-rose-500/20 rounded-xl py-2">
                                    <div class="text-lg font-bold text-rose-300">{{ $stats['rejected_count'] }}</div>
                                    <div class="text-[10px] text-slate-400 uppercase">Rejected</div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 pt-4 border-t border-white/5">
                            <div class="flex items-center justify-between">
                                <div class="text-xs text-slate-400">Penjualan Bulan Ini</div>
                                <div class="text-lg font-bold text-emerald-300">
                                    ${{ number_format($stats['earnings_this_month'], 2) }}
                                </div>
                            </div>
                            <div class="flex items-center justify-between mt-1">
                                <div class="text-xs text-slate-400">Total Penjualan</div>
                                <div class="text-sm font-semibold text-slate-200">
                                    ${{ number_format($stats['earnings_total'], 2) }}
                                </div>
                            </div>
                            @if ($stats['latest_earning_at'])
                                <div class="text-[10px] text-slate-500 mt-2">
                                    Laporan terakhir: {{ $stats['latest_earning_at']->diffForHumans() }}
                                </div>
                            @endif
                        </div>
                    </a>
                @empty
                    <div
                        class="col-span-3 bg-white/5 border border-white/10 rounded-3xl p-8 text-center text-slate-400">
                        Belum ada akun Gmail yang terhubung.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="syncLoading" class="hidden fixed inset-0 z-[9999] bg-black/70 backdrop-blur-md">
        <div class="h-full flex items-center justify-center">
            <div class="bg-[#0F172A] border border-white/10 rounded-3xl p-8 text-center w-[320px]">
                <div class="relative mx-auto w-20 h-20">
                    <div class="absolute inset-0 rounded-full border-4 border-blue-500/20"></div>
                    <div
                        class="absolute inset-0 rounded-full border-4 border-transparent border-t-blue-500 animate-spin">
                    </div>
                </div>
                <h3 class="mt-6 text-xl font-semibold">Syncing Gmail</h3>
                <p class="text-slate-400 mt-2 text-sm">Mengambil email Adobe dari semua akun...</p>
                <div class="mt-6">
                    <div class="h-2 bg-white/10 rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-blue-500 to-indigo-500 animate-pulse w-full"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const syncBtn = document.getElementById('syncBtn');
        const loading = document.getElementById('syncLoading');

        syncBtn?.addEventListener('click', async function() {
            loading.classList.remove('hidden');
            syncBtn.disabled = true;

            try {
                const response = await fetch("{{ route('emails.sync') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });
                const result = await response.json();

                if (result.success) {
                    loading.innerHTML = `
<div class="h-full flex items-center justify-center">
    <div class="bg-[#0F172A] rounded-3xl p-8 text-center">
        <div class="text-5xl">✅</div>
        <h3 class="mt-4 text-xl font-semibold">Sync Berhasil</h3>
        <p class="text-slate-400 mt-2">Email Adobe berhasil diperbarui</p>
    </div>
</div>`;
                    setTimeout(() => window.location.reload(), 1500);
                }
            } catch (e) {
                loading.classList.add('hidden');
                syncBtn.disabled = false;
                alert('Gagal melakukan sync');
            }
        });
    </script>
</body>

</html>
