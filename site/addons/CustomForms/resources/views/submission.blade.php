@extends('layout')

@section('content')

<div id="audit-log">
    <div class="flexy mb-3">
        <a class="btn icon round mr-2" href="{{ route('customforms.submissions', $name) }}">&larr;</a>
        <h1 class="fill">
            Submission
        </h1>
        <a href="{{ route('customforms.delete', [ $name, $submission->id ] ) }}" class="btn">Delete submission</a>
    </div>

    <div class="card">

        <p>
            <strong>Form:</strong>
            {{ $title }}
        </p>

        @if (isset($submission->ip))
        <p class="mb-0">
            <strong>IP address:</strong>
            {{ $submission->ip }}
        </p>
        @endif
    </div>

    <div class="card">
        <table class="dossier mt-0">
            <tbody>
                <tr>
                    <th width="25%">Date</th>
                    <td>
                        {{ $submission->created_at->format($datetime_format) }}
                        ({{ $submission->created_at->tzName }})
                    </td>
                </tr>
                @foreach($submission->snapshot as $column => $value)
                <tr>
                    <th width="25%">{{ isset($columns[$column]) ? $columns[$column] : $column }}</th>
                    <td>{{ $value }}</td>
                </tr>
                @endforeach
            </tbody>
    </div>

</div>

@endsection
