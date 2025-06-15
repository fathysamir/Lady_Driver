<?php
namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class AdminController extends Controller
{
    public function index(Request $request)
    {
        $all_admins = User::whereHas('roles', function ($query) {
            $query->where('roles.name', 'Admin');
        })->orderBy('id', 'desc');

        if ($request->has('search') && $request->search != null) {
            $all_admins->where(function ($query) use ($request) {
                $query->where('name', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('email', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('phone', 'LIKE', '%' . $request->search . '%');
            });
        }
        $all_admins = $all_admins->paginate(12);

        $all_admins->getCollection()->transform(function ($user) {
            // Add the 'image' key based on some condition
            $user->image = getFirstMediaUrl($user, $user->avatarCollection);
            return $user;
        });
        $search = $request->search;
        return view('dashboard.admins.index', compact('all_admins', 'search'));

    }

    public function create()
    {
        return view('dashboard.admins.create');
    }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'name'            => ['required', 'string', 'max:191'],
            'email'           => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->whereNull('deleted_at'),
            ],
            'password'        => ['required', 'string', 'min:8', 'confirmed'],
            'second_password' => ['required', 'string', 'min:8', 'confirmed'],
            'country_code'    => 'required',
            'phone'           => [
                'required',
                Rule::unique('users')->where(function ($query) use ($request) {
                    return $query->where('country_code', $request->country_code)
                        ->whereNull('deleted_at');
                }),
            ],
        ]);

        if ($validator->fails()) {
            return Redirect::back()->withInput()->withErrors($validator);
        }

        $admin = User::create([
            'name'         => $request->name,
            'email'        => $request->email,
            'phone'        => $request->phone,
            'country_code' => $request->country_code,
            'password'     => Hash::make($request->password),
            'password2'    => Hash::make($request->second_password),
            'status'       => 'confirmed',
            'theme'        => 'theme1',
            'gendor'       => 'other',
            'mode'         => 'admin',
        ]);
        $role = Role::where('Name', 'Admin')->first();

        $admin->assignRole([$role->id]);
        if ($request->file('image')) {
            uploadMedia($request->file('image'), $admin->avatarCollection, $admin);
        }
        return redirect('/admin-dashboard/admins');

    }

    public function edit($id)
    {
        $admin = User::findOrFail($id);
        return view('dashboard.admins.edit', compact('admin'));
    }

    public function update(Request $request, $id)
    {

        $validator = Validator::make($request->all(), [
            'name'            => ['required', 'string', 'max:191'],
            'email'           => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($id)->whereNull('deleted_at'),
            ],
            'password'        => ['nullable', 'string', 'min:8', 'confirmed'],
            'second_password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'country_code'    => 'required',

            'phone'           => [
                'required',
                Rule::unique('users')->ignore($id)->where(function ($query) use ($request) {
                    return $query->where('country_code', $request->country_code)
                        ->whereNull('deleted_at');
                }),
            ]]);

        if ($validator->fails()) {
            return Redirect::back()->withInput()->withErrors($validator);
        }

        $admin = User::where('id', $id)->update([
            'name'         => $request->name,
            'email'        => $request->email,
            'phone'        => $request->phone,
            'country_code' => $request->country_code,
            'password'     => Hash::make($request->password),
            'password2'    => Hash::make($request->second_password),
        ]);
        $admin = User::findOrFail($id);
        if ($request->password != null) {
            $admin->password = Hash::make($request->password);
        }

        if ($request->file('image')) {
            uploadMedia($request->file('image'), $admin->avatarCollection, $admin);
        }
        return redirect('/admin-dashboard/admins');

    }

    public function delete(User $admin)
    {
        // Prevent self-deletion
        if (auth()->id() === $admin->id) {
            return redirect()->back()
                ->with('error', 'You cannot delete yourself.');
        }

        try {
            $admin->delete();

            return redirect('/admin-dashboard/admins')
                ->with('success', 'Admin deleted successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error deleting admin: ' . $e->getMessage());
        }
    }
}
