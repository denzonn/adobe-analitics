<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $email->subject }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-[#070B1A] text-white min-h-screen">

    {{-- Background Glow --}}
    <div class="fixed inset-0 -z-10 overflow-hidden">

        <div
            class="absolute top-[-300px] left-[-300px]
            w-[800px] h-[800px]
            bg-blue-600/20 rounded-full blur-[220px]">
        </div>

        <div
            class="absolute bottom-[-300px] right-[-300px]
            w-[800px] h-[800px]
            bg-purple-600/20 rounded-full blur-[220px]">
        </div>

    </div>

    <div class="max-w-5xl mx-auto p-4 md:p-8">

        {{-- Back --}}
        <div class="mb-6">

            <a href="{{ route('dashboard') }}"
                class="inline-flex items-center gap-2
                px-4 py-2 rounded-xl
                bg-white/5 border border-white/10
                hover:bg-white/10">

                ← Kembali

            </a>

        </div>

        {{-- Email Header --}}
        <div class="bg-white/5
            border border-white/10
            rounded-3xl
            overflow-hidden">

            <div class="p-6 border-b border-white/10">

                <div class="flex items-start justify-between gap-4">

                    <div>

                        <h1 class="text-2xl md:text-3xl font-bold">

                            {{ $email->subject }}

                        </h1>

                        <div class="mt-4 space-y-1 text-slate-400">

                            <div>

                                <span class="text-white">
                                    From:
                                </span>

                                {{ $email->sender }}

                            </div>

                            <div>

                                <span class="text-white">
                                    Date:
                                </span>

                                {{ $email->received_at
                                    ? \Carbon\Carbon::parse($email->received_at)->format('d M Y H:i')
                                    : $email->created_at->format('d M Y H:i') }}

                            </div>

                        </div>

                    </div>

                    <span
                        class="px-3 py-1
                        rounded-full
                        bg-green-500/20
                        border border-green-500/20
                        text-green-400 text-sm">

                        Read

                    </span>

                </div>

            </div>

            {{-- Email Body --}}
            <div class="p-6
                prose prose-invert
                max-w-none">

                @if ($email->body)
                    {!! $email->body !!}
                @else
                    <div class="text-slate-400">

                        {{ $email->snippet }}

                    </div>
                @endif

            </div>

        </div>

    </div>

</body>

</html>
