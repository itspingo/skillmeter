<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BaseController extends Controller
{
    protected $model;
    protected $resource;
    protected $collection;
    protected $withRelations = [];

    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    protected function scopeQuery($query)
    {
        return $query->where('created_by', Auth::id());
    }

    public function index(Request $request)
    {
        $query = $this->scopeQuery($this->model::query())
            ->with($this->withRelations)
            ->active(); 

        // Add search/filter logic here if needed

        $perPage = $request->get('per_page', 15);
        $data = $query->paginate($perPage);

        return $this->collection::collection($data);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->model::validationRules());

        $data['client_id'] = auth()->user()->client_id;
        $data['created_by'] = auth()->id();

        $item = $this->model::create($data);

        return new $this->resource($item->load($this->withRelations));
    }

    public function show($id)
    {
        $item = $this->scopeQuery($this->model::query())
            ->with($this->withRelations)
            ->active()
            ->findOrFail($id);

        return new $this->resource($item);
    }

    public function update(Request $request, $id)
    {
        $item = $this->scopeQuery($this->model::query())->findOrFail($id);

        $data = $request->validate($this->model::validationRules('update'));

        $item->update($data);

        return new $this->resource($item->load($this->withRelations));
    }

    public function destroy($id)
    {
        $item = $this->scopeQuery($this->model::query())->findOrFail($id);
        $item->delete();

        return response()->json(['message' => 'Item deleted successfully']);
    }
}
