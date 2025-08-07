<!-- resources/views/tests/index.blade.php -->
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-between mb-4">
        <div class="col-md-6">
            <h1>Tests</h1>
        </div>
        <div class="col-md-6 text-right">
            <a href="{{ route('tests.create') }}" class="btn btn-primary">Create New Test</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Questions</th>
                        <th>Time Limit</th>
                        <th>Pass Threshold</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tests as $test)
                    <tr>
                        <td>{{ $test->title }}</td>
                        <td>{{ $test->questions_count }}</td>
                        <td>{{ $test->time_limit_minutes ? $test->time_limit_minutes . ' mins' : 'No limit' }}</td>
                        <td>{{ $test->pass_threshold ? $test->pass_threshold . '%' : 'Not set' }}</td>
                        <td>
                            <a href="{{ route('tests.show', $test->id) }}" class="btn btn-sm btn-info">View</a>
                            <a href="{{ route('tests.edit', $test->id) }}" class="btn btn-sm btn-warning">Edit</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            
            {{ $tests->links() }}
        </div>
    </div>
</div>
@endsection
