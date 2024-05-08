@extends('layout')

@section('content')

<div id="audit-log">
    <div class="flexy mb-3">
        <a class="btn icon round mr-2" href="{{ route('customforms.index') }}">&larr;</a>
        <h1 class="fill">
            {{ $title }}
        </h1>
        <a href="{{ route('forms') }}/{{ $name }}/edit" class="btn mr-1">Configure</a>
        <div class="btn-group">
            <a href="{{ route('customforms.export', [$name, 'csv']) }}" class="btn">Export</a>
            <button type="button" class="btn dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <span class="caret"></span>
                <span class="sr-only">Toggle Dropdown</span>
            </button>
            <ul class="dropdown-menu">
                <li><a href="{{ route('customforms.export', [$name, 'csv'] ) }}">Export as CSV</a></li>
                <li><a href="{{ route('customforms.export', [$name, 'json'] ) }}">Export as JSON</a></li>
            </ul>
        </div>
    </div>

    <div class="submissions-list">
        <div class="card flush">
            <table class="dossier">
                <thead>
                    <tr>
                        <th>Datestamp</th>
                        @foreach($columns as $column)
                        <th>{{ $column }}</th>
                        @endforeach
                        <th>&nbsp;</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($submissions as $submission)
                    <tr>
                        <td class="first-cell">
                            <a href="{{ route('customforms.submission', [ $name, $submission->id ]) }}">
                                {{ $submission->created_at->format($datetime_format) }}
                            </a>
                        </td>
                        @foreach(array_keys($columns) as $column)
                        <td>{{ array_get($submission->snapshot, $column) }}</td>
                        @endforeach
                        <td class="column-actions">
                            <div class="btn-group action-more">
                                <button type="button" class="btn-more dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="icon icon-dots-three-vertical"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a href="{{ route('customforms.delete', [ $name, $submission->id ] ) }}">Delete</a>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
