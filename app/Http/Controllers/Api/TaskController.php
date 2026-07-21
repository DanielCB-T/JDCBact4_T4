<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreTaskRequest;
use App\Http\Requests\Api\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    /**
     * GET /api/tasks
     * Lista las tareas de forma paginada.
     * Query params opcionales: ?per_page=10&page=1
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 10);
        $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 10;

        $tasks = Task::orderBy('id', 'desc')->paginate($perPage);

        return response()->json([
            'message' => $tasks->isEmpty() ? 'No hay tareas registradas.' : 'Tareas obtenidas correctamente.',
            'data' => TaskResource::collection($tasks->items()),
            'meta' => [
                'current_page' => $tasks->currentPage(),
                'last_page' => $tasks->lastPage(),
                'per_page' => $tasks->perPage(),
                'total' => $tasks->total(),
            ],
        ], 200);
    }

    /**
     * GET /api/tasks/{task}
     * Muestra una tarea individual.
     */
    public function show(Task $task): JsonResponse
    {
        return response()->json([
            'message' => 'Tarea obtenida correctamente.',
            'data' => new TaskResource($task),
        ], 200);
    }

    /**
     * POST /api/tasks
     * Crea una nueva tarea. Valida con StoreTaskRequest (422 si falla).
     */
    public function store(StoreTaskRequest $request): JsonResponse
    {
        $task = Task::create($request->validated());

        return response()->json([
            'message' => 'Tarea creada correctamente.',
            'data' => new TaskResource($task),
        ], 201);
    }

    /**
     * PUT/PATCH /api/tasks/{task}
     * Actualiza una tarea existente. Valida con UpdateTaskRequest (422 si falla).
     */
    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        $task->update($request->validated());

        return response()->json([
            'message' => 'Tarea actualizada correctamente.',
            'data' => new TaskResource($task->fresh()),
        ], 200);
    }

    /**
     * DELETE /api/tasks/{task}
     * Elimina una tarea.
     */
    public function destroy(Task $task): JsonResponse
    {
        $task->delete();

        return response()->json([
            'message' => 'Tarea eliminada correctamente.',
        ], 200);
    }
}
