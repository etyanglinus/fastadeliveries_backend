@extends('layouts.admin.app')
@section('title', 'Company website')

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title">Company website</h1>
        <p>Edit the company site’s pages, navigation and homepage content. Terms, privacy and the existing About story still use their Business Pages editors.</p>
        <p><a href="{{ route('admin.business-settings.admin-landing-page-settings', 'setup') }}">Homepage sections and images</a> · <a href="{{ route('admin.business-settings.about-us') }}">About story</a> · <a href="{{ route('admin.business-settings.terms-and-conditions') }}">Terms</a></p>
    </div>
    @if(session('company_saved'))<div class="alert alert-success" role="status">{{ session('company_saved') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="row">
        <aside class="col-md-3 mb-3">
            <nav class="list-group" aria-label="Company pages">
                @foreach($pages as $key => $entry)
                    <a class="list-group-item list-group-item-action {{ $key === $slug ? 'active' : '' }}" href="{{ route('admin.business-settings.company-pages.edit', $key) }}">{{ $entry['title'] }}</a>
                @endforeach
            </nav>
        </aside>
        <div class="col-md-9">
            <form method="POST" action="{{ route('admin.business-settings.company-pages.update', $slug) }}" class="card p-4">
                @csrf
                <div class="d-flex justify-content-between flex-wrap gap-2"><h2>{{ $page['title'] }}</h2><a href="{{ $previewUrl }}">View public page</a></div>
                <label class="mt-3" for="page-title">Page title and navigation label</label>
                <input class="form-control" id="page-title" name="title" maxlength="100" value="{{ old('title', $page['title']) }}" required>
                <label class="mt-3" for="page-intro">Introduction</label>
                <textarea class="form-control" id="page-intro" name="intro" rows="3" maxlength="1200">{{ old('intro', $page['intro']) }}</textarea>
                <fieldset class="mt-3">
                    <legend class="h5">Visibility</legend>
                    @foreach(['published'=>'Published', 'header'=>'Show in header', 'footer'=>'Show in footer'] as $key => $label)
                        @if($key === 'published' || $slug !== 'home')
                            <input type="hidden" name="{{ $key }}" value="0">
                            <label class="d-inline-block mr-3"><input type="checkbox" name="{{ $key }}" value="1" @checked(old($key, $page[$key]))> {{ $label }}</label>
                        @else
                            <input type="hidden" name="{{ $key }}" value="0">
                        @endif
                    @endforeach
                    @if(in_array($slug, ['home','about'], true))<p class="small text-muted">Publishing here controls the extra company content. The homepage and existing About story remain available.</p>@endif
                </fieldset>
                <label class="mt-3" for="page-cta">Page button</label>
                <select class="form-control" id="page-cta" name="cta">
                    <option value="">No button</option>
                    @foreach($actions as $key => $label)<option value="{{ $key }}" @selected(old('cta', $page['cta']) === $key)>{{ $label }}</option>@endforeach
                </select>
                <label class="mt-3" for="page-cta-label">Custom button label (optional)</label>
                <input class="form-control" id="page-cta-label" name="cta_label" maxlength="80" value="{{ old('cta_label', $page['cta_label'] ?? '') }}">
                <h3 class="h5 mt-4">Page sections</h3>
                <p class="small text-muted">Use the slots in order. Clear both fields to remove a section.</p>
                @for($i = 0; $i < 8; $i++)
                    <details class="border rounded p-3 mb-2" @if(filled(old("sections.$i.heading", $page['sections'][$i]['heading'] ?? ''))) open @endif>
                        <summary>Section {{ $i + 1 }}</summary>
                        <label class="mt-2" for="heading-{{ $i }}">Heading</label>
                        <input class="form-control" id="heading-{{ $i }}" name="sections[{{ $i }}][heading]" maxlength="120" value="{{ old("sections.$i.heading", $page['sections'][$i]['heading'] ?? '') }}">
                        <label class="mt-2" for="body-{{ $i }}">Text</label>
                        <textarea class="form-control" id="body-{{ $i }}" name="sections[{{ $i }}][body]" maxlength="2500" rows="4">{{ old("sections.$i.body", $page['sections'][$i]['body'] ?? '') }}</textarea>
                    </details>
                @endfor
                <div class="mt-3"><button class="btn btn-primary" type="submit">Save page</button></div>
            </form>
        </div>
    </div>
</div>
@endsection
