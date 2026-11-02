@extends('layouts.blank')

@section('content')
    <div class="text-center text-white mb-4">
        <h2>Software Installation</h2>
        <h6 class="fw-normal">Continue to configure the application database.</h6>
    </div>

    <div class="pb-2">
        <div class="progress" role="progressbar" aria-label="Software installation progress"
             aria-valuenow="40" aria-valuemin="0" aria-valuemax="100" data-bs-toggle="tooltip"
             data-bs-placement="top">
            <div class="progress-bar" style="width: 40%"></div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="p-4 mb-md-3 mx-xl-4 px-md-5 text-center">
            <h5 class="fw-bold text-uppercase">Step 2</h5>
            <p class="mb-4">Purchase verification is not required. Continue to database setup.</p>
            <a href="{{ route('step3', ['token' => bcrypt('step_3')]) }}" class="btn btn-dark px-sm-5">
                Continue
            </a>
        </div>
    </div>
@endsection
