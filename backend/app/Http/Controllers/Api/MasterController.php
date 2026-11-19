<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Validation\ValidationException;

abstract class MasterController extends Controller
{
    /**
     * @return class-string<Model>
     */
    abstract protected function modelClass(): string;

    /**
     * @return class-string<JsonResource>
     */
    abstract protected function resourceClass(): string;

    /**
     * @return list<string>
     */
    abstract protected function searchColumns(): array;

    /**
     * @return list<string>
     */
    protected function eagerLoad(): array
    {
        return [];
    }

    protected function applyFilters($query, Request $request): void {}

    public function index(Request $request)
    {
        $query = $this->modelClass()::query()->with($this->eagerLoad());

        if ($search = trim((string) $request->query('search'))) {
            $query->where(function ($q) use ($search) {
                foreach ($this->searchColumns() as $column) {
                    $q->orWhere($column, 'like', "%{$search}%");
                }
            });
        }

        $resource = $this->resourceClass();
        $this->applyFilters($query, $request);
        $perPage = min(max((int) $request->query('per_page', 15), 1), 100);

        return $resource::collection($query->orderBy('id')->paginate($perPage)->withQueryString());
    }

    public function show(string $id)
    {
        $resource = $this->resourceClass();

        return new $resource($this->find($id));
    }

    public function destroy(string $id)
    {
        try {
            $this->find($id)->delete();
        } catch (QueryException) {
            throw ValidationException::withMessages([
                'id' => 'Data tidak bisa dihapus karena masih dipakai data lain.',
            ]);
        }

        return response()->json(['message' => 'Data dihapus.']);
    }

    protected function find(string $id): Model
    {
        return $this->modelClass()::findOrFail($id);
    }
}
