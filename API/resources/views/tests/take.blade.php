<!-- resources/views/tests/take.blade.php -->
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">
                    <h2>{{ $test->title }}</h2>
                    <div class="float-right">
                        <span id="timer" class="badge badge-primary"></span>
                    </div>
                </div>
                
                <div class="card-body">
                    <form id="testForm" action="{{ route('attempts.submit', $attempt->id) }}" method="POST">
                        @csrf
                        
                        <div class="test-instructions mb-4">
                            {!! $test->instructions !!}
                        </div>
                        
                        @foreach($test->questions as $index => $question)
                        <div class="question-card mb-4" data-question-id="{{ $question->id }}">
                            <div class="question-header">
                                <h4>Question {{ $index + 1 }}</h4>
                                @if($question->time_limit_seconds)
                                <small class="text-muted">Time limit: {{ $question->time_limit_seconds }} seconds</small>
                                @endif
                            </div>
                            
                            <div class="question-body">
                                <p class="question-text">{{ $question->question_text }}</p>
                                
                                @if($question->type->has_options)
                                <div class="options">
                                    @foreach($question->options as $option)
                                    <div class="form-check">
                                        <input class="form-check-input" type="{{ $question->type->name === 'MCQ_Single' ? 'radio' : 'checkbox' }}" 
                                               name="responses[{{ $question->id }}][selected_options][]" 
                                               id="option_{{ $option->id }}" 
                                               value="{{ $option->id }}">
                                        <label class="form-check-label" for="option_{{ $option->id }}">
                                            {{ $option->option_text }}
                                        </label>
                                    </div>
                                    @endforeach
                                </div>
                                @else
                                <div class="form-group">
                                    @if($question->type->name === 'Short_Answer')
                                    <input type="text" class="form-control" 
                                           name="responses[{{ $question->id }}][response_text]">
                                    @else
                                    <textarea class="form-control" rows="5"
                                              name="responses[{{ $question->id }}][response_text]"></textarea>
                                    @endif
                                </div>
                                @endif
                            </div>
                        </div>
                        @endforeach
                        
                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">Submit Test</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Timer functionality
    const timeLimit = {{ $test->time_limit_minutes ? $test->time_limit_minutes * 60 : 0 }};
    let timeLeft = timeLimit;
    
    function updateTimer() {
        if (timeLeft <= 0) {
            document.getElementById('testForm').submit();
            return;
        }
        
        const minutes = Math.floor(timeLeft / 60);
        const seconds = timeLeft % 60;
        document.getElementById('timer').textContent = 
            `${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
            
        timeLeft--;
        setTimeout(updateTimer, 1000);
    }
    
    if (timeLimit > 0) {
        updateTimer();
    }
    
    // Auto-save functionality
    setInterval(() => {
        const formData = new FormData(document.getElementById('testForm'));
        fetch("{{ route('attempts.autosave', $attempt->id) }}", {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        }).then(response => {
            console.log('Auto-saved progress');
        });
    }, 30000); // Save every 30 seconds
</script>
@endsection
