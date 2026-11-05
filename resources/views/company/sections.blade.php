@if($entry['sections'])
    <div class="fasta-company-grid">
        @foreach($entry['sections'] as $section)
            <article class="content-card"><h2>{{ $section['heading'] }}</h2><p class="fasta-company-copy">{{ $section['body'] }}</p></article>
        @endforeach
    </div>
@endif
@php($actionUrl = $pageService->action($entry['cta']))
@if($actionUrl)
    <a class="fasta-order-link" href="{{ $actionUrl }}">{{ ($entry['cta_label'] ?? '') ?: \App\Services\FastaCompanyPages::ACTIONS[$entry['cta']] }}</a>
@endif
