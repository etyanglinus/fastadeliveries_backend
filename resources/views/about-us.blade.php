@extends('layouts.landing.app')

@section('title',translate('About us'))

@section('content')
    @php($pageService = app(\App\Services\FastaCompanyPages::class))
    @php($entry = $pageService->all()['about'])
    <section class="page-hero">
        <div class="container">
            <h1>{{ $entry['title'] }}</h1>
            <div class="breadcrumb">
                <a href="{{route('home')}}">{{ translate('messages.home') }}</a> / {{ translate('About us') }}
            </div>
        </div>
    </section>

    <section class="page-content">
        <div class="container">
            @if($entry['published'])
                @if(filled($entry['intro']))<p>{{ $entry['intro'] }}</p>@endif
                @include('company.sections', compact('entry', 'pageService'))
            @endif
            @if (filled($data))
            <div class="content-card">
                <h2>{{ $data_title }}</h2>
                <div>{!! $data !!}</div>
            </div>
            @endif
        </div>
    </section>
@endsection
