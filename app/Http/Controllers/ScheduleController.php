<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Schedule;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Carbon\Carbon;

class ScheduleController extends Controller
{
    public function index(Request $request)
{
    $user = Auth::user();
    $query = $request->input('query');
    if ($query) {

        $schedules = Schedule::search($query)->get();
    } else {
        if ($user && $user->role === 'admin') {
            $schedules = Schedule::with('user')->get();
        } else {
            
            $schedules = Schedule::where('user_id', $user->id)->with('user')->get();
        }
    }
    return view('dashboard', compact('schedules', 'query'));
}    public function edit(Schedule $schedule)
    {
        return view('schedules.edit', compact('schedule'));
    }
    public function update(Request $request, Schedule $schedule)
    {
        $validatedData = $request->validate([
            'title' => 'required|max:255',
            'description' => 'nullable',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'location' => 'nullable|max:255',
        ]);

        $schedule->update($validatedData);
        return redirect()->route('dashboard')->with('success', 'Schedule updated successfully.');
    }
    public function destroy($id)
    {
        $schedule = Schedule::findOrFail($id);
        return view('schedules.confirm-delete', compact('schedule'));
    }

    public function delete(Request $request, $id)
    {
        $schedule = Schedule::findOrFail($id);
        $schedule->delete();
        return redirect()->route('dashboard')->with('success', 'Schedule deleted successfully.');
    }

    public function create()
    {
        $users = User::where('role', '!=', 'admin')->get();
        return view('schedules.create', compact('users')); 
    }
    public function store(Request $request)
{
    $validatedData = $request->validate([
        'title' => 'required|max:255',
        'description' => 'nullable',
        'start_time' => 'required|date',
        'end_time' => 'required|date|after:start_time',
        'location' => 'nullable|max:255',
    ]);

    if (Auth::user()->role === 'admin') {
        $validatedData['user_id'] = $request->input('user_id');
    } else {
        $validatedData['user_id'] = Auth::id();
    }
    Schedule::create($validatedData);
    return redirect()->route('dashboard')->with('success', 'Schedule added successfully.');
}


public function notification()
{
    $userId = Auth::id();
    $now = Carbon::now();
    $twoDaysLater = $now->copy()->addDays(2);
    $schedulesDue = Schedule::where('end_time', '>=', $now)
    ->where('end_time', '<=', $twoDaysLater)
    ->where('user_id', $userId)
    ->orderBy('end_time', 'asc')
    ->get(['title', 'description', 'start_time', 'end_time', 'location']);


    return view('notification', [
        'schedulesDue' => $schedulesDue,
        'twoDaysLater' => $twoDaysLater,
    ]);
}

}