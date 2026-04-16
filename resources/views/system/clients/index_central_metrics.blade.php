@extends('system.layouts.app')

@section('content')
    <system-clients-index-central-metrics
        :delete-permission="{{ json_encode($delete_permission) }}"
        :plans='@json($plans)'
    ></system-clients-index-central-metrics>
@endsection
