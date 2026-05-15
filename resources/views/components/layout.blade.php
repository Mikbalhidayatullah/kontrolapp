<!DOCTYPE html>
<html lang="id" class="h-full scroll-smooth bg-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/@tailwindplus/elements@1" type="module"></script>
    <title>{{ trim($title ?? 'Kontrol App') }}</title>
</head>
<body id="page-top" class="h-full text-slate-900">
    <div class="min-h-full">
        <x-navbar />

        <x-header>{{ $title }}</x-header>

        <main>
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                @if (session('status'))
                    <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                        <p class="font-semibold">Ada data yang perlu diperiksa kembali.</p>
                        <ul class="mt-2 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{ $slot }}
            </div>
        </main>

        <footer class="border-t border-slate-200 bg-white/70">
            <div class="mx-auto max-w-7xl px-4 py-4 text-center text-xs text-slate-500 sm:px-6 lg:px-8">
                @Copyright M.I.H 2026
            </div>
        </footer>
    </div>

    <a
        href="#page-top"
        class="inline-flex items-center justify-center text-white"
        aria-label="Kembali ke atas"
        title="Kembali ke atas"
        style="position: fixed; right: 20px; bottom: 20px; z-index: 9999; width: 48px; height: 48px; border-radius: 9999px; background: #0f172a; box-shadow: 0 10px 25px rgba(15, 23, 42, 0.25);"
    >
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" style="width: 22px; height: 22px;">
            <path d="M12 19V5" stroke-linecap="round" stroke-linejoin="round" />
            <path d="m5 12 7-7 7 7" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
    </a>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[data-auto-submit-filter]').forEach((form) => {
                let submitTimer = null;
                let isSubmitting = false;

                const submitForm = () => {
                    if (isSubmitting) {
                        return;
                    }

                    isSubmitting = true;
                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit();
                        return;
                    }

                    form.submit();
                };

                form.querySelectorAll('[data-auto-submit-control]').forEach((control) => {
                    const delay = Number(control.dataset.autoSubmitDelay || 0);

                    if (control.tagName === 'SELECT') {
                        control.addEventListener('change', submitForm);
                        return;
                    }

                    control.addEventListener('input', () => {
                        window.clearTimeout(submitTimer);
                        submitTimer = window.setTimeout(submitForm, delay || 350);
                    });

                    control.addEventListener('change', () => {
                        window.clearTimeout(submitTimer);
                        submitTimer = window.setTimeout(submitForm, delay || 0);
                    });
                });
            });
        });
    </script>
</body>
</html>
