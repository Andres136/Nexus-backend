<?php

namespace App\Http\Controllers;

use App\Services\SessionService;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    protected $sessionService;

    public function __construct(SessionService $sessionService)
    {
        $this->sessionService = $sessionService;
    }
    public function index()
    {
        $sessions = $this->sessionService->getUserSessions(auth()->id());
        return response()->json($sessions);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->sessionService->deleteSession($id, auth()->id());
        return response()->json(['message' => 'Session deleted successfully']);
    }
        public function destroyOthers(Request $request)
    {
        $this->sessionService->deleteOtherSessions(
            auth()->id(),
            $request->session()->getId()
        );

        return response()->json(['message' => 'Otras sesiones cerradas']);
    }
}
