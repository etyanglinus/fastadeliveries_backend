@extends('layouts.admin.app')
@section('title', 'BrandSite')
@section('content')
<div class="content container-fluid">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div><h1 class="page-header-title">BrandSite</h1><p class="text-muted mb-0">Manage storefronts, help sellers finish their sites and review publishing activity.</p></div>
        <a class="btn btn-outline-primary" href="{{ route('admin.business-settings.subscriptionackage.index', ['module' => 'all']) }}">Manage package features</a>
    </div>
    <div class="alert alert-info">BrandSite access is controlled under <strong>Package Available Features</strong> when creating or editing a subscription package. Support editing uses the seller's current entitlement.</div>
    <div class="card mb-4"><div class="card-header"><h2 class="card-title">Seller storefronts</h2></div>
    <div class="table-responsive"><table class="table table-hover"><thead><tr><th>Seller store</th><th>Draft / live revision</th><th>Status</th><th>Seller support</th><th>Publishing controls</th></tr></thead><tbody>
    @forelse($sites as $site)
        <tr>
            <td><strong>{{ $site->name }}</strong><div class="text-muted small">{{ $site->slug }}</div></td>
            <td>{{ $site->revision ?? 0 }} / {{ $site->published_revision ?? 'Not published' }}</td>
            <td><span class="badge {{ $site->suspended_at ? 'badge-soft-danger' : 'badge-soft-success' }}">{{ $site->suspended_at ? 'Suspended' : ($site->published_revision ? 'Published' : 'Draft') }}</span></td>
            <td><form method="POST" action="{{ route('admin.brandsite.launch', $site->store_id) }}">@csrf
                <label class="sr-only" for="reason-{{ $site->store_id }}">Support reason for {{ $site->name }}</label>
                <input id="reason-{{ $site->store_id }}" class="form-control form-control-sm mb-2" name="support_reason" required minlength="8" maxlength="300" placeholder="Seller's support request">
                <button class="btn btn-primary btn-sm" @disabled(!$site->can_edit)>Open site editor</button>
                @unless($site->can_edit)<div class="text-muted small mt-2">{{ $site->suspended_at ? 'Reinstate this site before editing.' : 'An active package with BrandSite is required.' }}</div>@endunless
            </form></td>
            <td>@if($site->id)
                <div class="d-flex flex-wrap gap-2">
                <form method="POST" action="{{ route('admin.brandsite.moderate', $site->id) }}">@csrf<button class="btn btn-outline-secondary btn-sm" name="suspended" value="{{ $site->suspended_at ? '0' : '1' }}">{{ $site->suspended_at ? 'Reinstate' : 'Suspend' }}</button></form>
                @if($site->published_revision)
                    <form method="POST" action="{{ route('admin.brandsite.unpublish', $site->id) }}">@csrf<button class="btn btn-outline-danger btn-sm">Unpublish</button></form>
                    @if(config('brandsite.web_origin'))<a class="btn btn-outline-primary btn-sm" target="_blank" rel="noopener" href="{{ rtrim(config('brandsite.web_origin'), '/') }}/brandsite/{{ $site->slug }}">View live</a>@endif
                @endif
                </div>
            @else <span class="text-muted">Site not started</span> @endif</td>
        </tr>
    @empty <tr><td colspan="5">No seller stores found.</td></tr> @endforelse
    </tbody></table></div><div class="card-footer">{{ $sites->links() }}</div></div>
    <div class="row">
        <div class="col-lg-6"><div class="card mb-4"><div class="card-header"><h2 class="card-title">Domain requests</h2></div><div class="card-body">
            @forelse($domains as $domain)
            <div class="d-flex justify-content-between align-items-center mb-3"><div><strong>{{ $domain->hostname }}</strong><div class="text-muted small">Site {{ $domain->site_id }} · {{ $domain->verified_at ? 'DNS verified; HTTPS pending' : 'Awaiting DNS verification' }}</div></div>
                <form method="POST" action="{{ route('admin.brandsite.domain.remove', $domain->id) }}">@csrf @method('DELETE')<button class="btn btn-outline-danger btn-sm">Remove request</button></form></div>
            @empty <p class="text-muted mb-0">No custom domain requests.</p> @endforelse
            {{ $domains->links() }}
        </div></div></div>
        <div class="col-lg-6"><div class="card"><div class="card-header"><h2 class="card-title">Recent activity</h2></div><div class="card-body">
            @forelse($events as $event)<div class="mb-3"><strong>{{ str_replace('_', ' ', $event->action) }}</strong><div class="small text-muted">{{ $event->created_at }} · Site {{ $event->site_id }} · {{ $event->actor_type }} {{ $event->actor_id }}@if($event->revision) · Revision {{ $event->revision }}@endif</div></div>
            @empty <p class="text-muted">No activity recorded.</p> @endforelse
        </div></div></div>
    </div>
</div>
@endsection
