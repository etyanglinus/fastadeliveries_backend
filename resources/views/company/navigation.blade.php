@foreach(['services' => 'Services', 'partners' => 'Partner with us', 'company' => 'Company', 'help' => 'Help'] as $group => $label)
    @php($links = array_filter($companyPages, fn ($entry) => $entry['group'] === $group && $entry['published'] && $entry['header']))
    @if($links)
        <details class="fasta-menu">
            <summary>{{ $label }}</summary>
            <div class="fasta-menu-links">
                @foreach($links as $slug => $entry)
                    <a href="{{ $companyNavigation->url($slug) }}">{{ $entry['title'] }}</a>
                @endforeach
                @if($group === 'partners' && $sellerWorkspace)
                    <a href="{{ $sellerWorkspace }}">Seller sign in</a>
                @endif
            </div>
        </details>
    @endif
@endforeach
