@foreach(['services' => 'Services', 'company' => 'Company', 'partners' => 'Partners', 'help' => 'Help'] as $group => $label)
    @php($links = array_filter($companyPages, fn ($entry) => $entry['group'] === $group && $entry['published'] && $entry['footer']))
    @if($links || $group === 'help')
        <div>
            <h5>{{ $label }}</h5>
            <div class="f-links">
                @foreach($links as $slug => $entry)
                    <a href="{{ $companyNavigation->url($slug) }}">{{ $entry['title'] }}</a>
                @endforeach
                @if($group === 'help')<a href="{{ route('contact-us') }}">Contact Fasta</a>@endif
            </div>
        </div>
    @endif
@endforeach
