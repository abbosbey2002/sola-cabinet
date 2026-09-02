@extends('layouts.guest')
@section('title', trans('errors.service_unavailable').' - ')

@section('content')
    <section class="u-card u-card-hero u-rise w-full overflow-hidden p-6 sm:p-8" style="--i:1" aria-labelledby="unavailable-title">
        <div class="u-page-head__identity mt-1">
            <span class="u-page-head__icon" aria-hidden="true">
                <x-icon name="alert" size="size-6"/>
            </span>
            <h1 id="unavailable-title" class="u-page-head__title text-2xl">@lang('errors.service_unavailable')</h1>
            <p class="u-page-head__lead">@lang('errors.service_unavailable_hint')</p>
        </div>

        <a href="{{ url()->current() }}" class="u-btn-primary mt-8 inline-flex w-full text-lg">
            <x-icon name="refresh" size="size-5"/>@lang('errors.service_unavailable_retry')
        </a>
    </section>
@endsection
