<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Http\Controllers\Controller;
use Illuminate\Validation\Rule;

class UniversalDataController extends Controller
{
    /**
     * Handle all CRUD operations
     * POST /api/data/{operation}
     */
    public function handleDataOperation(Request $request, $operation)
    {
        $validated = $request->validate([
            'table' => ['required', 'string', Rule::in($this->getAllowedTables())],
            'data' => 'required_if:operation,create,update|array',
            'where' => 'nullable|array',
            'fields' => 'nullable|array',
            'limit' => 'nullable|integer|max:100'
        ]);

        try {
            return match($operation) {
                'create' => $this->createRecord($validated),
                'read' => $this->readRecords($validated),
                'update' => $this->updateRecords($validated),
                'delete' => $this->deleteRecords($validated),
                default => response()->json(['error' => 'Invalid operation'], 400)
            };
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTrace() : null
            ], 500);
        }
    }

    /**
     * Get table schema
     * GET /api/schema/{table}
     */
    public function getTableSchema($table)
    {
        if (!in_array($table, $this->getAllowedTables())) {
            return response()->json(['error' => 'Table not accessible'], 403);
        }

        return response()->json([
            'fields' => $this->getColumnInfo($table),
            'relations' => $this->getTableRelations($table)
        ]);
    }

    protected function createRecord($data)
    {
        $id = DB::table($data['table'])->insertGetId($data['data']);
        return response()->json([
            'success' => true,
            'id' => $id
        ], 201);
    }

    protected function readRecords($data)
    {
        $query = DB::table($data['table']);
        
        if (!empty($data['where'])) {
            $query->where($data['where']);
        }
        
        if (!empty($data['fields'])) {
            $query->select($data['fields']);
        }
        
        $results = $query->limit($data['limit'] ?? 10)->get();
        
        return response()->json([
            'data' => $results,
            'count' => $results->count()
        ]);
    }

    protected function updateRecords($data)
    {
        $query = DB::table($data['table']);
        
        if (!empty($data['where'])) {
            $query->where($data['where']);
        }
        
        $affected = $query->update($data['data']);
        
        return response()->json([
            'success' => true,
            'affected_rows' => $affected
        ]);
    }

    protected function deleteRecords($data)
    {
        $query = DB::table($data['table']);
        
        if (!empty($data['where'])) {
            $query->where($data['where']);
        }
        
        $affected = $query->delete();
        
        return response()->json([
            'success' => true,
            'affected_rows' => $affected
        ]);
    }

    protected function getAllowedTables()
    {
        return [
            'users',
            'tests',
            'questions',
            'question_options',
            'test_attempts',
            'cheating_data',
            'ai_generation_requests',
            'ai_generated_content'
        ];
    }

    protected function getColumnInfo($table)
    {
        return collect(Schema::getColumnListing($table))
            ->mapWithKeys(function($column) use ($table) {
                $type = Schema::getColumnType($table, $column);
                return [
                    $column => [
                        'type' => $type,
                        'required' => !Schema::isNullable($table, $column)
                    ]
                ];
            });
    }

    protected function getTableRelations($table)
    {
        // This would be enhanced based on your actual relationships
        $relations = [
            'tests' => ['questions', 'invitations'],
            'questions' => ['options', 'tags'],
            'cheating_data' => ['user', 'test']
        ];

        return $relations[$table] ?? [];
    }
}