@extends('system.layouts.app')

@section('content')

    <system-clients-index :plans='@json($plans)'></system-clients-index>

@endsection