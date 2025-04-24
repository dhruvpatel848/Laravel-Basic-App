<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class EmployeeController extends Controller
{
    public function register()
    {
        return view("register");
    }

    public function customregister(Request $request)
    {
        $validated = $request->validate([
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'fname' => 'required',
            'mname' => 'nullable',
            'lname' => 'required',
            'gender' => 'required|in:male,female',
            'hobbies' => 'nullable|array',
            'hobbies.*' => 'nullable|string',
            'address' => 'required',
            'email' => 'required|email|unique:employees,email',
            'number' => 'required|unique:employees,number|digits:10',
            'password' => 'required|min:6',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['hobbies'] = json_encode($request->hobbies);

        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');
            $filename = time() . '.' . $photo->getClientOriginalExtension();
            $path = $photo->storeAs('public/photos', $filename);
            $validated['photo'] = $filename;
        }

        DB::table('employees')->insert($validated);

        return redirect()->route('login');
    }

    public function index()
    {
        $employees = DB::table('employees')->get();
        return view('index', compact('employees'));
    }

    public function delete(Request $request)
    {
        $employee = DB::table('employees')->where('id', $request->id)->first();
        if ($employee && $employee->photo) {
            Storage::delete('public/photos/' . $employee->photo);
        }
        DB::table('employees')->where('id', $request->id)->delete();
        return redirect()->route('index');
    }

    public function bDelete(Request $request)
    {
        $selectedIds = $request->input('selected');
        $employees = DB::table('employees')->whereIn('id', $selectedIds)->get();
        foreach ($employees as $employee) {
            if ($employee->photo) {
                Storage::delete('public/photos/' . $employee->photo);
            }
        }
        DB::table('employees')->whereIn('id', $selectedIds)->delete();
        return redirect()->route('index');
    }

    public function update(Request $request, $id)
    {
        // Validate the incoming request data
        $validated = $request->validate([
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'fname' => 'required',
            'mname' => 'nullable',
            'lname' => 'required',
            'gender' => 'required|in:male,female',
            'hobbies' => 'nullable|array',
            'hobbies.*' => 'nullable|string',
            'address' => 'required',
            'email' => 'required|email|unique:employees,email,' . $id,
            'number' => 'required|unique:employees,number,' . $id . '|digits:10',
        ]);
    
        $employee = DB::table('employees')->where('id', $id)->first();
        $data = $validated;
    

        $data['hobbies'] = json_encode($request->hobbies);
    
        if ($request->hasFile('photo')) {
            // If the employee has an existing photo, delete it
            if ($employee && $employee->photo) {
                Storage::delete('public/photos/' . $employee->photo);
            }
    
            // Store the new photo file and update the data array
            $photo = $request->file('photo');
            $filename = time() . '.' . $photo->getClientOriginalExtension();
            $path = $photo->storeAs('public/photos', $filename);
    
            // Log the path and filename for debugging
            \Log::info('Photo stored at: ' . $path);
    
            $data['photo'] = $filename;
        }
    
        // Update the employee record in the database
        DB::table('employees')->where('id', $id)->update($data);
    
        // Redirect to the index page
        return redirect()->route('index');
    }    

    public function status(Request $request, $id)
    {
        $employee = DB::table('employees')->where('id', $id)->first();
        if (!$employee) {
            return redirect()->back()->with('error', 'Employee not found.');
        }

        $newStatus = $employee->status == 1 ? 0 : 1;
        DB::table('employees')->where('id', $id)->update(['status' => $newStatus]);

        return redirect()->back()->with('success', 'Employee status updated successfully.');
    }

    public function editRegistration($id, Request $request)
    {
        $employee = DB::table('employees')->where('id', $id)->first();
        return view('update', compact('id', 'employee'));
    }

    public function login(Request $request)
    {
        return view('login');
    }

    public function customlogin(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        $employee = DB::table('employees')->where('email', $validated['email'])->first();

        if (!$employee || !Hash::check($validated['password'], $employee->password)) {
            return redirect()->back()->with('error', 'Invalid credentials.');
        }

        $request->session()->put('employee', $employee);

        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        $request->session()->forget('employee');
        return redirect()->route('login');
    }

    public function profile(Request $request)
    {
        $employee = $request->session()->get('employee');
        return view('profile', compact('employee'));
    }

    public function dashboard(Request $request)
    {
        $employee = $request->session()->get('employee');

        if (!$employee) {
            return redirect()->route('login')->with('error', 'Please log in to access the dashboard.');
        }

        $users = DB::table('employees')
            ->join('roles', 'employees.role_id', '=', 'roles.role_id')
            ->where('employees.id', '!=', $employee->id)
            ->select('employees.*', 'roles.role as role_name')
            ->get();

        $employeeRole = DB::table('roles')->where('role_id', $employee->role_id)->value('role');

        return view('dashboard', compact('employee', 'users', 'employeeRole'));
    }

    public function view($id, Request $request)
    {
        $employee = $request->session()->get('employee');

        if (!$employee) {
            return redirect()->route('login')->with('error', 'Please log in to access the dashboard.');
        }

        $user = DB::table('employees')->where('id', $id)->first();

        return view('view', compact('user'));
    }
}
