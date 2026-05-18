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
            <div data-page-content class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                @if (session('status'))
                    <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any() && ! request()->routeIs('add-perjadin', 'add-perjadin.store', 'perjadin.edit', 'perjadin.update'))
                    @php
                        $fieldLabels = [
                            'assignment number' => 'nomor surat tugas',
                            'signature location' => 'lokasi tanda tangan',
                            'start date' => 'tanggal mulai',
                            'end date' => 'tanggal selesai',
                            'assignment date' => 'tanggal surat tugas',
                            'category' => 'kategori perjadin',
                            'skpd name' => 'nama SKPD',
                            'executor name' => 'nama pelaksana',
                            'position name' => 'jabatan',
                            'echelon level' => 'eselon',
                            'grade' => 'golongan',
                            'destination city' => 'kota / kabupaten tujuan',
                            'destination regency' => 'kabupaten tujuan',
                            'destination district' => 'kecamatan tujuan',
                            'origin regency' => 'kabupaten asal',
                            'origin district' => 'kecamatan asal',
                            'regional trip scope' => 'jenis perjalanan dalam daerah',
                            'daily allowance days' => 'jumlah hari uang harian',
                            'daily allowance rate' => 'nominal uang harian',
                            'representation days' => 'jumlah hari representasi',
                            'representation rate' => 'nominal representasi',
                            'lodging nights' => 'jumlah malam penginapan',
                            'lodging rate' => 'nominal penginapan',
                            'ticket departure date' => 'tanggal berangkat tiket',
                            'ticket return date' => 'tanggal pulang tiket',
                            'ticket departure price' => 'harga tiket berangkat',
                            'ticket return price' => 'harga tiket kembali',
                            'ticket transport type' => 'jenis transport tiket',
                        ];

                        $humanizeError = function (string $error) use ($fieldLabels): string {
                            $message = trim($error);

                            foreach ($fieldLabels as $english => $indo) {
                                if (str_contains(strtolower($message), $english.' field is required')) {
                                    return 'Kolom '.ucfirst($indo).' masih perlu diisi.';
                                }
                            }

                            if (str_contains(strtolower($message), 'field is required')) {
                                $normalized = strtolower($message);
                                $field = trim(str_replace(' field is required.', '', $normalized));

                                return 'Kolom '.ucfirst($field).' masih perlu diisi.';
                            }

                            return $message;
                        };
                    @endphp
                    <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                        <p class="font-semibold">Masih ada beberapa bagian yang perlu dilengkapi dulu.</p>
                        <ul class="mt-2 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $humanizeError($error) }}</li>
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
            const submitTimers = new WeakMap();
            const loadingForms = new WeakSet();

            const replacePageContent = (html, url, pushHistory = true) => {
                const parser = new DOMParser();
                const nextDocument = parser.parseFromString(html, 'text/html');
                const nextContent = nextDocument.querySelector('[data-page-content]');
                const currentContent = document.querySelector('[data-page-content]');

                if (!nextContent || !currentContent) {
                    window.location.assign(url);
                    return;
                }

                const currentScrollY = window.scrollY;
                currentContent.innerHTML = nextContent.innerHTML;
                document.title = nextDocument.title || document.title;

                if (pushHistory) {
                    window.history.pushState({ url }, '', url);
                }

                window.requestAnimationFrame(() => {
                    window.scrollTo({ top: currentScrollY, behavior: 'auto' });
                });
            };

            const submitFilterForm = async (form) => {
                if (!form || loadingForms.has(form)) {
                    return;
                }

                loadingForms.add(form);

                const formData = new FormData(form);
                const searchParams = new URLSearchParams();

                for (const [key, value] of formData.entries()) {
                    searchParams.append(key, value.toString());
                }

                const action = form.getAttribute('action') || window.location.pathname;
                const url = `${action}${searchParams.toString() ? `?${searchParams.toString()}` : ''}`;

                try {
                    const response = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }

                    const html = await response.text();
                    replacePageContent(html, url, true);
                } catch (error) {
                    window.location.assign(url);
                } finally {
                    loadingForms.delete(form);
                }
            };

            document.addEventListener('change', (event) => {
                const control = event.target.closest('[data-auto-submit-control]');
                if (!control) {
                    return;
                }

                const form = control.closest('[data-auto-submit-filter]');
                if (!form) {
                    return;
                }

                if (control.tagName === 'SELECT') {
                    submitFilterForm(form);
                    return;
                }

                const delay = Number(control.dataset.autoSubmitDelay || 0);
                window.clearTimeout(submitTimers.get(form));
                const timer = window.setTimeout(() => submitFilterForm(form), delay || 0);
                submitTimers.set(form, timer);
            });

            document.addEventListener('input', (event) => {
                const control = event.target.closest('[data-auto-submit-control]');
                if (!control || control.tagName === 'SELECT') {
                    return;
                }

                const form = control.closest('[data-auto-submit-filter]');
                if (!form) {
                    return;
                }

                const delay = Number(control.dataset.autoSubmitDelay || 0);
                window.clearTimeout(submitTimers.get(form));
                const timer = window.setTimeout(() => submitFilterForm(form), delay || 350);
                submitTimers.set(form, timer);
            });

            document.addEventListener('submit', (event) => {
                const form = event.target.closest('[data-auto-submit-filter]');
                if (!form) {
                    return;
                }

                event.preventDefault();
                window.clearTimeout(submitTimers.get(form));
                submitFilterForm(form);
            });

            window.addEventListener('popstate', async () => {
                try {
                    const response = await fetch(window.location.href, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }

                    const html = await response.text();
                    replacePageContent(html, window.location.href, false);
                } catch (error) {
                    window.location.reload();
                }
            });
        });
    </script>
</body>
</html>
