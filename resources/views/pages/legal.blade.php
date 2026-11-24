@extends('layouts.app')

@section('title', $title)

@section('content')
@include('partials.page-hero', [
    'eyebrow' => $eyebrow,
    'title' => $title,
    'subtitle' => $intro,
])

<section class="max-w-3xl mx-auto px-4 sm:px-6 py-12 sm:py-16 space-y-8">
    @foreach($sections as $section)
        <article class="rounded-2xl border border-slate-100 bg-white p-6 sm:p-8">
            <h2 class="text-xl font-extrabold text-navy-900">{{ $section[0] }}</h2>
            <p class="mt-3 text-slate-600 leading-relaxed">{{ $section[1] }}</p>
        </article>
    @endforeach
</section>
@endsection
