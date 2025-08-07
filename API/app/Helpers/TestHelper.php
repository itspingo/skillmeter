<?php

namespace App\Helpers;

use App\Models\TestAttempt;
use App\Models\QuestionResponse;
use App\Models\Question;

class TestHelper
{
    public static function calculateTestScore(TestAttempt $attempt)
    {
        $responses = $attempt->responses()->with('question')->get();
        
        $score = 0;
        $maxScore = 0;

        foreach ($responses as $response) {
            $question = $response->question;
            $maxScore += $question->max_score;

            if ($response->is_correct) {
                $score += $question->max_score;
            } elseif ($question->type->has_options && !$question->type->is_scorable) {
                // For non-scorable questions, give full points
                $score += $question->max_score;
            }
        }

        $percentage = $maxScore > 0 ? ($score / $maxScore) * 100 : 0;

        return [
            'score' => $score,
            'max_score' => $maxScore,
            'percentage' => $percentage,
        ];
    }

    public static function generateTestReport(TestAttempt $attempt)
    {
        $responses = $attempt->responses()
            ->with(['question', 'question.type', 'selectedOptions'])
            ->get();

        $report = [
            'overall' => [
                'score' => $attempt->score,
                'max_score' => $attempt->max_score,
                'percentage' => $attempt->percentage,
                'is_passed' => $attempt->is_passed,
                'time_spent' => $attempt->time_spent_seconds,
            ],
            'by_question_type' => [],
            'responses' => [],
        ];

        // Group by question type
        foreach ($responses as $response) {
            $questionType = $response->question->type->name;

            if (!isset($report['by_question_type'][$questionType])) {
                $report['by_question_type'][$questionType] = [
                    'correct' => 0,
                    'total' => 0,
                    'score' => 0,
                    'max_score' => 0,
                ];
            }

            $report['by_question_type'][$questionType]['total']++;
            $report['by_question_type'][$questionType]['max_score'] += $response->question->max_score;

            if ($response->is_correct) {
                $report['by_question_type'][$questionType]['correct']++;
                $report['by_question_type'][$questionType]['score'] += $response->question->max_score;
            }

            // Add detailed response
            $report['responses'][] = [
                'question_id' => $response->question_id,
                'question_text' => $response->question->question_text,
                'question_type' => $questionType,
                'response_text' => $response->response_text,
                'selected_options' => $response->selectedOptions->pluck('option_id'),
                'is_correct' => $response->is_correct,
                'score' => $response->score,
                'max_score' => $response->max_score,
                'explanation' => $response->question->explanation,
            ];
        }

        return $report;
    }

    public static function validateTestAccess($testId, $userId)
    {
        // Implement logic to validate if user has access to the test
        // This could check invitations, company permissions, etc.
        return true;
    }
}
