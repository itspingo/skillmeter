<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Models\Question;
use App\Models\AiGenerationRequest;
use App\Http\Resources\QuestionResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Jobs\GenerateAiQuestionsJob;

class QuestionController extends BaseController
{
    protected $model = Question::class;
    protected $resource = QuestionResource::class;
    protected $collection = QuestionResource::class; // Assuming QuestionResource handles collections
    protected $withRelations = ['type', 'category', 'difficulty', 'creator', 'options', 'tags'];

    /**
     * Display a listing of the resource scoped to the authenticated user.
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $questions = Auth::user()->questions()->with($this->withRelations)->paginate($perPage);
        return $this->collection::collection($questions);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Assuming Question model has static rules, otherwise define them here
        $validatedData = $request->validate(Question::$rules ?? []);
        $question = Auth::user()->questions()->create($validatedData);
        return new $this->resource($question->load($this->withRelations));
    }

    /**
     * Display the specified resource scoped to the authenticated user.
     */
    public function show($id)
    {
        $question = Auth::user()->questions()->with($this->withRelations)->findOrFail($id);
        return new $this->resource($question);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $question = Auth::user()->questions()->findOrFail($id);
        $validatedData = $request->validate(Question::$rules ?? []);
        $question->update($validatedData);
        return new $this->resource($question->load($this->withRelations));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $question = Auth::user()->questions()->findOrFail($id);
        $question->delete();
        return response()->json(['message' => 'Question deleted successfully.']);
    }

    public function generateAiQuestions(Request $request)
    {
        $request->validate([
            'topic' => 'required|string|max:255',
            'difficulty' => 'required|in:Beginner,Intermediate,Advanced,Expert',
            'question_type' => 'required|in:MCQ_Single,MCQ_Multiple,True_False',
            'count' => 'required|integer|min:1|max:20',
            'category_id' => 'nullable|exists:question_categories,id',
        ]);

        // Create AI generation request
        $aiRequest = AiGenerationRequest::create([
            'requested_by' => auth()->id(),
            'request_type' => 'question',
            'parameters' => $request->all(),
            'client_id' => auth()->user()->client_id,
        ]);

        // Dispatch job to handle AI generation
        // dispatch(new GenerateAiQuestionsJob($aiRequest));

        return response()->json([
            'message' => 'AI generation request submitted successfully',
            'request_id' => $aiRequest->id,
        ]);
    }

    public function getAiGeneratedQuestions($requestId)
    {
        $aiRequest = AiGenerationRequest::where('requested_by', Auth::id())->findOrFail($requestId);
        
        if ($aiRequest->status !== 'completed') {
            return response()->json([
                'message' => 'Request is still processing',
                'status' => $aiRequest->status,
            ], 202);
        }

        $questions = $aiRequest->generatedContent()
            ->where('content_type', 'question')
            ->get();

        return response()->json([
            'data' => $questions,
        ]);
    }
}
