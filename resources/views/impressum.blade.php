<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => 'Impressum'])
        <meta name="description" content="Impressum von Nebuliton mit allen gesetzlich erforderlichen Angaben.">
    </head>
    <body class="status-shell min-h-screen text-slate-900 antialiased">
        <div class="status-mesh"></div>

        <div class="relative mx-auto flex min-h-screen w-full max-w-5xl flex-col px-4 py-6 sm:px-6 lg:px-8">
            <header class="status-card px-5 py-5 sm:px-6">
                <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                    <a href="{{ route('home') }}" class="flex items-center gap-4">
                        <div class="status-icon-shell h-12 w-12 overflow-hidden rounded-2xl border-slate-200 bg-white p-0 shadow-[0_10px_24px_rgba(15,23,42,0.08)]">
                            <img src="{{ config('services.nebuliton.logo_url') }}" alt="Nebuliton Logo" class="h-full w-full object-cover" loading="eager">
                        </div>

                        <div>
                            <p class="font-display text-xl font-semibold tracking-tight text-slate-950 sm:text-2xl">Nebuliton</p>
                            <p class="mt-1 text-sm text-slate-500">Impressum und gesetzliche Anbieterkennzeichnung.</p>
                        </div>
                    </a>

                    <a href="{{ route('home') }}" class="status-share-button">
                        @include('partials.status.icon', ['name' => 'arrow-up-right', 'class' => 'h-4 w-4'])
                        Zur Statusseite
                    </a>
                </div>
            </header>

            <main class="flex-1 py-6">
                <article class="status-card px-5 py-6 sm:px-8">
                    <div class="max-w-3xl">
                        <h1 class="font-display text-3xl font-semibold tracking-tight text-slate-950">Impressum</h1>
                        <p class="mt-3 text-sm leading-7 text-slate-500">
                            Angaben gemäß den gesetzlichen Informationspflichten.
                        </p>

                        <div class="mt-8 grid gap-6">
                            <section class="status-card-subtle px-5 py-5">
                                <h2 class="text-sm font-semibold uppercase tracking-[0.16em] text-slate-400">Anbieter</h2>
                                <div class="mt-4 space-y-1 text-sm leading-7 text-slate-700">
                                    <p class="font-semibold text-slate-950">Nebuliton</p>
                                    <p>Inhaber: Christian Hagenacker</p>
                                    <p>Rutkamp 4</p>
                                    <p>24111 Kiel</p>
                                    <p>Deutschland</p>
                                </div>
                            </section>

                            <section class="status-card-subtle px-5 py-5">
                                <h2 class="text-sm font-semibold uppercase tracking-[0.16em] text-slate-400">Kontakt</h2>
                                <div class="mt-4 space-y-1 text-sm leading-7 text-slate-700">
                                    <p>E-Mail: <a href="mailto:admin@nebuliton.de" class="font-medium text-slate-950 hover:text-brand-600">admin@nebuliton.de</a></p>
                                    <p>Telefon: <a href="tel:+491756225187" class="font-medium text-slate-950 hover:text-brand-600">+49 175 6225187</a></p>
                                </div>
                            </section>

                            <section class="status-card-subtle px-5 py-5">
                                <h2 class="text-sm font-semibold uppercase tracking-[0.16em] text-slate-400">Umsatzsteuer-ID</h2>
                                <p class="mt-4 text-sm leading-7 text-slate-700">
                                    Umsatzsteuer-Identifikationsnummer gemäß § 27a Umsatzsteuergesetz:
                                </p>
                                <p class="mt-2 text-sm font-semibold text-slate-950">DE455508340</p>
                            </section>

                            <section class="status-card-subtle px-5 py-5">
                                <h2 class="text-sm font-semibold uppercase tracking-[0.16em] text-slate-400">Verantwortlich für den Inhalt nach § 55 Abs. 2 RStV</h2>
                                <div class="mt-4 space-y-1 text-sm leading-7 text-slate-700">
                                    <p class="font-semibold text-slate-950">Christian Hagenacker</p>
                                    <p>Rutkamp 4</p>
                                    <p>24111 Kiel</p>
                                </div>
                            </section>

                            <section class="status-card-subtle px-5 py-5">
                                <h2 class="text-sm font-semibold uppercase tracking-[0.16em] text-slate-400">Hinweis zu Supportanfragen</h2>
                                <p class="mt-4 text-sm leading-7 text-slate-700">
                                    Die obenstehenden Kontaktdaten dienen ausschließlich den gesetzlich vorgeschriebenen Impressumsangaben. Support-, Technik- oder Produktanfragen werden über diese Adresse nicht bearbeitet. Bitte nutze hierfür unsere offiziellen Kontaktwege auf der Website.
                                </p>
                            </section>

                            <section class="status-card-subtle px-5 py-5">
                                <h2 class="text-sm font-semibold uppercase tracking-[0.16em] text-slate-400">Haftungsausschluss</h2>
                                <p class="mt-4 text-sm leading-7 text-slate-700">
                                    Trotz sorgfältiger inhaltlicher Kontrolle übernehmen wir keine Haftung für Inhalte externer Links. Für den Inhalt verlinkter Seiten sind ausschließlich deren Betreiber verantwortlich.
                                </p>
                            </section>
                        </div>
                    </div>
                </article>
            </main>

            <footer class="border-t border-slate-200/80 px-1 pt-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-slate-500">Stand dieses Impressums: August 2025</p>
                    <div class="flex flex-wrap items-center gap-3">
                        <a href="{{ route('home') }}" class="status-footer-link">
                            Statusseite
                            @include('partials.status.icon', ['name' => 'arrow-up-right', 'class' => 'h-4 w-4'])
                        </a>
                        <a href="{{ config('services.nebuliton.shop_url') }}" class="status-footer-link">
                            Shop
                            @include('partials.status.icon', ['name' => 'arrow-up-right', 'class' => 'h-4 w-4'])
                        </a>
                    </div>
                </div>
            </footer>
        </div>
    </body>
</html>
