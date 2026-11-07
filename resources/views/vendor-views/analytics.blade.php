@extends('layouts.vendor.app')
@section('title', 'Store analytics')
@section('content')
<div class="content container-fluid">
<h1>Store analytics</h1>
<p>Estimated browser visitors and sessions. Counts include tracked events and can be affected by bots or blocked tracking.</p>
<form method="GET" class="d-flex gap-2 mb-4">
<select name="store_id" class="form-control">@foreach($stores as $option)<option value="{{ $option->id }}" @selected($store->id === $option->id)>{{ $option->name }}</option>@endforeach</select>
<select name="days" class="form-control">@foreach([7,30,90] as $period)<option value="{{ $period }}" @selected($days === $period)>Last {{ $period }} days</option>@endforeach</select>
<button class="btn btn-primary">View</button>
</form>
<div class="row">@foreach(['visitors'=>'Estimated visitors','sessions'=>'Sessions','views'=>'Page views','events'=>'Tracked events'] as $key=>$label)<div class="col-md-3"><div class="card p-3 mb-3"><h2>{{ number_format($summary->$key ?? 0) }}</h2><p>{{ $label }}</p></div></div>@endforeach</div>
<div class="card p-3 mb-3"><h2>Completed orders</h2><p>{{ $completedOrders }} delivered orders, including {{ $brandsiteOrders }} attributed to BrandSite.</p><p>These counts come from order records. Browser events do not confirm a sale.</p></div>
<h2>Approximate locations</h2>
<p>Network-based locations can be inaccurate. Unknown means trusted geolocation was unavailable.</p>
<table class="table"><thead><tr><th>Country</th><th>City</th><th>Estimated visitors</th></tr></thead><tbody>
@foreach($locations as $location)<tr><td>{{ $location->country ?: 'Unknown' }}</td><td>{{ $location->city ?: 'Unknown' }}</td><td>{{ $location->visitors }}</td></tr>@endforeach
</tbody></table>
<h2>By channel</h2><table class="table"><thead><tr><th>Channel</th><th>Events</th></tr></thead><tbody>@forelse($channels as $row)<tr><td>{{ $row->channel }}</td><td>{{ $row->total }}</td></tr>@empty<tr><td colspan="2">No tracked visits yet.</td></tr>@endforelse</tbody></table>
<h2>Daily activity</h2><table class="table"><thead><tr><th>Date</th><th>Events</th></tr></thead><tbody>@foreach($daily as $row)<tr><td>{{ $row->day }}</td><td>{{ $row->total }}</td></tr>@endforeach</tbody></table>
</div>
@endsection
