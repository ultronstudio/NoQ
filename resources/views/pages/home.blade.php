@extends('layouts.app')

@section('title', 'Virtuální čekací fronty')

@section('content')
    <div class="home-page">
        <div class="container">
            <section class="home-intro">
                <h1>NoQ</h1>

                <p>
                    Systém pro správu virtuálních čekacích front.
                </p>
            </section>

            <section class="home-section">
                <h2>Virtuální čekací fronty</h2>

                <p>
                    NoQ umožňuje návštěvníkům zařadit se do čekací fronty
                    pomocí odkazu nebo QR kódu a sledovat svůj aktuální stav
                    bez nutnosti čekat na jednom místě.
                </p>

                <p>
                    Obsluha může prostřednictvím administračního rozhraní
                    spravovat fronty, odbavovat čekající návštěvníky
                    a řídit jednotlivá obslužná místa.
                </p>
            </section>

            <section class="home-section">
                <h2>Vstup do fronty</h2>

                <p class="mb-0">
                    Pro vstup do konkrétní fronty použijte odkaz nebo QR kód
                    poskytnutý jejím provozovatelem.
                </p>
            </section>
        </div>
    </div>
@endsection
