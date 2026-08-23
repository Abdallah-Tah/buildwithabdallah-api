@extends('layouts.site', [
    'title' => 'Not found',
    'description' => 'The page you asked for is not part of this service.',
])

@section('content')

    <section class="hero">
        <div class="shell hero-solo">
            <div class="reveal">
                <span class="eyebrow">404</span>
                <h1>This route is not in the contract.</h1>
                <p class="lede">
                    Nothing answers at this path. The signature gate would reject it anyway,
                    so here is a honest page instead.
                </p>
                <div class="hero-actions">
                    <a class="btn btn--primary" href="{{ route('page.home') }}">Back to the overview</a>
                    <a class="btn" href="{{ route('page.docs') }}">Read the API docs</a>
                </div>
            </div>
        </div>
    </section>

@endsection
