@extends('layout')

@section('content')

    <script>
        Statamic.Publish = {
            contentData: {!! json_encode($data) !!},
        };
    </script>

    <publish title="{{ 'Deploy message' }}"
             :is-new="false"
             fieldset-name="deploy_message"
             submit-url="{{ $submitUrl }}"
             :remove-title="true"
    ></publish>

@endsection