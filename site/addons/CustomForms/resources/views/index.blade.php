@extends('layout')

@section('content')

<div id="audit-log">
    <div class="flexy mb-3">
        <h1 class="fill">
            Custom Forms
        </h1>
        <a href="{{ route('forms') }}/create" class="btn btn-primary">Create Form</a>
    </div>

    <div class="forms-list">
        <div class="card flush">
            <table class="dossier">
                <tbody>
                    @foreach($forms as $form)
                    <tr>
                        <td class="cell-title">
                            <div class="stat">
                                <span class="icon icon-documents"></span> {{ $form['count'] }}
                            </div>
                            <a href="{{ route('customforms.submissions', $form['name']) }}">{{ $form['title'] }}</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
