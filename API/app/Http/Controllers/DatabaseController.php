<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class DatabaseController extends Controller
{
    /**
     * WARNING: This controller allows direct execution of raw SQL queries.
     * This is a significant security risk and should only be used for administrative
     * purposes by trusted users. It is protected by 'admin' and 'super-admin'
     * abilities, but if an admin account is compromised, this endpoint could be
     * used to damage or exfiltrate data. USE WITH EXTREME CAUTION.
     */

    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'ability:admin,super-admin']);
    }

    /**
     * Execute safe database operations
     */
    public function executeQuery(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|max:1000',
            'type' => 'required|in:select,insert,update,delete,alter,create,drop',
            'confirm' => 'required|boolean|accepted'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors()
            ], 422);
        }

        try {
            // Block dangerous operations in production
            if (app()->environment('production')) {
                $blockedTypes = ['alter', 'create', 'drop', 'truncate'];
                if (in_array($request->type, $blockedTypes)) {
                    throw new \Exception("This operation is blocked in production");
                }
            }

            $start = microtime(true);
            $result = null;
            $data = [];

            if ($request->type === 'select') {
                $data = DB::select($request->query);
                $result = count($data);
            } else {
                $result = DB::statement($request->query);
            }
            
            $executionTime = round((microtime(true) - $start) * 1000, 2);

            return response()->json([
                'success' => true,
                'execution_time_ms' => $executionTime,
                'affected_rows' => $result,
                'data' => $data,
                'warnings' => "CRITICAL: Always backup database before executing raw queries. This is a high-risk operation."
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'trace' => app()->environment('local') ? $e->getTrace() : null
            ], 500);
        }
    }
}
