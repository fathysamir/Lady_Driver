<?php
namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\ApiController;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends ApiController
{
    /**
     * Display list of roles
     */
    public function index(Request $request)
    {
        $roles = Role::with('permissions')->whereNotIn('name', ['Client', 'Driver','AdminAdmin111']);
        if ($request->has('search') && $request->search != null) {
            $roles->where('name', 'LIKE', '%' . $request->search . '%');
        }
        $roles = $roles->paginate(10);
        return view('dashboard.roles.index', compact('roles'));
    }

    /**
     * Show form to create new role
     */
    public function create(Request $request)
    {
        $permissions = Permission::all();
        $queryString = $request->query();
        return view('dashboard.roles.create', compact('permissions', 'queryString'));
    }

    /**
     * Store new role
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|unique:roles,name',
            'permissions' => 'array',
        ]);

        // Create Role
        $role = Role::create([
            'name' => $request->name,
        ]);

        // Assign selected permissions
        if ($request->has('permissions')) {
            $permissions = Permission::whereIn('id', $request->permissions)->get();
            $role->syncPermissions($permissions);
        }

        return redirect()->route('roles.index')
            ->with('success', 'Role created successfully');
    }

    /**
     * Show form to edit existing role
     */
    public function edit(Request $request, $id)
    {
        $role            = Role::findOrFail($id);
        $permissions     = Permission::all();
        $rolePermissions = $role->permissions->pluck('id')->toArray();
        $queryString     = $request->query();
        return view('dashboard.roles.edit', compact('role', 'permissions', 'rolePermissions', 'queryString'));
    }

    /**
     * Update role and its permissions
     */
    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        $request->validate([
            'name'        => 'required|unique:roles,name,' . $role->id,
            'permissions' => 'array',
        ]);

        // Update role name
        $role->update([
            'name' => $request->name,
        ]);

        // Sync permissions
        $permissions = Permission::whereIn('id', $request->permissions)->get();
        $role->syncPermissions($permissions);

        return redirect()->route('roles.index')
            ->with('success', 'Role updated successfully');
    }

    /**
     * Delete role
     */
    public function destroy($id)
    {
        $role = Role::findOrFail($id);
        if ($role->name === 'Super Admin') {
            // return redirect()->back()->withErrors([
            //     'msg' => 'You cannot delete the Super Admin role.',
            // ]);
            return redirect()->route('roles.index')
                ->with('error', 'You cannot delete the Super Admin role.');
        }

        // Check if the role has users
        if ($role->users()->count() > 0) {
            // return redirect()->back()->withErrors([
            //     'msg' => 'This role cannot be deleted because it is assigned to users.',
            // ]);
            return redirect()->route('roles.index')
                ->with('error', 'This role cannot be deleted because it is assigned to users.');
        }
        $role->permissions()->detach();
        $role->delete();

        return redirect()->route('roles.index')
            ->with('success', 'Role deleted successfully');
    }
}
